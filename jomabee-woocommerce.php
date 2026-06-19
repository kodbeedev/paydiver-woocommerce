<?php
/**
 * Plugin Name: Jomabee for WooCommerce
 * Plugin URI:  https://github.com/kodbeedev/jomabee-woocommerce
 * Description: Accept payments through the Jomabee payment gateway (bKash, Nagad, Rocket, Upay) by Kodbee.
 * Version:     1.0.0
 * Author:      Kodbee
 * Author URI:  https://kodbee.com
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jomabee-woocommerce
 * Requires Plugins: woocommerce
 *
 * @package Jomabee\WooCommerce
 */

if (! defined('ABSPATH')) {
    exit; // No direct access.
}

define('JOMABEE_WC_VERSION', '1.0.0');
define('JOMABEE_WC_FILE', __FILE__);

add_action('plugins_loaded', static function (): void {
    if (! class_exists('WC_Payment_Gateway')) {
        add_action('admin_notices', static function (): void {
            echo '<div class="notice notice-error"><p>'
                . esc_html__('Jomabee for WooCommerce requires WooCommerce to be installed and active.', 'jomabee-woocommerce')
                . '</p></div>';
        });

        return;
    }

    require_once __DIR__ . '/includes/class-jomabee-api.php';
    require_once __DIR__ . '/includes/class-wc-gateway-jomabee.php';

    add_filter('woocommerce_payment_gateways', static function (array $gateways): array {
        $gateways[] = 'WC_Gateway_Jomabee';

        return $gateways;
    });
});

// Settings shortcut on the plugins screen.
add_filter('plugin_action_links_' . plugin_basename(__FILE__), static function (array $links): array {
    $url = admin_url('admin.php?page=wc-settings&tab=checkout&section=jomabee');
    array_unshift($links, '<a href="' . esc_url($url) . '">' . esc_html__('Settings', 'jomabee-woocommerce') . '</a>');

    return $links;
});
