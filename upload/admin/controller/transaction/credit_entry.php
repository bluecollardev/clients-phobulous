<?php

class ControllerTransactionCreditEntry extends Controller {
	private $error = array();

	public function add() {
		$this->load->language('transaction/credit');
		$this->load->language('transaction/credit_entry');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('transaction/credit');
		unset($this->session->data['cookie']);
		if ($this->validate()) {
			$this->load->model('user/api');
			$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));
			if ($api_info) {
				$curl = curl_init();
				if (substr(HTTPS_CATALOG, 0, 5) == 'https') {
					curl_setopt($curl, CURLOPT_PORT, 443);
				}
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLINFO_HEADER_OUT, true);
				curl_setopt($curl, CURLOPT_USERAGENT, $this->request->server['HTTP_USER_AGENT']);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_URL, HTTPS_CATALOG . 'index.php?route=api/login');
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($api_info));
				$json = curl_exec($curl);
				if (!$json) {
					$this->error['warning'] = sprintf($this->language->get('error_curl'), curl_error($curl), curl_errno($curl));
				} else {
					$response = json_decode($json, true);
					if (isset($response['cookie'])) {
						$this->session->data['cookie'] = $response['cookie'];
					}
					curl_close($curl);
				}
			}
		}
		$this->getForm();
	}

	public function edit() {
		$this->load->language('transaction/credit');
		$this->load->language('transaction/credit_entry');
		$this->document->setTitle($this->language->get('heading_title'));
		$this->load->model('transaction/credit');
		unset($this->session->data['cookie']);
		if ($this->validate()) {
			$this->load->model('user/api');
			$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));
			if ($api_info) {
				$curl = curl_init();
				if (substr(HTTPS_CATALOG, 0, 5) == 'https') {
					curl_setopt($curl, CURLOPT_PORT, 443);
				}
				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLINFO_HEADER_OUT, true);
				curl_setopt($curl, CURLOPT_USERAGENT, $this->request->server['HTTP_USER_AGENT']);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_URL, HTTPS_CATALOG . 'index.php?route=api/login');
				curl_setopt($curl, CURLOPT_POST, true);
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($api_info));
				$json = curl_exec($curl);
				if (!$json) {
					$this->error['warning'] = sprintf($this->language->get('error_curl'), curl_error($curl), curl_errno($curl));
				} else {
					$response = json_decode($json, true);
					if (isset($response['cookie'])) {
						$this->session->data['cookie'] = $response['cookie'];
					}
					curl_close($curl);
				}
			}
		}
		$this->getForm();
	}

	public function getForm() {
		$this->load->model('sale/customer');
		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_form'] = !isset($this->request->get['credit_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_default'] = $this->language->get('text_default');
		$data['text_select'] = $this->language->get('text_select');
		$data['text_none'] = $this->language->get('text_none');
		$data['text_loading'] = $this->language->get('text_loading');
		$data['text_product'] = $this->language->get('text_product');
		$data['text_voucher'] = $this->language->get('text_voucher');
		$data['text_credit'] = $this->language->get('text_credit');
		$data['entry_store'] = $this->language->get('entry_store');
		$data['entry_customer'] = $this->language->get('entry_customer');
		$data['entry_customer_group'] = $this->language->get('entry_customer_group');
		$data['entry_firstname'] = $this->language->get('entry_firstname');
		$data['entry_lastname'] = $this->language->get('entry_lastname');
		$data['entry_email'] = $this->language->get('entry_email');
		$data['entry_telephone'] = $this->language->get('entry_telephone');
		$data['entry_fax'] = $this->language->get('entry_fax');
		$data['entry_comment'] = $this->language->get('entry_comment');
		$data['entry_affiliate'] = $this->language->get('entry_affiliate');
		$data['entry_address'] = $this->language->get('entry_address');
		$data['entry_company'] = $this->language->get('entry_company');
		$data['entry_address_1'] = $this->language->get('entry_address_1');
		$data['entry_address_2'] = $this->language->get('entry_address_2');
		$data['entry_city'] = $this->language->get('entry_city');
		$data['entry_postcode'] = $this->language->get('entry_postcode');
		$data['entry_zone'] = $this->language->get('entry_zone');
		$data['entry_zone_code'] = $this->language->get('entry_zone_code');
		$data['entry_country'] = $this->language->get('entry_country');
		$data['entry_product'] = $this->language->get('entry_product');
		$data['entry_option'] = $this->language->get('entry_option');
		$data['entry_quantity'] = $this->language->get('entry_quantity');
		$data['entry_price'] = $this->language->get('entry_price');
		$data['entry_notax'] = $this->language->get('entry_notax');
		$data['entry_to_name'] = $this->language->get('entry_to_name');
		$data['entry_to_email'] = $this->language->get('entry_to_email');
		$data['entry_from_name'] = $this->language->get('entry_from_name');
		$data['entry_from_email'] = $this->language->get('entry_from_email');
		$data['entry_theme'] = $this->language->get('entry_theme');
		$data['entry_message'] = $this->language->get('entry_message');
		$data['entry_amount'] = $this->language->get('entry_amount');
		$data['entry_currency'] = $this->language->get('entry_currency');
		$data['entry_add_customer'] = $this->language->get('entry_add_customer');
		$data['entry_shipping_method'] = $this->language->get('entry_shipping_method');
		$data['entry_payment_method'] = $this->language->get('entry_payment_method');
		$data['entry_coupon'] = $this->language->get('entry_coupon');
		$data['entry_voucher'] = $this->language->get('entry_voucher');
		$data['entry_reward'] = $this->language->get('entry_reward');
		$data['entry_credit_status'] = $this->language->get('entry_credit_status');
		$data['column_product'] = $this->language->get('column_product');
		$data['column_option'] = $this->language->get('column_option');
		$data['column_model'] = $this->language->get('column_model');
		$data['column_quantity'] = $this->language->get('column_quantity');
		$data['column_price'] = $this->language->get('column_price');
		$data['column_price_t'] = $this->language->get('column_price_t');
		$data['column_total'] = $this->language->get('column_total');
		$data['column_total_t'] = $this->language->get('column_total_t');
		$data['column_notax'] = $this->language->get('column_notax');
		$data['column_action'] = $this->language->get('column_action');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_continue'] = $this->language->get('button_continue');
		$data['button_back'] = $this->language->get('button_back');
		$data['button_product_add'] = $this->language->get('button_product_add');
		$data['button_voucher_add'] = $this->language->get('button_voucher_add');
		$data['button_apply'] = $this->language->get('button_apply');
		$data['button_upload'] = $this->language->get('button_upload');
		$data['button_update'] = $this->language->get('button_update');
		$data['button_remove'] = $this->language->get('button_remove');
		$data['tab_credit'] = $this->language->get('tab_credit');
		$data['tab_customer'] = $this->language->get('tab_customer');
		$data['tab_payment'] = $this->language->get('tab_payment');
		$data['tab_shipping'] = $this->language->get('tab_shipping');
		$data['tab_product'] = $this->language->get('tab_product');
		$data['tab_voucher'] = $this->language->get('tab_voucher');
		$data['tab_total'] = $this->language->get('tab_total');
		$data['token'] = $this->session->data['token'];
		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}
		$data['product_column_option'] = 0;
		$data['product_column_price'] = 0;
		$data['product_column_pricet'] = 0;
		$data['product_column_total'] = 0;
		$data['product_column_totalt'] = 0;
		$data['product_column_notax'] = 0;
		$prod_cols = $this->config->get('oe_product_columns');
		if (is_array($prod_cols)) {
			if (in_array('option', $prod_cols)) {
				$data['product_column_option'] = 1;
			}
			if (in_array('price', $prod_cols)) {
				$data['product_column_price'] = 1;
			}
			if (in_array('pricet', $prod_cols)) {
				$data['product_column_pricet'] = 1;
			}
			if (in_array('total', $prod_cols)) {
				$data['product_column_total'] = 1;
			}
			if (in_array('totalt', $prod_cols)) {
				$data['product_column_totalt'] = 1;
			}
			if (in_array('notax', $prod_cols)) {
				$data['product_column_notax'] = 1;
			}
		}
		$url = '';
		if (isset($this->request->get['filter_credit_id'])) {
			$url .= '&filter_credit_id=' . $this->request->get['filter_credit_id'];
		}
		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}
		if (isset($this->request->get['filter_credit_status'])) {
			$url .= '&filter_credit_status=' . $this->request->get['filter_credit_status'];
		}
		if (isset($this->request->get['filter_total'])) {
			$url .= '&filter_total=' . $this->request->get['filter_total'];
		}
		if (isset($this->request->get['filter_date_added'])) {
			$url .= '&filter_date_added=' . $this->request->get['filter_date_added'];
		}
		if (isset($this->request->get['filter_date_modified'])) {
			$url .= '&filter_date_modified=' . $this->request->get['filter_date_modified'];
		}
		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}
		if (isset($this->request->get['credit'])) {
			$url .= '&credit=' . $this->request->get['credit'];
		}
		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('transaction/credit', 'token=' . $this->session->data['token'] . $url, 'SSL')
		);
		$data['cancel'] = $this->url->link('transaction/credit', 'token=' . $this->session->data['token'] . $url, 'SSL');
		if (isset($this->request->get['credit_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$credit_info = $this->model_sale_credit->getCredit($this->request->get['credit_id']);
		}
		if (!empty($credit_info)) {
			$data['credit_id'] = $this->request->get['credit_id'];
			$data['credit_balance'] = $credit_info['total'];
			$data['store_id'] = $credit_info['store_id'];
			$data['customer'] = $credit_info['customer'];
			$data['customer_id'] = $credit_info['customer_id'];
			$data['customer_group_id'] = $credit_info['customer_group_id'];
			$data['firstname'] = $credit_info['firstname'];
			$data['lastname'] = $credit_info['lastname'];
			$data['email'] = $credit_info['email'];
			$data['telephone'] = $credit_info['telephone'];
			$data['fax'] = $credit_info['fax'];
			$data['account_custom_field'] = $credit_info['custom_field'];
			$this->load->model('sale/customer');
			$data['addresses'] = $this->model_sale_customer->getAddresses($credit_info['customer_id']);
			$data['payment_firstname'] = $credit_info['payment_firstname'];
			$data['payment_lastname'] = $credit_info['payment_lastname'];
			$data['payment_company'] = $credit_info['payment_company'];
			$data['payment_address_1'] = $credit_info['payment_address_1'];
			$data['payment_address_2'] = $credit_info['payment_address_2'];
			$data['payment_city'] = $credit_info['payment_city'];
			$data['payment_postcode'] = $credit_info['payment_postcode'];
			$data['payment_country_id'] = $credit_info['payment_country_id'];
			$data['payment_zone_id'] = $credit_info['payment_zone_id'];
			$data['payment_custom_field'] = $credit_info['payment_custom_field'];
			$data['payment_method'] = $credit_info['payment_method'];
			$data['payment_code'] = $credit_info['payment_code'];
			$data['shipping_firstname'] = $credit_info['shipping_firstname'];
			$data['shipping_lastname'] = $credit_info['shipping_lastname'];
			$data['shipping_company'] = $credit_info['shipping_company'];
			$data['shipping_address_1'] = $credit_info['shipping_address_1'];
			$data['shipping_address_2'] = $credit_info['shipping_address_2'];
			$data['shipping_city'] = $credit_info['shipping_city'];
			$data['shipping_postcode'] = $credit_info['shipping_postcode'];
			$data['shipping_country_id'] = $credit_info['shipping_country_id'];
			$data['shipping_zone_id'] = $credit_info['shipping_zone_id'];
			$data['shipping_custom_field'] = $credit_info['shipping_custom_field'];
			$data['shipping_method'] = $credit_info['shipping_method'];
			$data['shipping_code'] = $credit_info['shipping_code'];
			// Add products to the API
			$data['credit_products'] = array();
			$products = $this->model_sale_credit->getCreditProducts($this->request->get['credit_id']);
			foreach ($products as $product) {
				$notax = 0;
				$oe_product_info = $this->model_sale_credit->getOeCreditProducts($this->request->get['credit_id'], $product['credit_product_id'], $product['product_id']);
				if ($oe_product_info) {
					$notax = $oe_product_info['notax'];
				}
				$data['credit_products'][] = array(
					'product_id' => $product['product_id'],
					'name'       => $product['name'],
					'model'      => $product['model'],
					'option'     => $this->model_sale_credit->getCreditOptions($this->request->get['credit_id'], $product['credit_product_id']),
					'quantity'   => $product['quantity'],
					'price'      => $product['price'],
					'total'      => $product['total'],
					'notax'		 => $notax,
					'reward'     => $product['reward']
				);
			}
			// Add vouchers to the API
			$data['credit_vouchers'] = $this->model_sale_credit->getCreditVouchers($this->request->get['credit_id']);
			$data['coupon'] = '';
			$data['voucher'] = '';
			$data['reward'] = '';
			$data['credit_totals'] = array();
			$credit_totals = $this->model_sale_credit->getCreditTotals($this->request->get['credit_id']);
			foreach ($credit_totals as $credit_total) {
				// If coupon, voucher or reward points
				$start = strpos($credit_total['title'], '(') + 1;
				$end = strrpos($credit_total['title'], ')');
				if ($start && $end) {
					if ($credit_total['code'] == 'coupon') {
						$data['coupon'] = substr($credit_total['title'], $start, $end - $start);
					}
					if ($credit_total['code'] == 'voucher') {
						$data['voucher'] = substr($credit_total['title'], $start, $end - $start);
					}
					if ($credit_total['code'] == 'reward') {
						$data['reward'] = substr($credit_total['title'], $start, $end - $start);
					}
				}
			}
			$data['credit_status_id'] = $credit_info['credit_status_id'];
			$data['comment'] = $credit_info['comment'];
			$data['affiliate_id'] = $credit_info['affiliate_id'];
			$data['affiliate'] = $credit_info['affiliate_firstname'] . ' ' . $credit_info['affiliate_lastname'];
			$data['currency_code'] = $credit_info['currency_code'];
		} else {
			$data['credit_id'] = 0;
			$data['credit_balance'] = 0;
			$data['store_id'] = '';
			$data['customer'] = '';
			$data['customer_id'] = '';
			$data['customer_group_id'] = $this->config->get('config_customer_group_id');
			$data['firstname'] = '';
			$data['lastname'] = '';
			$data['email'] = '';
			$data['telephone'] = '';
			$data['fax'] = '';
			$data['customer_custom_field'] = array();
			$data['addresses'] = array();
			$data['payment_firstname'] = '';
			$data['payment_lastname'] = '';
			$data['payment_company'] = '';
			$data['payment_address_1'] = '';
			$data['payment_address_2'] = '';
			$data['payment_city'] = '';
			$data['payment_postcode'] = '';
			$data['payment_country_id'] = '';
			$data['payment_zone_id'] = '';
			$data['payment_custom_field'] = array();
			$data['payment_method'] = '';
			$data['payment_code'] = '';
			$data['shipping_firstname'] = '';
			$data['shipping_lastname'] = '';
			$data['shipping_company'] = '';
			$data['shipping_address_1'] = '';
			$data['shipping_address_2'] = '';
			$data['shipping_city'] = '';
			$data['shipping_postcode'] = '';
			$data['shipping_country_id'] = '';
			$data['shipping_zone_id'] = '';
			$data['shipping_custom_field'] = array();
			$data['shipping_method'] = '';
			$data['shipping_code'] = '';
			$data['credit_products'] = array();
			$data['credit_vouchers'] = array();
			$data['credit_totals'] = array();
			$data['credit_status_id'] = $this->config->get('config_credit_status_id');
			$data['comment'] = '';
			$data['affiliate_id'] = '';
			$data['affiliate'] = '';
			$data['currency_code'] = $this->config->get('config_currency');
			$data['coupon'] = '';
			$data['voucher'] = '';
			$data['reward'] = '';
		}
		// Stores
		$this->load->model('setting/store');
		$data['stores'] = $this->model_setting_store->getStores();
		// Customer Groups
		$this->load->model('sale/customer_group');
		$data['customer_groups'] = $this->model_sale_customer_group->getCustomerGroups();
		// Custom Fields
		$this->load->model('sale/custom_field');
		$data['custom_fields'] = array();
		$filter_data = array(
			'sort'  => 'cf.sort_credit',
			'credit' => 'ASC'
		);
		$custom_fields = $this->model_sale_custom_field->getCustomFields($filter_data);
		foreach ($custom_fields as $custom_field) {
			$data['custom_fields'][] = array(
				'custom_field_id'    => $custom_field['custom_field_id'],
				'custom_field_value' => $this->model_sale_custom_field->getCustomFieldValues($custom_field['custom_field_id']),
				'name'               => $custom_field['name'],
				'value'              => $custom_field['value'],
				'type'               => $custom_field['type'],
				'location'           => $custom_field['location'],
				'sort_credit'         => $custom_field['sort_credit']
			);
		}
		$this->load->model('localisation/credit_status');
		$data['credit_statuses'] = $this->model_localisation_credit_status->getCreditStatuses();
		$this->load->model('localisation/country');
		$data['countries'] = $this->model_localisation_country->getCountries();
		$this->load->model('localisation/currency');
		$data['currencies'] = $this->model_localisation_currency->getCurrencies();
		$data['voucher_min'] = $this->config->get('config_voucher_min');
		$this->load->model('sale/voucher_theme');
		$data['voucher_themes'] = $this->model_sale_voucher_theme->getVoucherThemes();
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		$this->response->setOutput($this->load->view('transaction/credit_entry_form.tpl', $data));
	}

	public function checkEmail() {
		$this->load->model('transaction/credit_entry');
		$result = $this->model_sale_credit_entry->checkEmail($this->request->get['email']);
		if ($result) {
			$json = "exists";
		} else {
			$json = "new";
		}
		$this->response->setOutput(json_encode($json));
	}

	public function clearCreditEntry() {
		unset($this->session->data['oe']);
		$this->response->redirect($this->url->link('transaction/credit', 'token=' . $this->session->data['token'], 'SSL'));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'transaction/credit_entry')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		return !$this->error;
	}

}