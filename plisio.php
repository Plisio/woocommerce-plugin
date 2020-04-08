<?php
/**
 * Plugin Name: Plisio Payment Gateway for WooCommerce
 * Plugin URI: https://plisio.net/download/plugins/plisio-gateway-for-woocommerce-1.0.0.zip
 * Description: Accept cryptocurrencies via Plisio in your WooCommerce store
 * Version: 1.0.0
 * Author: Plisio
 * Author URI: http://plisio.net/
 * License: MIT
 */

define('PLISIO_WOOCOMMERCE_VERSION', '1.0.0');

add_action('plugins_loaded', 'plisio_init');

function plisio_init()
{
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

                    $storedSettings = get_option('woocommerce_plisio_settings');
                    if (is_array($storedSettings) && !empty($storedSettings['receive_currencies'])) {
                        $this->plisio_receive_currencies = $storedSettings['receive_currencies'];
                    }
                    if (!empty($this->plisio_receive_currencies)) {
                        usort($currencies['data'], function ($a, $b) {
                            $idxA = array_search($a['cid'], $this->plisio_receive_currencies);
                            $idxB = array_search($b['cid'], $this->plisio_receive_currencies);

                            $idxA = $idxA === false ? -1 : $idxA;
                            $idxB = $idxB === false ? -1 : $idxB;

                            if ($idxA < 0 && $idxB < 0) return -1;
                            if ($idxA < 0 && $idxB >= 0) return 1;
                            if ($idxA >= 0 && $idxB < 0) return -1;
                            return $idxA - $idxB;
                        });
                    }

                    $this->description = 'Pay with <select name="currency" class="select">';
                    foreach ($currencies['data'] as $currency) {
                        if (empty($this->plisio_receive_currencies) || in_array($currency['cid'], $this->plisio_receive_currencies)) {
                          $this->description .= '<option value="' . $currency['cid'] . '">' . $currency['name'] . '</option>';
                        }
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
          <p><?php _e('Accept cryptocurrencies through the Plisio.net') ?><br>
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
            global $woocommerce, $page, $paged;
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

        private function verifyCallbackData($post)
        {
            if (!isset($post['verify_hash'])) {
                return false;
            }

            $verifyHash = $post['verify_hash'];
            unset($post['verify_hash']);
            ksort($post);
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
                global $woocommerce;

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


        private
            $plisio_receive_currencies = array();


        public
        function generate_receive_currencies_html()
        {
            $plisio = new PlisioClient('');
            $currencies = $plisio->getCurrencies();
            if (empty($currencies) || empty($currencies['data'])) {
                return false;
            }
            $receive_currencies = $currencies['data'];
            $storedSettings = get_option('woocommerce_plisio_settings');
            if (is_array($storedSettings) && !empty($storedSettings['receive_currencies'])) {
                $this->plisio_receive_currencies = $storedSettings['receive_currencies'];
            }
            if (!empty($this->plisio_receive_currencies)) {
                usort($receive_currencies, function ($a, $b) {
                    $idxA = array_search($a['cid'], $this->plisio_receive_currencies);
                    $idxB = array_search($b['cid'], $this->plisio_receive_currencies);

                    $idxA = $idxA === false ? -1 : $idxA;
                    $idxB = $idxB === false ? -1 : $idxB;

                    if ($idxA < 0 && $idxB < 0) return -1;
                    if ($idxA < 0 && $idxB >= 0) return 1;
                    if ($idxA >= 0 && $idxB < 0) return -1;
                    return $idxA - $idxB;
                });
            }
            ob_start();
            ?>
          <style>
            .plisio-list-currencies table td {
              padding: 5px;
            }
          </style>
          <tr valign="top" class="plisio-list-currencies">
            <th scope="row" class="titledesc">Cryptocurrency:</th>
            <td>
              <!-- any -->
              <table cellspacing="0">
                <tr>
                  <td>
                      <?php if (empty($this->plisio_receive_currencies) || count($this->plisio_receive_currencies) === count($receive_currencies)): ?>
                        <input type="checkbox" value="" id="entry_currency_0" checked="checked"/>
                      <?php else: ?>
                        <input type="checkbox" value="" id="entry_currency_0"/>
                      <?php endif; ?>
                    <label for="entry_currency_0">Any</label></td>
                </tr>
              </table>
              <hr>
              <!-- choose some -->
              <table class="wc_input_table sortable" cellspacing="0" style="max-width: 400px;">
                <tbody class="ui-sortable">
                <?php if (empty($this->plisio_receive_currencies) || count($this->plisio_receive_currencies) === count($receive_currencies)): ?>
                    <?php foreach ($receive_currencies as $key => $currency): ?>
                    <tr class="ui-sortable-handle">
                      <td>
                        <input type="checkbox" name="woocommerce_plisio_receive_currencies[]"
                               value="<?= $currency['cid'] ?>"
                               id="entry_currency_<?= ++$key ?>" checked="checked"
                        />
                        <label for="entry_currency_<?= $key ?>"><?= $currency['name'] ?> (<?= $currency['currency'] ?>
                          )</label>
                      </td>
                      <td class="sort" style="width: 15px;"></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?php foreach ($receive_currencies as $key => $currency): ?>
                    <tr class="ui-sortable-handle">
                      <td>
                          <?php $isChecked = (is_array($this->plisio_receive_currencies) && in_array($currency['cid'], $this->plisio_receive_currencies)); ?>
                        <input type="checkbox" name="woocommerce_plisio_receive_currencies[]"
                               value="<?= $currency['cid'] ?>"
                               id="entry_currency_<?= ++$key ?>"
                            <?= $isChecked ? 'checked="checked"' : '' ?>
                        />
                        <label for="entry_currency_<?= $key ?>"><?= $currency['name'] ?> (<?= $currency['currency'] ?>
                          )</label>
                      </td>
                      <td class="sort" style="width: 15px;"></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
              </table>
              <p class="description">Drag and drop items to set order.</p>
            </td>
          </tr>
          <script>
            document.addEventListener('DOMContentLoaded', function () {
              var checkAny = document.getElementById('entry_currency_0');
              var checkSome = document.querySelectorAll('[name="woocommerce_plisio_receive_currencies[]"]');

              checkAny.addEventListener('click', function (event) {
                for (var i = 0; i < checkSome.length; i++) {
                  checkSome[i].checked = event.target.checked;
                }
              });

              checkSome.forEach(function (element) {
                element.addEventListener('click', function () {
                  var values = 0;
                  for (var i = 0; i < checkSome.length; i++) {
                    if (checkSome[i].checked) {
                      values++;
                    }
                  }
                  checkAny.checked = (values === checkSome.length);
                });
              });

              document.querySelector('form').addEventListener('submit', function(event) {
                var checked = 0;
                Array.prototype.forEach.call(checkSome, function (element) {
                  if (element.checked) checked += 1;
                });
                if (!checked) {
                  event.preventDefault();
                  alert('You must check at least one cryptocurrency.')
                }
              });
            });
          </script>
            <?php
            return ob_get_clean();
        }


        public
        function validate_receive_currencies_field()
        {
            $post = isset( $_POST[$this->plugin_id . $this->id . '_receive_currencies'] ) ? (array) $_POST[$this->plugin_id . $this->id . '_receive_currencies'] : array();
            if (empty($post))  return false;
            $post = array_map( 'esc_attr', $post );
            return $post;
        }


        public
        function generate_order_statuses_html()
        {
            ob_start();

            $plisioStatuses = $this->plisioStatuses();
            $wcStatuses = wc_get_order_statuses();
            $defaultStatuses = $this->woocommenceStatuses();
            $storedSettings = get_option('woocommerce_plisio_settings');
            $selectedStatuses = $storedSettings['order_statuses'];
            ?>
          <tr valign="top">
            <th scope="row" class="titledesc">Order Statuses:</th>
            <td class="forminp" id="plisio_order_statuses">
              <table cellspacing="0">
                  <?php
                  foreach ($plisioStatuses as $status => $statusTitle) {
                      if (!isset($selectedStatuses[$status]) || empty($currentStatus) === true) {
                          $currentStatus = $defaultStatuses[$status];
                      } else {
                          $currentStatus = $selectedStatuses[$status];
                      }
                      ?>
                    <tr>
                      <th><?php echo $statusTitle; ?></th>
                      <td>
                        <select name="woocommerce_plisio_order_statuses[<?php echo $status; ?>]">
                            <?php
                            foreach ($wcStatuses as $wcStatus => $wcStatusTitle) {
                                if ($currentStatus == $wcStatus)
                                    echo "<option value=\"$wcStatus\" selected>$wcStatusTitle</option>";
                                else
                                    echo "<option value=\"$wcStatus\">$wcStatusTitle</option>";
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

        public
        function validate_order_statuses_field()
        {
            $orderStatuses = $this->get_option('order_statuses');

            if (isset($_POST[$this->plugin_id . $this->id . '_order_statuses']))
                $orderStatuses = sanitize_text_field($_POST[$this->plugin_id . $this->id . '_order_statuses']);

            return $orderStatuses;
        }

        public
        function save_order_statuses()
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

        private
        function plisioStatuses()
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

        private
        function woocommenceStatuses()
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

    function add_plisio_gateway($methods)
    {
        $methods[] = 'WC_Plisio_Gateway';

        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'add_plisio_gateway');
}
