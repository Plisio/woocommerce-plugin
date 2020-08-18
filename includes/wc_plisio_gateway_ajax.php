<?php

class WC_Plisio_Gateway_Ajax
{

    protected $order;

    public function __construct()
    {
        $this->order = new WC_Plisio_Gateway_Order();
        $this->init_hooks();
    }


    public function init_hooks()
    {
        add_action('wp_ajax_plisio_update_invoice', array($this, 'plisio_update_invoice'));
        add_action('wp_ajax_nopriv_plisio_update_invoice', array($this, 'plisio_update_invoice'));
    }

    public function plisio_update_invoice()
    {
        $order_id = $_POST['order_id'];
//            check_ajax_referer('wc-update_invoice', 'security');
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
}

new WC_Plisio_Gateway_Ajax();