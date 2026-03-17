<?php
/**
 * Bumps the version in:
 * - clevers-chilean-paypal-payment.php (plugin header Version)
 * - clevers-chilean-paypal-payment.php (CLEVERS_CHILEAN_PAYPAL_PAYMENT_VERSION constant)
 * - readme.txt (Stable tag)
 *
 * Usage:
 *   php bin/bump-version.php 1.1.0
 */

if ('cli' === PHP_SAPI && !defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

if (!defined('ABSPATH')) {
    exit;
}

if ($argc < 2) {
    fwrite(STDERR, "Usage: php bin/bump-version.php X.Y.Z\n");
    exit(1);
}

$clevers_chilean_paypal_payment_new_version = $argv[1];

if (!preg_match('/^[0-9]+\.[0-9]+\.[0-9]+$/', $clevers_chilean_paypal_payment_new_version)) {
    fwrite(STDERR, "Invalid version: {$clevers_chilean_paypal_payment_new_version}. Use X.Y.Z format.\n");
    exit(1);
}

$clevers_chilean_paypal_payment_root = dirname(__DIR__);

function clevers_chilean_paypal_payment_bump_plugin_header_version($file, $new_version) {
    if (!file_exists($file)) {
        echo "File not found: {$file}\n";
        return;
    }

    $lines = file($file);
    $changed = 0;

    foreach ($lines as &$line) {
        if (preg_match('/^\s*\*\s*Version\b/i', $line)) {
            if (preg_match('/^(\s*\*\s*)Version\b/i', $line, $matches)) {
                $line_prefix = $matches[1];
            } else {
                $line_prefix = ' * ';
            }

            $line = $line_prefix . 'Version: ' . $new_version . "\n";
            ++$changed;
        }
    }
    unset($line);

    if ($changed > 0) {
        file_put_contents($file, implode('', $lines));
        echo "Updated plugin header version in {$file} to {$new_version}.\n";
    } else {
        echo "No 'Version' line found in {$file}.\n";
    }
}

function clevers_chilean_paypal_payment_bump_plugin_constant_version($file, $new_version) {
    if (!file_exists($file)) {
        echo "File not found: {$file}\n";
        return;
    }

    $contents = file_get_contents($file);
    if (false === $contents) {
        echo "Unable to read {$file}\n";
        return;
    }

    $updated = preg_replace(
        "/define\\('CLEVERS_CHILEAN_PAYPAL_PAYMENT_VERSION',\\s*'[^']+'\\);/",
        "define('CLEVERS_CHILEAN_PAYPAL_PAYMENT_VERSION', '{$new_version}');",
        $contents,
        1,
        $count
    );

    if (0 === $count || null === $updated) {
        echo "No CLEVERS_CHILEAN_PAYPAL_PAYMENT_VERSION constant found in {$file}.\n";
        return;
    }

    file_put_contents($file, $updated);
    echo "Updated plugin version constant in {$file} to {$new_version}.\n";
}

function clevers_chilean_paypal_payment_bump_readme_stable_tag($file, $new_version) {
    if (!file_exists($file)) {
        echo "File not found: {$file}\n";
        return;
    }

    $lines = file($file);
    $changed = 0;

    foreach ($lines as &$line) {
        if (preg_match('/^Stable tag:/i', $line)) {
            $line = "Stable tag: {$new_version}\n";
            ++$changed;
        }
    }
    unset($line);

    if ($changed > 0) {
        file_put_contents($file, implode('', $lines));
        echo "Updated readme stable tag in {$file} to {$new_version}.\n";
    } else {
        echo "No 'Stable tag:' line found in {$file}.\n";
    }
}

clevers_chilean_paypal_payment_bump_plugin_header_version($clevers_chilean_paypal_payment_root . '/clevers-chilean-paypal-payment.php', $clevers_chilean_paypal_payment_new_version);
clevers_chilean_paypal_payment_bump_plugin_constant_version($clevers_chilean_paypal_payment_root . '/clevers-chilean-paypal-payment.php', $clevers_chilean_paypal_payment_new_version);
clevers_chilean_paypal_payment_bump_readme_stable_tag($clevers_chilean_paypal_payment_root . '/readme.txt', $clevers_chilean_paypal_payment_new_version);

echo "\nDone. Review the changes with: git diff\n";
