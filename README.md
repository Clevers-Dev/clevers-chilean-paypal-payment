# Clevers Chilean PayPal Payment

Plugin focused on WooCommerce stores that price in CLP and need a standalone PayPal gateway that sends payments in USD.

## Purpose

This plugin is not a generic currency pack. Its real job is to provide its own WooCommerce PayPal gateway for stores that sell in Chilean pesos and need the checkout request sent in USD.

## Features

- Adds the `CLP` currency to WooCommerce when needed.
- Adds a standalone WooCommerce PayPal gateway.
- Converts checkout amounts from `CLP` to `USD`.
- Uses `https://open.er-api.com/v6/latest/USD` by default and can optionally use `https://www.exchangerate-api.com/` with an API key.
- Lets admins define the PayPal receiver email, sandbox mode, an optional ExchangeRate-API key, and a manual USD -> CLP rate.
- Makes the postcode field optional.

## Configuration

The gateway settings are available under `WooCommerce > Settings > Payments > Clevers Chilean PayPal`.

If you enable the fixed exchange-rate option, the plugin stops calling the remote API and uses the manual value instead.

## WordPress.org

The intended plugin slug and distribution folder name are `clevers-chilean-paypal-payment`.

## Development

```bash
composer install
composer lint
```

## Release

This repository includes workflows for:

- CI with Composer validation and PHP linting
- Creating releases from GitHub Actions
- Building distribution ZIP files
- Deploying to WordPress.org

## Changelog

### 1.0.0

- Full Clevers rebrand.
- New main plugin file: `clevers-chilean-paypal-payment.php`.
- Standalone WooCommerce PayPal gateway with CLP -> USD conversion via `open.er-api.com`.
- Removed Chilean region support.
- Added Clevers-style CI and release workflows.
