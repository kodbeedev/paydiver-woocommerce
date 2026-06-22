=== Paydiver for WooCommerce ===
Contributors: kodbee
Tags: woocommerce, payment, bangladesh, bkash, nagad
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept bKash, Nagad, Rocket and Upay payments in WooCommerce through the Paydiver gateway by Kodbee.

== Description ==

Paydiver for WooCommerce adds a redirect-based payment method that lets customers
pay with bKash, Nagad, Rocket or Upay. Orders are confirmed automatically through
a signed (HMAC-SHA256) server-to-server webhook, so payments are verified securely
without manual checking.

* Hosted Paydiver payment page (no card data touches your site)
* Automatic order completion via verified webhook
* Works with any Paydiver instance (set your own Base URL)

== Installation ==

1. Upload the plugin to `/wp-content/plugins/paydiver-woocommerce` and activate it.
2. Go to WooCommerce → Settings → Payments → Paydiver.
3. Enter your Base URL, API Key, Secret Key and Webhook Secret.
4. Enable the gateway and save.

== Frequently Asked Questions ==

= Where do I get the API keys? =
From your Paydiver dashboard under Settings → API Keys.

= How are payments confirmed? =
Paydiver sends a signed webhook to your store; the plugin verifies the signature
and marks the order paid automatically.

== Changelog ==

= 1.0.0 =
* Initial release.
