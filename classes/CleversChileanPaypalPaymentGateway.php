<?php

if (!defined('ABSPATH')) {
    exit;
}

class CleversChileanPaypalPaymentGateway extends WC_Payment_Gateway {

    public function __construct() {
        $this->id = 'clevers_chilean_paypal';
        $this->method_title = __('Clevers Chilean PayPal', 'clevers-chilean-paypal-payment');
        $this->method_description = __('Standalone PayPal gateway for WooCommerce stores that charge in CLP and send PayPal payments in USD.', 'clevers-chilean-paypal-payment');
        $this->has_fields = false;
        $this->supports = array('products');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title', __('Pay with PayPal', 'clevers-chilean-paypal-payment'));
        $this->description = $this->get_option('description', __('Pay securely with PayPal. CLP totals are converted to USD before redirecting you to PayPal.', 'clevers-chilean-paypal-payment'));

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_' . strtolower(__CLASS__), array($this, 'handleIpn'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'clevers-chilean-paypal-payment'),
                'type' => 'checkbox',
                'label' => __('Enable Clevers Chilean PayPal', 'clevers-chilean-paypal-payment'),
                'default' => 'no',
            ),
            'title' => array(
                'title' => __('Title', 'clevers-chilean-paypal-payment'),
                'type' => 'text',
                'description' => __('This controls the payment method title shown to customers at checkout.', 'clevers-chilean-paypal-payment'),
                'default' => __('Pay with PayPal', 'clevers-chilean-paypal-payment'),
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => __('Description', 'clevers-chilean-paypal-payment'),
                'type' => 'textarea',
                'description' => __('This controls the payment method description shown to customers at checkout.', 'clevers-chilean-paypal-payment'),
                'default' => __('Pay securely with PayPal. CLP totals are converted to USD before redirecting you to PayPal.', 'clevers-chilean-paypal-payment'),
                'desc_tip' => true,
            ),
            'paypal_email' => array(
                'title' => __('PayPal email', 'clevers-chilean-paypal-payment'),
                'type' => 'email',
                'description' => __('Primary PayPal account email that will receive the payment.', 'clevers-chilean-paypal-payment'),
                'default' => '',
                'desc_tip' => true,
            ),
            'sandbox' => array(
                'title' => __('Sandbox mode', 'clevers-chilean-paypal-payment'),
                'type' => 'checkbox',
                'label' => __('Use PayPal Sandbox endpoints', 'clevers-chilean-paypal-payment'),
                'default' => 'no',
            ),
            'exchange_rate_api_key' => array(
                'title' => __('ExchangeRate-API key', 'clevers-chilean-paypal-payment'),
                'type' => 'text',
                'description' => __('Optional. If provided, the plugin will request USD to CLP rates from exchangerate-api.com before falling back to the public endpoint.', 'clevers-chilean-paypal-payment'),
                'default' => '',
                'desc_tip' => true,
            ),
            'use_fixed_rate' => array(
                'title' => __('Use fixed exchange rate', 'clevers-chilean-paypal-payment'),
                'type' => 'checkbox',
                'label' => __('Always use the configured fixed USD to CLP value', 'clevers-chilean-paypal-payment'),
                'default' => 'no',
            ),
            'fixed_rate' => array(
                'title' => __('Fixed USD to CLP value', 'clevers-chilean-paypal-payment'),
                'type' => 'number',
                'custom_attributes' => array(
                    'min' => '1',
                    'step' => '0.01',
                ),
                'default' => CLEVERS_CHILEAN_PAYPAL_PAYMENT_DEFAULT_USD_CLP_RATE,
                'description' => __('Used only when fixed exchange rate is enabled.', 'clevers-chilean-paypal-payment'),
                'desc_tip' => true,
            ),
        );
    }

    public function validate_email_field($key, $value) {
        $email = sanitize_email($value);

        if (empty($email)) {
            WC_Admin_Settings::add_error(__('A valid PayPal email is required.', 'clevers-chilean-paypal-payment'));
        }

        return $email;
    }

    public function process_admin_options() {
        $saved = parent::process_admin_options();

        delete_transient(CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TRANSIENT_KEY);

        return $saved;
    }

    public function is_available() {
        if ('yes' !== $this->get_option('enabled', 'no')) {
            return false;
        }

        if (empty($this->get_option('paypal_email', ''))) {
            return false;
        }

        if (function_exists('get_woocommerce_currency') && 'CLP' !== get_woocommerce_currency()) {
            return false;
        }

        return parent::is_available();
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        if (!$order instanceof WC_Order) {
            wc_add_notice(__('The order could not be loaded for PayPal checkout.', 'clevers-chilean-paypal-payment'), 'error');
            return array('result' => 'failure');
        }

        $paypal_email = sanitize_email($this->get_option('paypal_email', ''));
        if (empty($paypal_email)) {
            wc_add_notice(__('PayPal is not configured yet. Contact the store administrator.', 'clevers-chilean-paypal-payment'), 'error');
            return array('result' => 'failure');
        }

        $paypal_url = $this->getPayPalRequestUrl($order);

        $order->update_status('pending', __('Customer redirected to PayPal.', 'clevers-chilean-paypal-payment'));
        WC()->cart->empty_cart();

        return array(
            'result' => 'success',
            'redirect' => $paypal_url,
        );
    }

    private function getPayPalRequestUrl(WC_Order $order) {
        return add_query_arg($this->getPayPalArgs($order), $this->getPayPalEndpoint());
    }

    private function getPayPalArgs(WC_Order $order) {
        $order_total_usd = clevers_chilean_paypal_payment_convert_amount_to_usd((float) $order->get_total(), $order->get_currency());

        if (null === $order_total_usd) {
            $order_total_usd = round((float) $order->get_total(), 2);
        }

        $billing_first_name = $order->get_billing_first_name();
        $billing_last_name = $order->get_billing_last_name();

        return array(
            'cmd' => '_xclick',
            'business' => sanitize_email($this->get_option('paypal_email', '')),
            'currency_code' => 'USD',
            'charset' => 'utf-8',
            'rm' => '2',
            'no_note' => '1',
            'bn' => 'WooThemes_Cart',
            'amount' => number_format($order_total_usd, 2, '.', ''),
            'item_name' => sprintf(__('Order %s', 'clevers-chilean-paypal-payment'), $order->get_order_number()),
            'invoice' => (string) $order->get_id(),
            'custom' => $order->get_id() . '|' . $order->get_order_key(),
            'notify_url' => WC()->api_request_url(strtolower(__CLASS__)),
            'return' => $this->get_return_url($order),
            'cancel_return' => $order->get_cancel_order_url_raw(),
            'first_name' => $billing_first_name,
            'last_name' => $billing_last_name,
            'email' => $order->get_billing_email(),
            'country' => $order->get_billing_country(),
        );
    }

    private function getPayPalEndpoint() {
        if ('yes' === $this->get_option('sandbox', 'no')) {
            return 'https://www.sandbox.paypal.com/cgi-bin/webscr';
        }

        return 'https://www.paypal.com/cgi-bin/webscr';
    }

    private function getIpnVerificationEndpoint() {
        if ('yes' === $this->get_option('sandbox', 'no')) {
            return 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr';
        }

        return 'https://ipnpb.paypal.com/cgi-bin/webscr';
    }

    public function handleIpn() {
        $posted_data = wp_unslash($_POST);

        if (empty($posted_data) || !is_array($posted_data)) {
            status_header(400);
            exit;
        }

        if (!$this->isValidIpn($posted_data)) {
            status_header(400);
            exit;
        }

        $order = $this->getOrderFromIpn($posted_data);
        if (!$order instanceof WC_Order) {
            status_header(404);
            exit;
        }

        $receiver_email = isset($posted_data['receiver_email']) ? sanitize_email($posted_data['receiver_email']) : '';
        $configured_email = sanitize_email($this->get_option('paypal_email', ''));

        if (!empty($configured_email) && !empty($receiver_email) && strtolower($configured_email) !== strtolower($receiver_email)) {
            $order->add_order_note(__('PayPal IPN ignored because the receiver email did not match the configured account.', 'clevers-chilean-paypal-payment'));
            status_header(400);
            exit;
        }

        $payment_status = isset($posted_data['payment_status']) ? sanitize_text_field($posted_data['payment_status']) : '';
        $txn_id = isset($posted_data['txn_id']) ? sanitize_text_field($posted_data['txn_id']) : '';
        $mc_currency = isset($posted_data['mc_currency']) ? sanitize_text_field($posted_data['mc_currency']) : '';
        $mc_gross = isset($posted_data['mc_gross']) ? (float) $posted_data['mc_gross'] : 0.0;

        $expected_total = clevers_chilean_paypal_payment_convert_amount_to_usd((float) $order->get_total(), $order->get_currency());
        if (null !== $expected_total && abs($expected_total - $mc_gross) > 0.01) {
            $order->add_order_note(sprintf(__('PayPal IPN amount mismatch. Expected %1$s USD, received %2$s %3$s.', 'clevers-chilean-paypal-payment'), number_format($expected_total, 2, '.', ''), number_format($mc_gross, 2, '.', ''), $mc_currency));
            status_header(400);
            exit;
        }

        if ('USD' !== strtoupper($mc_currency)) {
            $order->add_order_note(__('PayPal IPN ignored because the currency was not USD.', 'clevers-chilean-paypal-payment'));
            status_header(400);
            exit;
        }

        if (in_array($payment_status, array('Completed', 'Processed'), true)) {
            if (!$order->is_paid()) {
                $order->payment_complete($txn_id);
                $order->add_order_note(sprintf(__('PayPal payment completed. Transaction ID: %s', 'clevers-chilean-paypal-payment'), $txn_id));
            }

            status_header(200);
            exit;
        }

        if ('Pending' === $payment_status) {
            $order->update_status('on-hold', sprintf(__('PayPal payment is pending. Transaction ID: %s', 'clevers-chilean-paypal-payment'), $txn_id));
            status_header(200);
            exit;
        }

        $order->add_order_note(sprintf(__('Unhandled PayPal IPN status: %1$s. Transaction ID: %2$s', 'clevers-chilean-paypal-payment'), $payment_status, $txn_id));
        status_header(200);
        exit;
    }

    private function isValidIpn(array $posted_data) {
        $verification_body = array_merge(array('cmd' => '_notify-validate'), $posted_data);

        $response = wp_remote_post(
            $this->getIpnVerificationEndpoint(),
            array(
                'body' => $verification_body,
                'timeout' => 20,
                'httpversion' => '1.1',
                'compress' => false,
                'decompress' => false,
                'user-agent' => 'WordPress/' . get_bloginfo('version') . ' | Clevers Chilean PayPal',
            )
        );

        if (is_wp_error($response)) {
            return false;
        }

        if (200 !== (int) wp_remote_retrieve_response_code($response)) {
            return false;
        }

        return 'VERIFIED' === trim(wp_remote_retrieve_body($response));
    }

    private function getOrderFromIpn(array $posted_data) {
        $order_id = 0;

        if (!empty($posted_data['invoice'])) {
            $order_id = absint($posted_data['invoice']);
        } elseif (!empty($posted_data['custom'])) {
            $custom_parts = explode('|', sanitize_text_field($posted_data['custom']));
            $order_id = absint($custom_parts[0]);
        }

        if ($order_id <= 0) {
            return null;
        }

        return wc_get_order($order_id);
    }
}
