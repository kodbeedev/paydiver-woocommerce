<?php
/**
 * Jomabee redirect payment gateway for WooCommerce.
 *
 * @package Jomabee\WooCommerce
 */

if (! defined('ABSPATH')) {
    exit;
}

class WC_Gateway_Jomabee extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id                 = 'jomabee';
        $this->method_title       = __('Jomabee', 'jomabee-woocommerce');
        $this->method_description = __('Accept bKash, Nagad, Rocket and Upay payments via Jomabee.', 'jomabee-woocommerce');
        $this->has_fields         = false;
        $this->supports           = ['products'];

        $this->init_form_fields();
        $this->init_settings();

        $this->title       = $this->get_option('title');
        $this->description = $this->get_option('description');

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        // Server-to-server webhook: https://site/?wc-api=jomabee
        add_action('woocommerce_api_jomabee', [$this, 'handle_webhook']);
    }

    public function init_form_fields(): void
    {
        $this->form_fields = [
            'enabled' => [
                'title'   => __('Enable/Disable', 'jomabee-woocommerce'),
                'type'    => 'checkbox',
                'label'   => __('Enable Jomabee', 'jomabee-woocommerce'),
                'default' => 'no',
            ],
            'title' => [
                'title'       => __('Title', 'jomabee-woocommerce'),
                'type'        => 'text',
                'default'     => __('Mobile Banking (Jomabee)', 'jomabee-woocommerce'),
                'desc_tip'    => true,
                'description' => __('Payment method title shown at checkout.', 'jomabee-woocommerce'),
            ],
            'description' => [
                'title'   => __('Description', 'jomabee-woocommerce'),
                'type'    => 'textarea',
                'default' => __('Pay securely with bKash, Nagad, Rocket or Upay.', 'jomabee-woocommerce'),
            ],
            'base_url' => [
                'title'       => __('Jomabee Base URL', 'jomabee-woocommerce'),
                'type'        => 'text',
                'default'     => 'https://pay.kodbee.com',
                'description' => __('Your Jomabee instance URL.', 'jomabee-woocommerce'),
            ],
            'api_key' => [
                'title' => __('API Key', 'jomabee-woocommerce'),
                'type'  => 'text',
            ],
            'secret_key' => [
                'title' => __('Secret Key', 'jomabee-woocommerce'),
                'type'  => 'password',
            ],
            'webhook_secret' => [
                'title'       => __('Webhook Secret', 'jomabee-woocommerce'),
                'type'        => 'password',
                'description' => __('Used to verify webhook signatures. Leave blank to use the Secret Key.', 'jomabee-woocommerce'),
            ],
        ];
    }

    private function api(): Jomabee_Api
    {
        return new Jomabee_Api(
            (string) $this->get_option('base_url'),
            (string) $this->get_option('api_key'),
            (string) $this->get_option('secret_key')
        );
    }

    private function webhook_secret(): string
    {
        $secret = (string) $this->get_option('webhook_secret');

        return $secret !== '' ? $secret : (string) $this->get_option('secret_key');
    }

    /**
     * @return array<string,mixed>
     */
    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);

        try {
            $data = $this->api()->create_payment([
                'amount'         => (float) $order->get_total(),
                'product_name'   => sprintf(/* translators: %s order number */ __('Order #%s', 'jomabee-woocommerce'), $order->get_order_number()),
                'customer_name'  => trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name()),
                'customer_email' => $order->get_billing_email(),
                'redirect_url'   => $this->get_return_url($order),
                'callback_url'   => WC()->api_request_url('jomabee'),
            ]);
        } catch (Exception $e) {
            wc_add_notice(__('Payment error: ', 'jomabee-woocommerce') . $e->getMessage(), 'error');

            return ['result' => 'failure'];
        }

        if (empty($data['invoice_id']) || empty($data['payment_url'])) {
            wc_add_notice(__('Could not start the Jomabee payment.', 'jomabee-woocommerce'), 'error');

            return ['result' => 'failure'];
        }

        $order->update_meta_data('_jomabee_invoice_id', sanitize_text_field((string) $data['invoice_id']));
        $order->update_status('pending', __('Awaiting Jomabee payment.', 'jomabee-woocommerce'));
        $order->save();

        return [
            'result'   => 'success',
            'redirect' => esc_url_raw((string) $data['payment_url']),
        ];
    }

    /**
     * Verify the webhook signature and mark the matching order paid.
     */
    public function handle_webhook(): void
    {
        $raw       = file_get_contents('php://input') ?: '';
        $signature = isset($_SERVER['HTTP_X_JOMABEE_SIGNATURE']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_JOMABEE_SIGNATURE'])) : '';

        $api = new Jomabee_Api(
            (string) $this->get_option('base_url'),
            (string) $this->get_option('api_key'),
            $this->webhook_secret()
        );

        if (! $api->verify_signature($raw, $signature)) {
            status_header(400);
            exit('invalid signature');
        }

        $event = json_decode($raw, true);
        if (! is_array($event) || ($event['event'] ?? '') !== 'payment.verified') {
            status_header(200);
            exit('ignored');
        }

        $invoice_id = (string) ($event['invoice_id'] ?? '');
        $orders = wc_get_orders([
            'limit'      => 1,
            'meta_key'   => '_jomabee_invoice_id',
            'meta_value' => $invoice_id,
        ]);

        if (empty($orders)) {
            status_header(404);
            exit('order not found');
        }

        $order = $orders[0];
        if (! $order->is_paid()) {
            $order->payment_complete((string) ($event['trx_id'] ?? ''));
            $order->add_order_note(sprintf(
                /* translators: 1 gateway, 2 trx id */
                __('Jomabee payment verified (%1$s, TrxID %2$s).', 'jomabee-woocommerce'),
                (string) ($event['gateway'] ?? '-'),
                (string) ($event['trx_id'] ?? '-')
            ));
        }

        status_header(200);
        exit('ok');
    }
}
