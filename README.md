# Paydiver for WooCommerce

Accept **bKash, Nagad, Rocket and Upay** payments in WooCommerce through the
[Paydiver](https://kodbee.com) payment gateway by **Kodbee**.

- Redirect-based hosted checkout (no card data on your site)
- Automatic order completion via signed (HMAC-SHA256) webhook
- Works with any Paydiver instance

## Install

1. Copy this folder to `wp-content/plugins/paydiver-woocommerce` (or upload the zip).
2. Activate **Paydiver for WooCommerce** in WordPress → Plugins.
3. **WooCommerce → Settings → Payments → Paydiver** → enter:
   - Base URL (e.g. `https://pay.kodbee.com`)
   - API Key, Secret Key (dashboard → API Keys)
   - Webhook Secret (optional; defaults to Secret Key)
4. Enable and save.

## How it works

1. At checkout the plugin calls `POST /api/v1/payment/create` and redirects the
   customer to the hosted `payment_url`.
2. After payment, Paydiver calls the store webhook (`/?wc-api=paydiver`).
3. The plugin verifies the `X-Paydiver-Signature` against the raw body and calls
   `payment_complete()` on the matching order.

## License

GPL-2.0-or-later © [Kodbee](https://kodbee.com)
