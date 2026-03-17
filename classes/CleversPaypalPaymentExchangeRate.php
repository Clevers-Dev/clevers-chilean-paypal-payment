<?php

/**
 * Fetches the CLP per USD exchange rate from open.er-api.com.
 */
class CleversPaypalPaymentExchangeRate {

    /**
     * @var string
     */
    private $url = 'https://open.er-api.com/v6/latest/USD';

    /**
     * @var object|false|null
     */
    private $exchangeRates;

    private function maybeLoadExchangeRates() {
        if (null !== $this->exchangeRates) {
            return;
        }

        $response = wp_remote_get($this->url, array('timeout' => 10));

        if (is_wp_error($response)) {
            $this->exchangeRates = false;
            return;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if (200 !== (int) $response_code) {
            $this->exchangeRates = false;
            return;
        }

        $json = wp_remote_retrieve_body($response);

        if (empty($json)) {
            $this->exchangeRates = false;
            return;
        }

        $this->exchangeRates = json_decode($json);
    }

    public function clpToUsd($clp) {
        $convert_rate = $this->getUsdToClpRate();

        if (empty($convert_rate)) {
            return null;
        }

        return round($clp / $convert_rate, 2);
    }

    public function usdToClp($usd) {
        $convert_rate = $this->getUsdToClpRate();

        if (empty($convert_rate)) {
            return null;
        }

        return round($usd * $convert_rate, 2);
    }

    public function getUsdToClpRate() {
        $this->maybeLoadExchangeRates();

        if (empty($this->exchangeRates) || !is_object($this->exchangeRates)) {
            return null;
        }

        if (!isset($this->exchangeRates->result) || 'success' !== $this->exchangeRates->result) {
            return null;
        }

        if (!isset($this->exchangeRates->rates) || !isset($this->exchangeRates->rates->CLP)) {
            return null;
        }

        return (float) $this->exchangeRates->rates->CLP;
    }
}
