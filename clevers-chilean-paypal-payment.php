<?php
/**
 * Plugin Name: Clevers Chilean PayPal Payment
 * Description: Lets WooCommerce stores sell in CLP while sending PayPal Standard payments in USD.
 * Author: Clevers
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires Plugins: woocommerce
 * Requires PHP: 7.4
 * Tested up to: 6.9
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: clevers-chilean-paypal-payment
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_VERSION', '1.0.0');
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_PLUGIN_FILE', __FILE__);
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_OPTIONS_KEY', 'clevers_chilean_paypal_payment_options');
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_LEGACY_OPTIONS_KEYS', array(
    'clevers_chilean_peso_options',
    'ctala_options_pesos',
));
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TRANSIENT_KEY', 'clevers_chilean_paypal_payment_usd_clp_rate');
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_DEFAULT_USD_CLP_RATE', 690);
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TTL', DAY_IN_SECONDS);

require_once CLEVERS_CHILEAN_PAYPAL_PAYMENT_PLUGIN_DIR . 'classes/CleversChileanPaypalPaymentSettingsPage.php';
require_once CLEVERS_CHILEAN_PAYPAL_PAYMENT_PLUGIN_DIR . 'classes/CleversPaypalPaymentExchangeRate.php';
require_once CLEVERS_CHILEAN_PAYPAL_PAYMENT_PLUGIN_DIR . 'helpers/rowMeta.php';

function clevers_chilean_paypal_payment_is_woocommerce_active() {
    return class_exists('WooCommerce');
}

function clevers_chilean_paypal_payment_get_legacy_options() {
    foreach (CLEVERS_CHILEAN_PAYPAL_PAYMENT_LEGACY_OPTIONS_KEYS as $legacy_key) {
        $legacy_options = get_option($legacy_key, array());
        if (is_array($legacy_options) && !empty($legacy_options)) {
            return $legacy_options;
        }
    }

    return array();
}

function clevers_chilean_paypal_payment_activate() {
    $current_options = get_option(CLEVERS_CHILEAN_PAYPAL_PAYMENT_OPTIONS_KEY, array());

    if (empty($current_options)) {
        $legacy_options = clevers_chilean_paypal_payment_get_legacy_options();

        if (!empty($legacy_options)) {
            update_option(CLEVERS_CHILEAN_PAYPAL_PAYMENT_OPTIONS_KEY, $legacy_options);
        }
    }

    delete_transient(CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TRANSIENT_KEY);
}

register_activation_hook(__FILE__, 'clevers_chilean_paypal_payment_activate');

function clevers_chilean_paypal_payment_deactivate() {
    delete_transient(CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TRANSIENT_KEY);
}

register_deactivation_hook(__FILE__, 'clevers_chilean_paypal_payment_deactivate');

function clevers_chilean_paypal_payment_get_options() {
    $defaults = array(
        'id_fijo_dolar' => CLEVERS_CHILEAN_PAYPAL_PAYMENT_DEFAULT_USD_CLP_RATE,
        'id_check_usarfijodolar' => '',
    );

    $options = get_option(CLEVERS_CHILEAN_PAYPAL_PAYMENT_OPTIONS_KEY, array());

    if (!is_array($options) || empty($options)) {
        $options = clevers_chilean_paypal_payment_get_legacy_options();
    }

    if (!is_array($options)) {
        $options = array();
    }

    return array_merge($defaults, $options);
}

function clevers_chilean_paypal_payment_add_currency($currencies) {
    $currencies['CLP'] = __('Chilean peso', 'clevers-chilean-paypal-payment');

    return $currencies;
}

function clevers_chilean_paypal_payment_add_currency_symbol($currency_symbol, $currency) {
    if ('CLP' === $currency) {
        $currency_symbol = '$';
    }

    return $currency_symbol;
}

function clevers_chilean_paypal_payment_add_paypal_currency($currencies) {
    if (!in_array('CLP', $currencies, true)) {
        $currencies[] = 'CLP';
    }

    return $currencies;
}

function clevers_chilean_paypal_payment_get_usd_rate() {
    $options = clevers_chilean_paypal_payment_get_options();

    if (isset($options['id_check_usarfijodolar']) && 'on' === $options['id_check_usarfijodolar']) {
        return !empty($options['id_fijo_dolar']) ? (float) $options['id_fijo_dolar'] : CLEVERS_CHILEAN_PAYPAL_PAYMENT_DEFAULT_USD_CLP_RATE;
    }

    $cached_rate = get_transient(CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TRANSIENT_KEY);
    if (false !== $cached_rate && is_numeric($cached_rate)) {
        return (float) $cached_rate;
    }

    $exchange_rate = new CleversPaypalPaymentExchangeRate();
    $usd_rate = $exchange_rate->getUsdToClpRate();

    if (empty($usd_rate)) {
        $usd_rate = CLEVERS_CHILEAN_PAYPAL_PAYMENT_DEFAULT_USD_CLP_RATE;
    }

    set_transient(CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TRANSIENT_KEY, $usd_rate, CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TTL);

    return (float) $usd_rate;
}

function clevers_chilean_paypal_payment_convert_paypal_args($paypal_args) {
    if (!isset($paypal_args['currency_code']) || 'CLP' !== $paypal_args['currency_code']) {
        return $paypal_args;
    }

    $convert_rate = clevers_chilean_paypal_payment_get_usd_rate();
    if ($convert_rate <= 0) {
        return $paypal_args;
    }

    $paypal_args['currency_code'] = 'USD';
    $index = 1;

    while (isset($paypal_args['amount_' . $index])) {
        $paypal_args['amount_' . $index] = round($paypal_args['amount_' . $index] / $convert_rate, 2);
        ++$index;
    }

    if (isset($paypal_args['discount_amount_cart']) && $paypal_args['discount_amount_cart'] > 0) {
        $paypal_args['discount_amount_cart'] = round($paypal_args['discount_amount_cart'] / $convert_rate, 2);
    }

    if (isset($paypal_args['tax_cart']) && $paypal_args['tax_cart'] > 0) {
        $paypal_args['tax_cart'] = round($paypal_args['tax_cart'] / $convert_rate, 2);
    }

    return $paypal_args;
}

function clevers_chilean_paypal_payment_postcode_optional($address_fields) {
    $address_fields['postcode']['required'] = false;

    return $address_fields;
}

add_filter('woocommerce_default_address_fields', 'clevers_chilean_paypal_payment_postcode_optional');
add_filter('woocommerce_paypal_args', 'clevers_chilean_paypal_payment_convert_paypal_args');
add_filter('woocommerce_currencies', 'clevers_chilean_paypal_payment_add_currency', 10, 1);
add_filter('woocommerce_currency_symbol', 'clevers_chilean_paypal_payment_add_currency_symbol', 10, 2);
add_filter('woocommerce_paypal_supported_currencies', 'clevers_chilean_paypal_payment_add_paypal_currency');

add_action('plugins_loaded', function () {
    if (is_admin()) {
        new CleversChileanPaypalPaymentSettingsPage();
    }
});

add_action('admin_notices', function () {
    if (!current_user_can('activate_plugins') || clevers_chilean_paypal_payment_is_woocommerce_active()) {
        return;
    }

    echo '<div class="notice notice-warning"><p>' . esc_html__('Clevers Chilean PayPal Payment requires WooCommerce to be installed and active.', 'clevers-chilean-paypal-payment') . '</p></div>';
});
