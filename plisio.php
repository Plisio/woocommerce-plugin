<?php
/**
 * Plugin Name: Plisio Payment Gateway for WooCommerce
 * Plugin URI: https://github.com/Plisio/woocommerce-plugin/releases/download/v1.0.7/plisio-gateway-for-woocommerce-1.0.7.zip
 * Description: Accept cryptocurrencies via Plisio in your WooCommerce store
 * Version: 1.0.7
 * Author: Plisio
 * Author URI: http://plisio.net/
 * License: MIT
 */

add_action('plugins_loaded', 'plisio_init');

function plisio_init()
{
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    };

    define('PLISIO_PLUGIN_PATH', dirname(__FILE__));
    define('PLISIO_PLUGIN_DIR_NAME', basename(PLISIO_PLUGIN_PATH));
    define('PLISIO_PLUGIN_URL', plugins_url(PLISIO_PLUGIN_DIR_NAME . '/'));
    define('PLISIO_WOOCOMMERCE_VERSION', '1.0.7');

    require_once dirname(__FILE__) . '/includes/wc_plisio_gateway.php';
    require_once dirname(__FILE__) . '/includes/wc_plisio_gateway_order.php';
    require_once dirname(__FILE__) . '/includes/wc_plisio_gateway_ajax.php';

    add_filter('woocommerce_payment_gateways', function ($methods){
        $methods[] = 'WC_Plisio_Gateway';

        return $methods;
    });
}

function plisio_install()
{
    global $wpdb;

    $transactions_table = $wpdb->prefix . 'plisio_order';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS `$transactions_table` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `order_id` INT(11) NOT NULL,
            `plisio_invoice_id` VARCHAR(40),
            `amount` VARCHAR(40) DEFAULT '',
            `pending_amount` VARCHAR(40) DEFAULT '',
            `wallet_hash` VARCHAR(120) DEFAULT '',
            `psys_cid` VARCHAR(10) DEFAULT '',
            `currency` VARCHAR(10) DEFAULT '',
            `status` VARCHAR(10) DEFAULT 'new',
            `source_currency` VARCHAR(10) DEFAULT '',
            `source_rate` VARCHAR(40) DEFAULT '',
            `expire_utc` DATETIME DEFAULT NULL,
            `qr_code` BLOB DEFAULT NULL,
            `confirmations` TINYINT(2) DEFAULT 0,
            `expected_confirmations` TINYINT(2) DEFAULT 0,
            `tx_urls` TEXT DEFAULT NULL,
    		`invoice_currency_set` BOOLEAN DEFAULT NULL,
            PRIMARY KEY (`id`)
          ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $res = dbDelta($sql);
}

register_activation_hook(__FILE__, 'plisio_install');

