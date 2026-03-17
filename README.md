# Clevers Chilean PayPal Payment

Plugin focused on WooCommerce stores that price in CLP but need PayPal Standard payments to be sent in USD.

## Purpose

This plugin is not a generic currency pack. Its real job is to bridge WooCommerce stores that sell in Chilean pesos with PayPal Standard, which expects the checkout request in USD.

## Features

- Adds the `CLP` currency to WooCommerce when needed.
- Converts PayPal Standard checkout amounts from `CLP` to `USD`.
- Uses `https://open.er-api.com/v6/latest/USD` to fetch the exchange rate.
- Lets admins define a manual USD -> CLP rate.
- Makes the postcode field optional.

## Configuration

The plugin settings are available under `Settings > Clevers Chilean PayPal Payment`.

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
- PayPal Standard CLP -> USD conversion via `open.er-api.com`.
- Removed Chilean region support.
- Added Clevers-style CI and release workflows.
