<?php

class CleversChileanPaypalPaymentSettingsPage {

    /**
     * @var array
     */
    private $options = array();

    public function __construct() {
        add_action('admin_menu', array($this, 'addPluginPage'));
        add_action('admin_init', array($this, 'pageInit'));
    }

    public function addPluginPage() {
        add_options_page(
            'Clevers Chilean PayPal Payment',
            'Clevers Chilean PayPal Payment',
            'manage_options',
            'clevers-chilean-paypal-payment',
            array($this, 'createAdminPage')
        );
    }

    public function createAdminPage() {
        $this->options = clevers_chilean_paypal_payment_get_options();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Clevers Chilean PayPal Payment', 'clevers-chilean-paypal-payment'); ?></h1>
            <p><?php echo esc_html__('Configure how WooCommerce orders in CLP are converted into USD for PayPal.', 'clevers-chilean-paypal-payment'); ?></p>
            <form method="post" action="options.php">
                <?php
                settings_fields('clevers_chilean_paypal_payment_option_group');
                do_settings_sections('clevers-chilean-paypal-payment');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function pageInit() {
        register_setting(
            'clevers_chilean_paypal_payment_option_group',
            CLEVERS_CHILEAN_PAYPAL_PAYMENT_OPTIONS_KEY,
            array($this, 'sanitize')
        );

        add_settings_section(
            'clevers_chilean_paypal_payment_default_values',
            'Configuration',
            array($this, 'printSectionInfo'),
            'clevers-chilean-paypal-payment'
        );

        add_settings_field(
            'id_check_usarfijodolar',
            'Use fixed exchange rate',
            array($this, 'fixedRateEnabledCallback'),
            'clevers-chilean-paypal-payment',
            'clevers_chilean_paypal_payment_default_values'
        );

        add_settings_field(
            'id_fijo_dolar',
            'Fixed USD -> CLP value',
            array($this, 'fixedRateValueCallback'),
            'clevers-chilean-paypal-payment',
            'clevers_chilean_paypal_payment_default_values'
        );
    }

    public function sanitize($input) {
        $new_input = array();

        if (isset($input['id_fijo_dolar'])) {
            $new_input['id_fijo_dolar'] = absint($input['id_fijo_dolar']);
        }

        $new_input['id_check_usarfijodolar'] = isset($input['id_check_usarfijodolar']) ? 'on' : '';

        delete_transient(CLEVERS_CHILEAN_PAYPAL_PAYMENT_RATE_TRANSIENT_KEY);

        return $new_input;
    }

    public function printSectionInfo() {
        echo esc_html__('Use a manual exchange rate if you do not want PayPal conversions to rely on the remote API.', 'clevers-chilean-paypal-payment');
    }

    public function fixedRateValueCallback() {
        printf(
            '<input type="number" min="1" step="1" id="id_fijo_dolar" name="%s[id_fijo_dolar]" value="%s" class="regular-text" />',
            esc_attr(CLEVERS_CHILEAN_PAYPAL_PAYMENT_OPTIONS_KEY),
            isset($this->options['id_fijo_dolar']) ? esc_attr($this->options['id_fijo_dolar']) : esc_attr(CLEVERS_CHILEAN_PAYPAL_PAYMENT_DEFAULT_USD_CLP_RATE)
        );
    }

    public function fixedRateEnabledCallback() {
        printf(
            '<label><input type="checkbox" id="id_check_usarfijodolar" name="%s[id_check_usarfijodolar]" %s /> Always use the configured fixed value</label>',
            esc_attr(CLEVERS_CHILEAN_PAYPAL_PAYMENT_OPTIONS_KEY),
            checked(isset($this->options['id_check_usarfijodolar']) ? $this->options['id_check_usarfijodolar'] : '', 'on', false)
        );
    }
}
