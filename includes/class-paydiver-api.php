<?php
/**
 * Minimal, self-contained Paydiver API client (no Composer dependency).
 *
 * @package Paydiver\WooCommerce
 */

if (! defined('ABSPATH')) {
    exit;
}

class Paydiver_Api
{
    public function __construct(
        private string $base_url,
        private string $api_key,
        private string $secret_key
    ) {
        $this->base_url = rtrim($base_url, '/');
    }

    /**
     * Create a payment invoice.
     *
     * @param array<string,mixed> $params
     * @return array<string,mixed>
     * @throws Exception On API/transport error.
     */
    public function create_payment(array $params): array
    {
        $response = wp_remote_post($this->base_url . '/api/v1/payment/create', [
            'timeout' => 30,
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
                'X-API-Key'    => $this->api_key,
                'X-Secret-Key' => $this->secret_key,
            ],
            'body' => wp_json_encode($params),
        ]);

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = json_decode((string) wp_remote_retrieve_body($response), true);

        if (! is_array($body) || $code >= 400 || (($body['success'] ?? false) === false)) {
            $message = $body['error']['message'] ?? 'Paydiver API request failed.';
            throw new Exception((string) $message);
        }

        return $body['data'] ?? [];
    }

    /**
     * Verify a webhook signature against the raw request body.
     */
    public function verify_signature(string $raw_body, string $signature): bool
    {
        if ($signature === '' || $this->secret_key === '') {
            return false;
        }

        $expected = hash_hmac('sha256', $raw_body, $this->secret_key);

        return hash_equals($expected, $signature);
    }
}
