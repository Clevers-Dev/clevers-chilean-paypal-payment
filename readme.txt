=== Clevers Chilean PayPal Payment ===

Contributors: cleversdev
Tags: woocommerce, paypal, clp, usd, chile
Requires at least: 6.0
Requires Plugins: woocommerce
Requires PHP: 7.4
Tested up to: 6.9
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Short Description: Lets WooCommerce stores charge in CLP while sending PayPal Standard payments in USD.

== Description ==

Clevers Chilean PayPal Payment is built for WooCommerce stores that sell in CLP but need PayPal Standard payments to be sent in USD.

It adds CLP support to WooCommerce and converts PayPal Standard checkout amounts from CLP to USD.

The conversion uses https://open.er-api.com/v6/latest/USD and does not require an API key.

The plugin also lets you set a manual USD -> CLP exchange rate from the admin panel.

Settings are available under `Settings > Clevers Chilean PayPal Payment`.

= Features =

* Adds `CLP` as a WooCommerce currency.
* Converts PayPal Standard payments from `CLP` to `USD`.
* Uses a remote exchange rate with a configurable manual fallback.
* Makes the postcode field optional for local checkout flows.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/` or install the release ZIP.
2. Activate it from the WordPress Plugins screen.
3. Make sure WooCommerce and PayPal Standard are configured on the site.
4. Go to `Settings > Clevers Chilean PayPal Payment`.
5. Choose whether to use the automatic rate or a fixed manual value.

== Frequently Asked Questions ==

= What happens if the exchange-rate API fails? =

The plugin falls back to a default rate and also lets you define a manual USD -> CLP value.

= Which PayPal integration does this affect? =

The current filter targets `woocommerce_paypal_args`, which is used by PayPal Standard.

== Changelog ==

= 1.0.0 =

* Full Clevers rebrand.
* New main plugin file: `clevers-chilean-paypal-payment.php`.
* PayPal Standard CLP -> USD conversion using open.er-api.com.
* Removed Chilean region functionality.
* Added CI, release ZIP, and deploy workflows.
