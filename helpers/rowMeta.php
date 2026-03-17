<?php
/**
 * Plugin row meta links.
 */
function clevers_chilean_paypal_payment_plugin_row_meta($plugin_meta, $plugin_file) {
    if (plugin_basename(CLEVERS_CHILEAN_PAYPAL_PAYMENT_PLUGIN_FILE) === $plugin_file) {
        $plugin_meta[] = '<a href="https://clevers.dev" target="_blank" rel="noopener noreferrer">Clevers</a>';
        $plugin_meta[] = '<a href="https://github.com/cleversdev/clevers-chilean-paypal-payment" target="_blank" rel="noopener noreferrer">GitHub</a>';
    }

    return $plugin_meta;
}

add_filter('plugin_row_meta', 'clevers_chilean_paypal_payment_plugin_row_meta', 10, 2);
