<?php

class WC_Gateway_Plisio_Order
{
	protected function validateRequiredData($data, $extra = [])
	{
		$required = array_merge(['order_id', 'plisio_invoice_id'], $extra);
		$invalid = [];
		foreach ($required as $item) {
			if (!isset($data[$item]) || empty($data[$item])) {
				$invalid[] = $item;
			}
		}
		return $invalid;
	}

    public function get($order_id)
    {
        global $wpdb;
        $query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}plisio_order WHERE order_id = %d",
            $order_id
        );
        $order = $wpdb->get_row($query, ARRAY_A);
        return $order;
    }

    private function prepareOrderData($data, $fields)
    {
        return array_filter($data, function ($i) use ($fields) {
            return in_array($i, $fields);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function add($data)
    {
        global $wpdb;

        $invalid = $this->validateRequiredData($data);
        if (count($invalid) === 0 && isset($data['wallet_hash']) && !empty($data['wallet_hash'])) {
            $data = $this->prepareOrderData($data, [
                'order_id', 'plisio_invoice_id', 'amount', 'pending_amount', 'wallet_hash', 'psys_cid',
                'currency', 'status', 'source_currency', 'source_rate', 'expire_utc',
                'confirmations', 'expected_confirmations', 'qr_code', 'tx_urls'
            ]);
            $data['expire_utc'] = (new DateTime())->setTimestamp($data['expire_utc'])->format('Y-m-d H:i:s');
            $orderTable = $wpdb->prefix . 'plisio_order';
            return $wpdb->insert($orderTable, $data);
        }
        return false;
    }

    public function update($data)
    {
        global $wpdb;

        $invalid = $this->validateRequiredData($data);
        if (count($invalid) === 0 && isset($data['wallet_hash']) && !empty($data['wallet_hash'])) {
            $where = [
                'order_id' => $data['order_id'],
                'plisio_invoice_id' => $data['plisio_invoice_id']
            ];
            $data = $this->prepareOrderData($data, ['pending_amount', 'status', 'qr_code', 'confirmations', 'tx_urls']);
            $orderTable = $wpdb->prefix . 'plisio_order';
            return $wpdb->update($orderTable, $data, $where);
        }
        return false;
    }
}
