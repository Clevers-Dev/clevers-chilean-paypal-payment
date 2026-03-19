<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if (!defined('ABSPATH')) {
    exit;
}

class CleversChileanPaypalPaymentBlocksSupport extends AbstractPaymentMethodType {

    protected $name = 'clevers_chilean_paypal';

    public function initialize() {
        $this->settings = get_option('woocommerce_clevers_chilean_paypal_settings', array());
    }

    public function is_active() {
        return filter_var($this->get_setting('enabled', false), FILTER_VALIDATE_BOOLEAN) && !empty($this->get_setting('paypal_email', ''));
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'clevers-chilean-paypal-blocks',
            plugins_url('assets/js/blocks.js', CLEVERS_CHILEAN_PAYPAL_PAYMENT_PLUGIN_FILE),
            array('wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-html-entities'),
            CLEVERS_CHILEAN_PAYPAL_PAYMENT_VERSION,
            true
        );

        return array('clevers-chilean-paypal-blocks');
    }

    public function get_payment_method_script_handles_for_admin() {
        return $this->get_payment_method_script_handles();
    }

    public function get_payment_method_data() {
        return array(
            'title' => $this->get_setting('title', __('Pay with PayPal', 'clevers-chilean-paypal-payment')),
            'description' => $this->get_setting('description', __('Pay securely with PayPal. CLP totals are converted to USD before redirecting you to PayPal.', 'clevers-chilean-paypal-payment')),
            'supports' => array('products'),
            'isAvailable' => $this->is_active(),
            'storeCurrency' => get_woocommerce_currency(),
        );
    }
}
