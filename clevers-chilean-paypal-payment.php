<?php
/**
 * Plugin Name: Clevers Chilean PayPal Payment
 * Description: Adds a standalone WooCommerce PayPal gateway for stores that sell in CLP and send PayPal payments in USD.
 * Author: Clevers
 * Version: 1.0.3
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

define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_VERSION', '1.0.3');
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_PLUGIN_FILE', __FILE__);
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_OPTIONS_KEY', 'clevers_chilean_paypal_payment_options');
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_LEGACY_OPTIONS_KEYS', array(
    'clevers_chilean_peso_options',
    'ctala_options_pesos',
));
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TRANSIENT_KEY', 'clevers_chilean_paypal_payment_usd_clp_rate');
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_DEFAULT_USD_CLP_RATE', 900);
define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TTL', DAY_IN_SECONDS);

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
    $current_gateway_settings = get_option('woocommerce_clevers_chilean_paypal_settings', array());

    if (empty($current_options)) {
        $legacy_options = clevers_chilean_paypal_payment_get_legacy_options();

        if (!empty($legacy_options)) {
            update_option(CLEVERS_CHILEAN_PAYPAL_PAYMENT_OPTIONS_KEY, $legacy_options);
            $current_options = $legacy_options;
        }
    }

    if (empty($current_gateway_settings) && !empty($current_options) && is_array($current_options)) {
        update_option(
            'woocommerce_clevers_chilean_paypal_settings',
            array(
                'enabled' => 'no',
                'title' => __('Pay with PayPal', 'clevers-chilean-paypal-payment'),
                'description' => __('Pay securely with PayPal. CLP totals are converted to USD before redirecting you to PayPal.', 'clevers-chilean-paypal-payment'),
                'paypal_email' => isset($current_options['paypal_email']) ? sanitize_email($current_options['paypal_email']) : '',
                'sandbox' => 'no',
                'exchange_rate_api_key' => '',
                'use_fixed_rate' => !empty($current_options['id_check_usarfijodolar']) ? 'yes' : 'no',
                'fixed_rate' => !empty($current_options['id_fijo_dolar']) ? (string) $current_options['id_fijo_dolar'] : (string) CLEVERS_CHILEAN_PAYPAL_PAYMENT_DEFAULT_USD_CLP_RATE,
            )
        );
    }

    delete_transient(CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TRANSIENT_KEY);
}

register_activation_hook(__FILE__, 'clevers_chilean_paypal_payment_activate');

function clevers_chilean_paypal_payment_deactivate() {
    delete_transient(CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TRANSIENT_KEY);
}

register_deactivation_hook(__FILE__, 'clevers_chilean_paypal_payment_deactivate');

add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

function clevers_chilean_paypal_payment_get_options() {
    $defaults = array(
        'id_fijo_dolar' => CLEVERS_CHILEAN_PAYPAL_PAYMENT_DEFAULT_USD_CLP_RATE,
        'id_check_usarfijodolar' => '',
        'paypal_email' => '',
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

function clevers_chilean_paypal_payment_get_gateway_settings() {
    $defaults = array(
        'enabled' => 'no',
        'title' => __('Pay with PayPal', 'clevers-chilean-paypal-payment'),
        'description' => __('Pay securely with PayPal. CLP totals are converted to USD before redirecting you to PayPal.', 'clevers-chilean-paypal-payment'),
        'paypal_email' => '',
        'sandbox' => 'no',
        'exchange_rate_api_key' => '',
        'use_fixed_rate' => 'no',
        'fixed_rate' => CLEVERS_CHILEAN_PAYPAL_PAYMENT_DEFAULT_USD_CLP_RATE,
    );

    $settings = get_option('woocommerce_clevers_chilean_paypal_settings', array());

    if (!is_array($settings)) {
        $settings = array();
    }

    $legacy_options = clevers_chilean_paypal_payment_get_options();

    if (empty($settings['paypal_email']) && !empty($legacy_options['paypal_email'])) {
        $settings['paypal_email'] = $legacy_options['paypal_email'];
    }

    if (empty($settings['fixed_rate']) && !empty($legacy_options['id_fijo_dolar'])) {
        $settings['fixed_rate'] = $legacy_options['id_fijo_dolar'];
    }

    if (empty($settings['use_fixed_rate']) && !empty($legacy_options['id_check_usarfijodolar'])) {
        $settings['use_fixed_rate'] = 'on' === $legacy_options['id_check_usarfijodolar'] ? 'yes' : 'no';
    }

    return array_merge($defaults, $settings);
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

function clevers_chilean_paypal_payment_get_usd_rate() {
    $options = clevers_chilean_paypal_payment_get_gateway_settings();

    if (isset($options['use_fixed_rate']) && 'yes' === $options['use_fixed_rate']) {
        return !empty($options['fixed_rate']) ? (float) $options['fixed_rate'] : CLEVERS_CHILEAN_PAYPAL_PAYMENT_DEFAULT_USD_CLP_RATE;
    }

    $cached_rate = get_transient(CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TRANSIENT_KEY);
    if (false !== $cached_rate && is_numeric($cached_rate)) {
        return (float) $cached_rate;
    }

    $exchange_rate = new CleversPaypalPaymentExchangeRate(
        !empty($options['exchange_rate_api_key']) ? sanitize_text_field($options['exchange_rate_api_key']) : ''
    );
    $usd_rate = $exchange_rate->getUsdToClpRate();

    if (empty($usd_rate)) {
        $usd_rate = CLEVERS_CHILEAN_PAYPAL_PAYMENT_DEFAULT_USD_CLP_RATE;
    }

    set_transient(CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TRANSIENT_KEY, $usd_rate, CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TTL);

    return (float) $usd_rate;
}

function clevers_chilean_paypal_payment_convert_amount_to_usd($amount, $currency = 'CLP') {
    if ('CLP' !== $currency) {
        return round((float) $amount, 2);
    }

    $convert_rate = clevers_chilean_paypal_payment_get_usd_rate();
    if ($convert_rate <= 0) {
        return null;
    }

    return round(((float) $amount) / $convert_rate, 2);
}

function clevers_chilean_paypal_payment_postcode_optional($address_fields) {
    $address_fields['postcode']['required'] = false;

    return $address_fields;
}

add_filter('woocommerce_default_address_fields', 'clevers_chilean_paypal_payment_postcode_optional');
add_filter('woocommerce_currencies', 'clevers_chilean_paypal_payment_add_currency', 10, 1);
add_filter('woocommerce_currency_symbol', 'clevers_chilean_paypal_payment_add_currency_symbol', 10, 2);

add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce') || !class_exists('WC_Payment_Gateway')) {
        return;
    }

    require_once CLEVERS_CHILEAN_PAYPAL_PAYMENT_PLUGIN_DIR . 'classes/CleversChileanPaypalPaymentGateway.php';
});

add_filter('woocommerce_payment_gateways', function ($gateways) {
    if (class_exists('CleversChileanPaypalPaymentGateway')) {
        $gateways[] = 'CleversChileanPaypalPaymentGateway';
    }

    return $gateways;
});

add_action('woocommerce_blocks_payment_method_type_registration', function ($payment_method_registry) {
    if (!class_exists('\Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once CLEVERS_CHILEAN_PAYPAL_PAYMENT_PLUGIN_DIR . 'classes/CleversChileanPaypalPaymentBlocksSupport.php';

    if (class_exists('CleversChileanPaypalPaymentBlocksSupport')) {
        $payment_method_registry->register(new CleversChileanPaypalPaymentBlocksSupport());
    }
});

add_action('admin_notices', function () {
    if (!current_user_can('activate_plugins') || clevers_chilean_paypal_payment_is_woocommerce_active()) {
        return;
    }

    echo '<div class="notice notice-warning"><p>' . esc_html__('Clevers Chilean PayPal Payment requires WooCommerce to be installed and active.', 'clevers-chilean-paypal-payment') . '</p></div>';
});
