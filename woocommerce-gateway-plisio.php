<?php
/**
 * Plugin Name: WooCommerce Plisio Payments Gateway
 * Plugin URI: https://github.com/Plisio/woocommerce-plugin
 * Description: Adds the Plisio Payments gateway to your WooCommerce website.
 * Version: 2.0.6
 *
 * Author: Plisio
 * Author URI: https://plisio.net/
 *
 * Text Domain: woocommerce-gateway-plisio
 * Domain Path: /i18n/languages/
 *
 * Requires at least: 4.2
 * Tested up to: 6.5
 *
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC Plisio Payment gateway plugin class.
 *
 * @class WC_Plisio_Payments
 */
class WC_Plisio_Payments {

	/**
	 * Plugin bootstrapping.
	 */
	public static function init() {

        define('PLISIO_PLUGIN_PATH', __DIR__);
        define('PLISIO_PLUGIN_DIR_NAME', basename(PLISIO_PLUGIN_PATH));
        define('PLISIO_PLUGIN_URL', plugins_url(PLISIO_PLUGIN_DIR_NAME . '/'));
        define('PLISIO_WOOCOMMERCE_VERSION', '2.0.6');

		// Plisio Payments gateway class.
		add_action( 'plugins_loaded', array( __CLASS__, 'includes' ), 0 );

        register_activation_hook(__FILE__, array('WC_Plisio_Payments', 'plisio_install'));

		// Make the Plisio Payments gateway available to WC.
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_gateway' ) );

		// Registers WooCommerce Blocks integration.
		add_action( 'woocommerce_blocks_loaded', array( __CLASS__, 'woocommerce_gateway_plisio_woocommerce_block_support' ) );

	}

	/**
	 * Add the Plisio Payment gateway to the list of available gateways.
	 *
	 * @param array
	 */
	public static function add_gateway( $gateways ) {

		$options = get_option( 'woocommerce_plisio_settings', array() );

		if ( isset( $options['hide_for_non_admin_users'] ) ) {
			$hide_for_non_admin_users = $options['hide_for_non_admin_users'];
		} else {
			$hide_for_non_admin_users = 'no';
		}

		if ( ( 'yes' === $hide_for_non_admin_users && current_user_can( 'manage_options' ) ) || 'no' === $hide_for_non_admin_users ) {
			$gateways[] = 'WC_Gateway_Plisio';
		}
		return $gateways;
	}

	/**
	 * Plugin includes.
	 */
	public static function includes() {

		// Make the WC_Gateway_Plisio class available.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once 'includes/class-wc-gateway-plisio.php';
		}
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_abspath() {
		return trailingslashit( plugin_dir_path( __FILE__ ) );
	}

	/**
	 * Registers WooCommerce Blocks integration.
	 *
	 */
	public static function woocommerce_gateway_plisio_woocommerce_block_support() {
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			require_once 'includes/blocks/class-wc-plisio-payments-blocks.php';
			add_action(
				'woocommerce_blocks_payment_method_type_registration',
				function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
					$payment_method_registry->register( new WC_Gateway_Plisio_Blocks_Support() );
				}
			);
		}
	}

    public static function plisio_install()
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
            PRIMARY KEY (`id`)
          ) ENGINE=MyISAM DEFAULT COLLATE=utf8_general_ci";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        $res = dbDelta($sql);
    }
}

WC_Plisio_Payments::init();
