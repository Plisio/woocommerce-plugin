<?php
require_once(__DIR__ . '/PlisioClient.php');

class WC_Plisio_Gateway_Ajax
{
    protected $order;
    protected $plisio;
    protected $api_key;

    public function __construct()
    {
        $this->order = new WC_Plisio_Gateway_Order();
        $this->init_hooks();
        $this->api_key = get_option('woocommerce_plisio_settings')['api_key'];
	    $this->plisio = new PlisioClient($this->api_key);
    }


    public function init_hooks()
    {
        add_action('wp_ajax_plisio_update_invoice', array($this, 'plisio_update_invoice'));
        add_action('wp_ajax_nopriv_plisio_update_invoice', array($this, 'plisio_update_invoice'));
        add_action('wp_ajax_choose_currency', array($this, 'plisio_choose_currency'));
		add_action('wp_ajax_nopriv_choose_currency', array($this, 'plisio_choose_currency'));
    }

    public function plisio_update_invoice()
    {
        $order_id = $_POST['order_id'];
        $order = $this->order->get($order_id);
        if ($order){
            $order['expire_utc'] = (new DateTime($order['expire_utc']))->getTimestamp() * 1000;
            if (!empty($order['tx_urls'])) {
                $order['tx_urls'] = stripslashes($order['tx_urls']);
                try {
                    $txUrl = json_decode($order['tx_urls']);
                    if (!empty($txUrl)) {
                        $txUrl = gettype($txUrl) === 'string' ? $txUrl : $txUrl[count($txUrl) - 1];
                        $order['txUrl'] = $txUrl;
                    }
                } catch (Exception $e) {
                }
            }
            wp_send_json_success($order);
        } else {
            wp_send_json_error();
        }
    }

    public function plisio_choose_currency()
    {
	    $order_id     = $_POST['order_id'];
	    $plisio_order = $this->order->get( $order_id );
	    $order_info   = wc_get_order( $order_id );
	    if ($plisio_order['currency'] != $_POST['currency']) {

		    $request = array(
			    'invoice'           => $_POST['invoice'],
			    'source_amount'     => floatval( $order_info->get_total() ),
			    'source_currency'   => $plisio_order['source_currency'],
			    'currency'          => $_POST['currency'],
			    'allowed_psys_cids' => $_POST['currency'],
			    'order_number'      => $order_id,
			    'order_name'        => get_bloginfo( 'name', 'raw' ) . ' Order #' . $order_id,
			    'cancel_url'        => $order_info->get_cancel_order_url(),
			    'callback_url'      => trailingslashit( get_bloginfo( 'wpurl' ) ) . '?wc-api=wc_plisio_gateway',
			    'success_url'       => $_POST['return_url'],
			    'email'             => $order_info->get_billing_email(),
			    'language'          => get_locale(),
			    'plugin'            => 'woocommerce',
			    'version'           => PLISIO_WOOCOMMERCE_VERSION
		    );

		    $response = $this->plisio->createTransaction( $request );
		    if ( $response && $response['status'] !== 'error' && ! empty( $response['data'] ) ) {
			    if ( isset( $response['data']['wallet_hash'] ) ) {
				    if ( $this->verifyCallbackData( $response['data'] ) ) {
					    $response['data']['expire_utc'] = date( 'Y-m-d H:i:s', $response['data']['expire_utc'] );
					    $orderData                      = array_merge( [
						    'order_id'          => $plisio_order['order_id'],
						    'plisio_invoice_id' => $_POST['invoice']
					    ], $response['data'] );
					    if ($this->order->setNewCurrency($orderData)) {
						    wp_send_json_success( $_POST['return_url'] );
					    } else {
					    	wp_send_json_error($response);
					    }
				    }
			    } else {
				    error_log( 'Plisio response looks suspicious. Skip adding order' );
			    }
		    } else {
		    	error_log(print_r($response, 1));
		    }
	    } else {
	    	$orderData = [
			    'order_id'          => $plisio_order['order_id'],
			    'plisio_invoice_id' => $_POST['invoice']
		    ];
		    $this->order->setInvoiceCurrencyStatus($orderData);
		    wp_send_json_success( $_POST['return_url'] );
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
}

new WC_Plisio_Gateway_Ajax();
