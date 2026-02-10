<?php

require_once(__DIR__ . '/class-plisio-client.php');
require_once(__DIR__ . '/class-wc-gateway-plisio-order.php');

/**
 * WC_Gateway_Plisio class
 *
 * @author   plisio
 * @package  WooCommerce Plisio Payments Gateway
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plisio Gateway.
 *
 * @class    WC_Gateway_Plisio
 * @version  2.0.5
 */
class WC_Gateway_Plisio extends WC_Payment_Gateway {

    public $api_key;
    public $order_statuses;
    private $order;

    /** @var Plisio_Client */
    private $plisio;

    /** @var bool|null */
    private $white_label_cache = null;

	/**
	 * Constructor for the gateway.
	 */
	public function __construct() {
        global $woocommerce;

		$this->id                 = 'plisio';
		$this->icon               = apply_filters( 'woocommerce_plisio_icon', PLISIO_PLUGIN_URL . 'assets/plisio.png' );
		$this->has_fields         = false;

		$this->method_title       = _x( 'Plisio', 'Plisio payment method', 'woocommerce-gateway-plisio' );
		$this->method_description = __( 'Allows plisio payments.', 'woocommerce-gateway-plisio' );

		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		// Define user set variables.
		$this->title                    = $this->get_option( 'title' );
		$this->description              = $this->get_option( 'description' );
		$this->instructions             = $this->get_option( 'instructions', $this->description );

        $this->api_key = $this->get_option('api_key');
        $this->order_statuses = $this->get_option('order_statuses');
        $this->plisio = new Plisio_Client($this->api_key);

		// Actions.
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
        add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'save_order_statuses' ) );
        add_action('woocommerce_thankyou_plisio', array($this, 'thankyou'));
        add_action('woocommerce_api_wc_gateway_plisio', array($this, 'payment_callback'));
        if (is_object($woocommerce) && version_compare($woocommerce->version, '3.7.0', '>=')) {
            add_action('woocommerce_before_thankyou', array(
                $this, 'qrcode_section'
            ));
        } else {
            add_filter('do_shortcode_tag', array(
                $this, 'prepend_woocommerce_checkout_shortcode'
            ), 10, 4);
        }

        $this->order = new WC_Gateway_Plisio_Order();
	}

	/**
	 * Initialise Gateway Settings Form Fields.
	 */
	public function init_form_fields() {

		$this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable Plisio', 'woocommerce'),
                'label' => __('Enable cryptocurrency payments via Plisio', 'woocommerce'),
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
            ),
            'description' => array(
                'title' => __('Description', 'woocommerce'),
                'type' => 'textarea',
                'description' => __('The payment method description which a user sees at the checkout of your store.', 'woocommerce'),
                'default' => __('Pay with cryptocurrencies'),
            ),
            'title' => array(
                'title' => __('Title', 'woocommerce'),
                'type' => 'text',
                'description' => __('The payment method title which a customer sees at the checkout of your store.', 'woocommerce'),
                'default' => __('Cryptocurrencies via Plisio', 'woocommerce'),
            ),
            'api_key' => array(
                'title' => __('API Secret key', 'woocommerce'),
                'type' => 'text',
                'description' => __('Plisio API Secret key', 'woocommerce'),
                'default' => (empty($this->get_option('api_key')) ? '' : $this->get_option('api_key')),
            ),
            'order_statuses' => array(
                'type' => 'order_statuses'
            ),
		);
	}

	/**
	 * Process the payment and return the result.
	 *
	 * @param  int  $order_id
	 * @return array
	 */
	public function process_payment( $order_id ) {
        $order = new WC_Order($order_id);
        $shop = $this->plisio->getShopInfo();

        $amount = $order->get_total();

        $data = array(
            'order_number' => $order->get_id(),
            'order_name' => get_bloginfo('name', 'raw') . ' Order #' . $order->get_id(),
            'source_amount' => number_format($amount, 8, '.', ''),
            'source_currency' => get_woocommerce_currency(),
            'cancel_url' => $order->get_cancel_order_url(),
            'callback_url' => trailingslashit(get_bloginfo('wpurl')) . '?wc-api=wc_gateway_plisio',
            'success_url' => add_query_arg('order-received', $order->get_id(), add_query_arg('key', $order->get_order_key(), $this->get_return_url($order))),
            'email' => $order->get_billing_email(),
            'plugin' => 'woocommerce',
            'version' => PLISIO_WOOCOMMERCE_VERSION
        );
        $response = $this->plisio->createTransaction($data);

        if ($response && $response['status'] !== 'error' && !empty($response['data'])) {
            update_post_meta($order_id, 'plisio_order_token', $response['data']['txn_id']);
            $this->order->add(array_merge($response['data'], [
                'order_id' => $order_id,
                'plisio_invoice_id' => $response['data']['txn_id']
            ]));

            if (isset($shop['data']['white_label']) && $shop['data']['white_label']){
                $redirect = $this->get_return_url($order);
            } else {
                $redirect = $response['data']['invoice_url'];
            }

            return array(
                'result' => 'success',
                'redirect' => $redirect
            );
        }

        $message = __( 'Error occurred while processing the payment:  ' . implode(',', json_decode($response['data']['message'], true)), 'woocommerce-gateway-plisio' );
        throw new Exception( $message );
	}

    public function prepend_woocommerce_checkout_shortcode($output, $tag)
    {
        global $wp;

        if ($tag != 'woocommerce_checkout') {
            return $output;
        }

        $order_id = $wp->query_vars['order-received'];

        if (!$order_id) {
            return $output;
        }

        $prepend = $this->qrcode_section($order_id);

        $output = $prepend . $output;

        return $output;
    }

    public function qrcode_section($order_id)
    {
        global $wp;

        $wcOrder = wc_get_order($order_id);
        $shop = $this->plisio->getShopInfo();

        if (is_null($order_id)){
            $order_id = $wp->query_vars['order-received'];
        }
        $order = $this->order->get($order_id);

        if ($order) {
            $plisio_invoice_id = $order['plisio_invoice_id'];
            include_once(implode(DIRECTORY_SEPARATOR, [PLISIO_PLUGIN_PATH, 'resources', 'templates', 'invoice.php']));
        }
    }

	private function is_white_label(){
		if ($this->white_label_cache === null) {
			$shop = $this->plisio->getShopInfo();
			$this->white_label_cache = $shop['data']['white_label'];
		}
		return $this->white_label_cache;
	}

	public function get_title() {
		if ($this->is_white_label()) {
			return __('Cryptocurrencies payments', 'woocommerce-gateway-plisio');
		} else {
			return $this->title;
		}
	}

	public function get_icon(){
		if ($this->is_white_label()) {
			return '';
        } else {
			return $this->icon;
		}
    }

    public function thankyou()
    {
        if ($description = $this->get_description()) {
            echo wpautop(wptexturize($description));
        }
    }

    private function verifyCallbackData($data)
    {
        if (!isset($data['verify_hash'])) {
            return false;
        }

        $post = $data;
        $verifyHash = $post['verify_hash'];
        unset($post['verify_hash']);
        ksort($post);
        if (isset($post['expire_utc'])){
            $post['expire_utc'] = (string)$post['expire_utc'];
        }
        if (isset($post['tx_urls'])){
            $post['tx_urls'] = html_entity_decode(stripslashes($post['tx_urls']));
        }
        $postString = serialize($post);
        $checkKey = hash_hmac('sha1', $postString, $this->api_key);
        if ($checkKey != $verifyHash) {
            return false;
        }

        return true;
    }

    public function payment_callback()
    {
        if ($this->verifyCallbackData($_POST)) {
            $request = $_POST;
            $data = array_merge($_POST, [
                'order_id' => $_POST['order_number'],
                'plisio_invoice_id' => $_POST['txn_id']
            ]);
            $this->order->update($data);

            $order = new WC_Order($request['order_number']);
            if ($order->get_payment_method() == "plisio") {
                try {
                    if (!$order || !$order->get_id()) {
                        throw new Exception('Order #' . $request['order_id'] . ' does not exists');
                    }

                    $orderStatuses = $this->get_option('order_statuses');
                    $wcOrderStatus = $orderStatuses[$request['status']];

                    switch ($request['status']) {
                        case 'new':
                            WC()->mailer()->emails['WC_Email_New_Order']->trigger($order->get_id());
                            $order->update_status($wcOrderStatus);
                            break;
                        case 'pending':
                            if ($request['source_amount'] > 0) {
                                WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order->get_id());
                                $order->update_status('processing');
                            } else {
                                WC()->mailer()->emails['WC_Email_New_Order']->trigger($order->get_id());
                                $order->update_status($wcOrderStatus);
                            }
                            break;
                        case 'completed':
                        case 'mismatch':
                            $order->update_status($wcOrderStatus);
                            $order->add_order_note($request['comment']);
                            $order->payment_complete();
                            break;
                        case 'error':
                            $order->update_status($wcOrderStatus);
                            $order->add_order_note(__('Payment rejected by the network or did not confirm within 10 hours.', 'plisio'));
                            break;
                        case 'cancelled':
                        case 'expired':
                            $wcOrderStatus = $orderStatuses['cancelled'];
                            $order->update_status($wcOrderStatus);
                            break;
                    }
                } catch (Exception $e) {
                    error_log($e->getMessage());
                }
            }
        } else {
            error_log('Plisio verifyCallbackData failed');
        }
    }

    public function generate_order_statuses_html()
    {
        $plisioStatuses = $this->plisioStatuses();
        $wcStatuses = wc_get_order_statuses();
        $defaultStatuses = $this->woocommerceStatuses();
        $storedSettings = get_option('woocommerce_plisio_settings');
        $selectedStatuses = (isset($storedSettings['order_statuses'])) ? $storedSettings['order_statuses'] : [];

        include(implode(DIRECTORY_SEPARATOR, [PLISIO_PLUGIN_PATH, 'resources', 'templates', 'admin_statuses_row.php']));
    }

    public function validate_order_statuses_field()
    {
        $orderStatuses = $this->get_option('order_statuses');

        if (isset($_POST[$this->plugin_id . $this->id . '_order_statuses']))
            $orderStatuses = sanitize_text_field($_POST[$this->plugin_id . $this->id . '_order_statuses']);

        return $orderStatuses;
    }

    public function save_order_statuses()
    {
        $plisioStatuses = $this->plisioStatuses();
        $wcStatuses = wc_get_order_statuses();

        if (isset($_POST['woocommerce_plisio_order_statuses']) === true) {
            $plisioSettings = get_option('woocommerce_plisio_settings');
            $orderStatuses = isset($plisioSettings['order_statuses']) && !empty($plisioSettings['order_statuses']) ? $plisioSettings['order_statuses'] : [];

            foreach ($plisioStatuses as $status => $statusTitle) {
                if (isset($_POST['woocommerce_plisio_order_statuses'][$status]) === false)
                    continue;

                $wcStatusName = sanitize_text_field($_POST['woocommerce_plisio_order_statuses'][$status]);

                if (array_key_exists($wcStatusName, $wcStatuses) === true) {
                    $orderStatuses[$status] = $wcStatusName;
                }
            }

            $plisioSettings['order_statuses'] = $orderStatuses;
            update_option('woocommerce_plisio_settings', $plisioSettings);
        }
    }

    private function plisioStatuses()
    {
        return array(
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Paid',
            'mismatch' => 'Overpayed',
            'error' => 'Failed',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled'
        );
    }

    private function woocommerceStatuses()
    {
        return array(
            'pending' => 'wc-pending',
            'completed' => 'wc-completed',
            'processing' => 'wc-processing',
            'mismatch' => 'wc-completed',
            'error' => 'wc-failed',
            'expired' => 'wc-failed',
            'cancelled' => 'wc-cancelled',
        );
    }
}
