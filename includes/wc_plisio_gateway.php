<?php
require_once(__DIR__ . '/PlisioClient.php');

class WC_Plisio_Gateway extends WC_Payment_Gateway
{
    public $api_key;
    public $order_statuses;
    private $order;

    public function __construct()
    {

        $this->id = 'plisio';
        $this->has_fields = false;
        $this->method_title = 'Plisio';
        $this->icon = apply_filters('woocommerce_plisio_icon', PLISIO_PLUGIN_URL . 'assets/plisio.png');

        $this->init_form_fields();
        $this->init_settings();

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->api_key = $this->get_option('api_key');
        $this->order_statuses = $this->get_option('order_statuses');

        $this->init_hooks();
        $this->order = new WC_Plisio_Gateway_Order();
    }


    public function init_hooks()
    {
        global $woocommerce;
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'save_order_statuses'));
        add_action('woocommerce_thankyou_plisio', array($this, 'thankyou'));
        add_action('woocommerce_api_wc_plisio_gateway', array($this, 'payment_callback'));
        if (is_object($woocommerce) && version_compare($woocommerce->version, '3.7.0', '>=')) {
            add_action('woocommerce_before_thankyou', array(
                $this, 'qrcode_section'
            ));
        } else {
            add_filter('do_shortcode_tag', array(
                $this, 'prepend_woocommerce_checkout_shortcode'
            ), 10, 4);
        }
        add_action('wp_enqueue_scripts', array($this, 'payment_scripts'));
    }

    public function payment_scripts(){
        global $wp;
        wp_enqueue_style('wc_plisio', PLISIO_PLUGIN_URL . 'assets/plisio.css', array(), PLISIO_WOOCOMMERCE_VERSION);
        wp_enqueue_script(
            'plisio-ajax-handle',
            PLISIO_PLUGIN_URL . 'assets/invoice.js',
            array('jquery'),
            PLISIO_WOOCOMMERCE_VERSION
        );
        wp_localize_script('plisio-ajax-handle', 'plisio_ajax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'order_id' => isset($wp->query_vars['order-received']) ? $wp->query_vars['order-received'] : null
        ));
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

    /**
     * Add QRcode section to thankyou page
     *
     * @param $order_id
     */
    public function qrcode_section($order_id)
    {
        $order = $this->order->get($order_id);

        if ($order) {
            $order['expire_utc'] = (new DateTime($order['expire_utc']))->getTimestamp() * 1000;
            if (isset($order['tx_urls']) && !empty($order['tx_urls'])) {
                try {
                    $txUrl = json_decode(stripslashes($order['tx_urls']));
                    if (!empty($txUrl)) {
                        $txUrl = gettype($txUrl) === 'string' ? $txUrl : $txUrl[count($txUrl) - 1];
                        $order['txUrl'] = $txUrl;
                    }
                } catch (Exception $e) {
                }
            }
            include(implode(DIRECTORY_SEPARATOR, [PLISIO_PLUGIN_PATH, 'templates', 'invoice.php']));
        }
    }


    public function payment_fields()
    {
        $plisio = new PlisioClient('');
        $currencies = $plisio->getCurrencies();
        if (isset($currencies['data']) && !empty($currencies['data'])) {
            $currencies = $currencies['data'];
            $showCurrencies = $currencies;
            $storedSettings = get_option('woocommerce_plisio_settings');
            $plisio_receive_currencies = [];
            if (is_array($storedSettings) && !empty($storedSettings['receive_currencies'])) {
                $plisio_receive_currencies = $storedSettings['receive_currencies'];
            }
            if (!empty($plisio_receive_currencies)) {
                $showCurrencies = array_reduce($currencies, function ($res, $i) use ($plisio_receive_currencies) {
                    if (in_array($i['cid'], $plisio_receive_currencies)) {
                        $res[$i['cid']] = $i;
                    }
                    return $res;
                });
                $showCurrencies = array_replace(array_flip($plisio_receive_currencies), $showCurrencies);
            }

            $html = 'Pay with <select name="currency" class="select">';
            foreach ($showCurrencies as $currency) {
                $html .= '<option value="' . $currency['cid'] . '">' . $currency['name'] . '</option>';
            }
            $html .= '</select>';
        }
        echo $html;
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
            'receive_currencies' => array(
                'type' => 'receive_currencies'
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
        $order = new WC_Order($order_id);

        $plisio = new PlisioClient($this->api_key);

        $description = array();
        foreach ($order->get_items('line_item') as $item) {
            $description[] = $item['qty'] . ' × ' . $item['name'];
        }

        $wcOrder = wc_get_order($order_id);

        $data = array(
            'order_number' => $order->get_id(),
            'order_name' => get_bloginfo('name', 'raw') . ' Order #' . $order->get_id(),
            'description' => implode($description, ', '),
            'source_amount' => number_format($order->get_total(), 8, '.', ''),
            'source_currency' => get_woocommerce_currency(),
            'currency' => sanitize_text_field($_POST['currency']),
            'cancel_url' => $order->get_cancel_order_url(),
            'callback_url' => trailingslashit(get_bloginfo('wpurl')) . '?wc-api=wc_plisio_gateway',
            'success_url' => add_query_arg('order-received', $order->get_id(), add_query_arg('key', $order->get_order_key(), $this->get_return_url($wcOrder))),
            'email' => $order->get_billing_email(),
            'language' => get_locale()
        );
        $response = $plisio->createTransaction($data);

        if ($response && $response['status'] !== 'error' && !empty($response['data'])) {
            update_post_meta($order_id, 'plisio_order_token', $response['data']['txn_id']);
            $this->order->add(array_merge($response['data'], [
                'order_id' => $order_id,
                'plisio_invoice_id' => $response['data']['txn_id']
            ]));

            if (isset($response['data']['wallet_hash']) && !empty($response['data']['wallet_hash'])){
                $redirect = $this->get_return_url($order);
            } else {
                $redirect = $response['data']['invoice_url'];
            }

            return array(
                'result' => 'success',
                'redirect' => $redirect
            );
        } else {
            return array(
                'result' => 'failure',
            );
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
            if ($order->get_payment_method() === "plisio") {
                try {
                    if (!$order || !$order->get_id()) {
                        throw new Exception('Order #' . $request['order_id'] . ' does not exists');
                    }

                    $orderStatuses = $this->get_option('order_statuses');
                    $wcOrderStatus = $orderStatuses[$request['status']];

                    switch ($request['status']) {
                        case 'new':
                        case 'pending':
                            if ($request['source_amount'] > 0) {
                                WC()->mailer()->emails['WC_Email_Customer_Processing_Order']->trigger($order->get_id());
                            } else {
                                WC()->mailer()->emails['WC_Email_New_Order']->trigger($order->get_id());
                            }
                            break;
                        case 'completed':
                        case 'mismatch':
                            $order->update_status($wcOrderStatus);
                            if (!isset($request['comment']) || empty($request['comment'])) {
                                $request['comment'] = __('Payment is confirmed on the network, and has been credited to the merchant. Purchased goods/services can be securely delivered to the buyer.', 'plisio');
                            }
                            $order->add_order_note($request['comment']);
                            $order->payment_complete();
                            break;
                        case 'error':
                            $order->update_status($wcOrderStatus);
                            $order->add_order_note(__('Payment rejected by the network or did not confirm within 10 hours.', 'plisio'));
                            break;
                        case 'expired':
                            if ((float)$request['source_amount'] <= 0) {
                                $wcOrderStatus = $orderStatuses['canceled'];
                                $order->add_order_note(__('Buyer did not pay within the required time and the invoice expired.',
                                    'plisio'));
                            } else {
                                $order->add_order_note($request['comment']);
                            }
                            $order->update_status($wcOrderStatus);

                            break;
                    }
                } catch (Exception $e) {
                    die(get_class($e) . ': ' . $e->getMessage());
                }
            }
        }
    }


    public function generate_receive_currencies_html()
    {
        $plisio = new PlisioClient('');
        $currencies = $plisio->getCurrencies();
        if (empty($currencies) || empty($currencies['data'])) {
            return false;
        }
        $supported_currencies = $currencies['data'];
        $storedSettings = get_option('woocommerce_plisio_settings');
        if (is_array($storedSettings) && !empty($storedSettings['receive_currencies'])) {
            $plisio_receive_currencies = $storedSettings['receive_currencies'];
        }
        if (!empty($plisio_receive_currencies)) {
            usort($supported_currencies, function ($a, $b) use ($plisio_receive_currencies) {
                $idxA = array_search($a['cid'], $plisio_receive_currencies);
                $idxB = array_search($b['cid'], $plisio_receive_currencies);

                $idxA = $idxA === false ? -1 : $idxA;
                $idxB = $idxB === false ? -1 : $idxB;

                if ($idxA < 0 && $idxB < 0) return -1;
                if ($idxA < 0 && $idxB >= 0) return 1;
                if ($idxA >= 0 && $idxB < 0) return -1;
                return $idxA - $idxB;
            });
        }
        include(implode(DIRECTORY_SEPARATOR, [PLISIO_PLUGIN_PATH, 'templates', 'currencies.php']));
    }


    public function validate_receive_currencies_field()
    {
        $post = isset($_POST[$this->plugin_id . $this->id . '_receive_currencies']) ? (array)$_POST[$this->plugin_id . $this->id . '_receive_currencies'] : array();
        if (empty($post)) return false;
        $post = array_map('esc_attr', $post);
        return $post;
    }


    public function generate_order_statuses_html()
    {
        $plisioStatuses = $this->plisioStatuses();
        $wcStatuses = wc_get_order_statuses();
        $defaultStatuses = $this->woocommenceStatuses();
        $storedSettings = get_option('woocommerce_plisio_settings');
        $selectedStatuses = $storedSettings['order_statuses'];

        include(implode(DIRECTORY_SEPARATOR, [PLISIO_PLUGIN_PATH, 'templates', 'admin_statuses_row.php']));
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
            'completed' => 'Paid',
            'mismatch' => 'Overpayed',
            'error' => 'Failed',
            'expired' => 'Expired',
            'cancelled' => 'Cancelled'
        );
    }

    private function woocommenceStatuses()
    {
        return array(
            'pending' => 'wc-pending',
            'completed' => 'wc-completed',
            'mismatch' => 'wc-processing',
            'error' => 'wc-failed',
            'expired' => 'wc-failed',
            'cancelled' => 'wc-cancelled',
        );
    }


}