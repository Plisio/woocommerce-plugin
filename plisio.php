<?php
/**
 * Plugin Name: WooCommerce Plisio Payment Gateway
 * Plugin URI: https://plisio.net/download/plugins/woocommerce-plisio-1.0.0.zip
 * Description: Accept cryptocurrencies via Plisio in your WooCommerce store
 * Version: 1.0.0
 * Author: Plisio
 * Author URI: http://plisio.net/
 * License: MIT
 */

define('PLISIO_WOOCOMMERCE_VERSION', '1.0.0');

add_action('plugins_loaded', 'plisio_init');

function plisio_init(){
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    };

    define('PLISIO_PLUGIN_DIR', plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__)) . '/');

    require_once(__DIR__ . '/Plisio/PlisioClient.php');

    class WC_Plisio_Gateway extends WC_Payment_Gateway
    {
        public function __construct()
        {
            global $woocommerce;

            $this->id = 'plisio';
            $this->has_fields = false;
            $this->method_title = 'Plisio';
            $this->icon = apply_filters('woocommerce_plisio_icon', PLISIO_PLUGIN_DIR . 'assets/plisio.png');

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');

            $plisio = new PlisioClient('');
            $currencies = $plisio->getCurrencies();
            if (!isset($_GET['order-received']) || !isset($_GET['key'])) {
                if (isset($currencies['data']) && !empty($currencies['data'])) {
                    $this->description = 'Pay with: <select name="currency" class="select">';
                    foreach ($currencies['data'] as $currency) {
                        $this->description .= '<option value="' . $currency['cid'] . '">' . $currency['name'] . '</option>';
                    }
                    $this->description .= '</select>';
                }
            }

            $this->api_key = $this->get_option('api_key');
            $this->order_statuses = $this->get_option('order_statuses');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'save_order_statuses'));
            add_action('woocommerce_thankyou_plisio', array($this, 'thankyou'));
            add_action('woocommerce_api_wc_plisio_gateway', array($this, 'payment_callback'));
        }

        public function admin_options()
        {
            ?>
            <h3><?php _e('Plisio', 'woothemes'); ?></h3>
            <p><?php _e('Accept cryptocurrencies through the Plisio.net')?><br>
            <table class="form-table">
                <?php $this->generate_settings_html(); ?>
            </table>
            <?php

        }

        public function init_form_fields()
        {
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

        public function thankyou()
        {
            if ($description = $this->get_description()) {
                echo wpautop(wptexturize($description));
            }
        }

        public function process_payment($order_id)
        {
            global $woocommerce, $page, $paged;
            $order = new WC_Order($order_id);

            $plisio = new PlisioClient($this->api_key);

            $description = array();
            foreach ($order->get_items('line_item') as $item) {
                $description[] = $item['qty'] . ' × ' . $item['name'];
            }

            $token = get_post_meta($order->get_id(), 'plisio_order_token', true);

            if (empty($token)) {
                $token = substr(md5(rand()), 0, 32);

                update_post_meta($order_id, 'plisio_order_token', $token);
            }

            $wcOrder = wc_get_order($order_id);

            $data = array(
                'order_number'      => $order->get_id(),
                'order_name'        => get_bloginfo('name', 'raw') . ' Order #' . $order->get_id(),
                'description'       => implode($description, ', '),
                'amount_usd'        => number_format($order->get_total(), 8, '.', ''),
                'orig_currency'     => get_woocommerce_currency(),
                'currency'          => sanitize_text_field($_POST['currency']),
                'cancel_url'        => $order->get_cancel_order_url(),
                'callback_url'      => trailingslashit(get_bloginfo('wpurl')) . '?wc-api=wc_plisio_gateway',
                'success_url'       => add_query_arg('order-received', $order->get_id(), add_query_arg('key', $order->get_order_key(), $this->get_return_url($wcOrder))),
            );
          $response = $plisio->createTransaction($data);

            if ($response && $response['status'] !== 'error' && !empty($response['data'])) {
              return array(
                'result' => 'success',
                'redirect' => $response['data']['invoice_url'],
              );
            } else {
              return array(
                'result' => 'fail',
              );
            }
        }

        public function payment_callback()
        {
            $request = $_POST;

            global $woocommerce;

            $order = new WC_Order($request['order_number']);

            try {
                if (!$order || !$order->get_id()) {
                    throw new Exception('Order #' . $request['order_id'] . ' does not exists');
                }


                $orderStatuses = $this->get_option('order_statuses');
                $wcOrderStatus = $orderStatuses[$request['status']];
                $wcExpiredStatus = $orderStatuses['expired'];
                $wcCanceledStatus = $orderStatuses['canceled'];

                switch ($request['status']) {
                    case 'completed':
                        $statusWas = "wc-" . $request['status'];

                        $order->update_status($wcOrderStatus);
                        $order->add_order_note(__('Payment is confirmed on the network, and has been credited to the merchant. Purchased goods/services can be securely delivered to the buyer.', 'plisio'));
                        $order->payment_complete();

                        if ($order->status == 'processing' && ($statusWas == $wcExpiredStatus || $statusWas == $wcCanceledStatus)) {
                            WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order->get_id());
                        }
                        if (($order->status == 'processing' || $order->status == 'completed') && ($statusWas == $wcExpiredStatus || $statusWas == $wcCanceledStatus)) {
                            WC()->mailer()->emails['WC_Email_New_Order']->trigger($order->get_id());
                        }
                        break;
                    case 'error':
                        $order->update_status($wcOrderStatus);
                        $order->add_order_note(__('Payment rejected by the network or did not confirm within 10 hours.', 'plisio'));
                        break;
                    case 'expired':
                        if($order->get_payment_method() === "plisio") {
                            $order->update_status($wcOrderStatus);
                            $order->add_order_note(__('Buyer did not pay within the required time and the invoice expired.',
                                'plisio'));
                        }
                        break;
                }
            } catch (Exception $e) {
                die(get_class($e) . ': ' . $e->getMessage());
            }
        }

        public function generate_order_statuses_html()
        {
            ob_start();

            $plisioStatuses = $this->plisioStatuses();
            $wcStatuses = wc_get_order_statuses();
            $defaultStatuses = array('completed' => 'wc-completed', 'invalid' => 'wc-failed', 'expired' => 'wc-cancelled', 'error' => 'wc-failed');

            ?>
            <tr valign="top">
                <th scope="row" class="titledesc">Order Statuses:</th>
                <td class="forminp" id="plisio_order_statuses">
                    <table cellspacing="0">
                        <?php
                        foreach ($plisioStatuses as $status => $statusTitle) {
                            ?>
                            <tr>
                                <th><?php echo $statusTitle; ?></th>
                                <td>
                                    <select name="woocommerce_plisio_order_statuses[<?php echo $status; ?>]">
                                        <?php
                                        $orderStatuses = get_option('woocommerce_plisio_settings');
                                        $orderStatuses = $orderStatuses['order_statuses'];

                                        foreach ($wcStatuses as $wcStatusName => $wcStatusTitle) {
                                            $currentStatus = $orderStatuses[$status];

                                            if (empty($currentStatus) === true)
                                                $currentStatus = $defaultStatuses[$status];

                                            if ($currentStatus == $wcStatusName)
                                                echo "<option value=\"$wcStatusName\" selected>$wcStatusTitle</option>";
                                            else
                                                echo "<option value=\"$wcStatusName\">$wcStatusTitle</option>";
                                        }
                                        ?>
                                    </select>
                                </td>
                            </tr>
                            <?php
                        }
                        ?>
                    </table>
                </td>
            </tr>
            <?php

            return ob_get_clean();
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
                $orderStatuses = $plisioSettings['order_statuses'];

                foreach ($plisioStatuses as $status => $statusTitle) {
                    if (isset($_POST['woocommerce_plisio_order_statuses'][$status]) === false)
                        continue;

                    $wcStatusName = $_POST['woocommerce_plisio_order_statuses'][$status];

                    if (array_key_exists($wcStatusName, $wcStatuses) === true)
                        $orderStatuses[$status] = $wcStatusName;
                }

                $plisioSettings['order_statuses'] = $orderStatuses;
                update_option('woocommerce_plisio_settings', $plisioSettings);
            }
        }

        private function plisioStatuses()
        {
            return array('completed' => 'Paid', 'invalid' => 'Invalid', 'expired' => 'Expired', 'error' => 'Failed');
        }

    }

    function add_plisio_gateway($methods)
    {
        $methods[] = 'WC_Plisio_Gateway';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_plisio_gateway');
}
