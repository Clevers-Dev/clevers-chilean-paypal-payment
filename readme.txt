=== Clevers Chilean PayPal Payment ===

Contributors: cleversdev
Tags: woocommerce, paypal, clp, usd, chile
Requires at least: 6.0
Requires Plugins: woocommerce
Requires PHP: 7.4
Tested up to: 6.9
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Short Description: Adds a standalone WooCommerce PayPal gateway for stores that charge in CLP and send PayPal payments in USD.

== Description ==

Clevers Chilean PayPal Payment is built for WooCommerce stores that sell in CLP but need a standalone PayPal payment method that sends payments in USD.

It adds CLP support to WooCommerce, registers its own PayPal gateway, and converts checkout amounts from CLP to USD before redirecting the customer to PayPal.

The conversion uses https://open.er-api.com/v6/latest/USD by default and can optionally use https://www.exchangerate-api.com/ when you provide an API key.

The plugin also lets you configure the receiver PayPal email, sandbox mode, an optional ExchangeRate-API key, and a manual USD -> CLP exchange rate from the WooCommerce payment settings.

Settings are available under `WooCommerce > Settings > Payments > Clevers Chilean PayPal`.

= Features =

* Adds `CLP` as a WooCommerce currency.
* Adds a standalone PayPal payment method to WooCommerce.
* Converts PayPal payments from `CLP` to `USD`.
* Uses a remote exchange rate with optional ExchangeRate-API support and a configurable manual fallback.
* Makes the postcode field optional for local checkout flows.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/` or install the release ZIP.
2. Activate it from the WordPress Plugins screen.
3. Go to `WooCommerce > Settings > Payments`.
4. Enable `Clevers Chilean PayPal`.
5. Enter the PayPal receiver email and choose whether to use the automatic rate or a fixed manual value.

== Frequently Asked Questions ==

= What happens if the exchange-rate API fails? =

The plugin falls back to a default rate and also lets you define a manual USD -> CLP value.

= Can I use my own exchange-rate API key? =

Yes. In the payment gateway settings you can enter an ExchangeRate-API key. When present, the plugin will use `v6.exchangerate-api.com` first and fall back to `open.er-api.com` if that request fails.

= Does this depend on another PayPal plugin? =

No. The plugin registers its own WooCommerce payment gateway and does not depend on the built-in or third-party PayPal gateways.

== Changelog ==

= 1.0.0 =

* Full Clevers rebrand.
* New main plugin file: `clevers-chilean-paypal-payment.php`.
* Standalone WooCommerce PayPal gateway with CLP -> USD conversion using open.er-api.com.
* Removed Chilean region functionality.
* Added CI, release ZIP, and deploy workflows.
