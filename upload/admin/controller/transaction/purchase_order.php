<?php
require_once(DIR_QC . 'vendor/autoload.php');

require_once(DIR_SYSTEM . 'engine/qccontroller.php');

require_once(DIR_SYSTEM . 'library/quickcommerce/resource.php'); // TODO: Not sure why this isn't autoloading... don't need to load the file in invoices...
require_once(DIR_SYSTEM . 'library/quickcommerce/transaction/purchase_order.php'); // TODO: Not sure why this isn't autoloading... don't need to load the file in invoices...

require_once(DIR_SYSTEM . 'library/quickcommerce/doctrine.php');
require_once(DIR_SYSTEM . 'library/quickcommerce/entity_manager.php');
require_once(DIR_SYSTEM . 'library/quickcommerce/lines.php');
require_once(DIR_SYSTEM . 'library/quickcommerce/session.php');

use App\Resource\Product;
use App\Resource\PurchaseOrder;
use App\Resource\PurchaseOrderLine;
//use App\Resource\Language;
use App\Resource\Option;
use App\Resource\ProductOption;
use App\Resource\ProductOptionValue;

use Ddeboer\DataImport\Workflow;
use Ddeboer\DataImport\Reader\ArrayReader;
use Ddeboer\DataImport\Reader\OneToManyReader;
use Ddeboer\DataImport\Writer\ArrayWriter;
use Ddeboer\DataImport\Writer\CallbackWriter;
use Ddeboer\DataImport\Writer\DoctrineWriter;
use Ddeboer\DataImport\ItemConverter\MappingItemConverter;
use Ddeboer\DataImport\ItemConverter\NestedMappingItemConverter;
use Ddeboer\DataImport\ValueConverter\DateTimeValueConverter;

use Doctrine\ORM\Configuration;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\NamingStrategy;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Tools;
use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\ORM\Tools\DisconnectedClassMetadataFactory;
use Doctrine\Common\Util\Inflector;
use Doctrine\Common\Util\Debug;

class ControllerTransactionPurchaseOrder extends Controller {
	private $error = array();

	/**
	 * @param $registry
	 * @throws Exception
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Doctrine\ORM\ORMException
	 */
	function __construct($registry) {
		//if (empty($this->tableName)) // TODO: Interface yo
		//throw new Exception('Mapping table name ($tableName) was not specified in the extending controller class');
		//if (empty($this->joinTableName)) // TODO: Interface yo
		//throw new Exception('Join table name ($joinTableName) was not specified in the extending controller class');
		//if (empty($this->joinCol)) // TODO: Interface yo
		//throw new Exception('Join column name ($joinCol) was not specified in the extending controller class');

		$registry->set('tax', new Tax($registry));
		$registry->set('lines', new Lines($registry)); // Lines requires tax, load in order
		parent::__construct($registry);

		$di = new DoctrineInitializer($this, $registry);
	}

	// TODO: Make some kind of Doctrine helper, or move this to EntityMapper class
	// I'm going to turn this into a static method too
	// This is also in qccontroller class need to refactor
	public function mapDoctrineEntity(&$mappings, $config = array(), $children = false, $foreign = true) {
		try {
			// Being able to nest like this would be cool, but I don't know if there will be issues
			// with the mapping converter if there are similarly named keys...
			/*$this->context->mapDoctrineEntity($mappings, array(
				'OcTransaction' => array(
					'foreign' => 'Transaction',
					'meta' => $tMeta,
					'children' => array(
						'OcPurchaseOrder' => array(
							'foreign' => 'PurchaseOrder',
							'meta' => $iMeta,
							'children' => array(
								'OcPurchaseOrderLine' => array(
									'foreign' => 'Line',
									'meta' => $ilMeta
								),
								'OcOrderOption' => array(
									'foreign' => 'Option',
									'meta' => $ooMeta
								),
								'OcVendor' => array(
									'foreign' => 'Vendor',
									'meta' => $cMeta
								)
							)
						)
					)
				)
			), 2);*/

			// How many levels of nesting?
			// 0: Root only
			// 1: One level
			// n: n levels
			$children = abs((int)$children);

			$entityName = key($config);
			$params = $config[$entityName];
			$meta = $params['meta'];

			$mapping = array_fill_keys(array_keys($meta->fieldMappings), null);

			// Mapping against the remote entity
			if ($foreign && isset($params['foreign'])) {
				// Get relevant XML node(s);
				$xpath = '//entity[@foreign="' . $params['foreign'] . '"]';
				// TODO: Check to see if key exists - if anything's missing throw an Exception
				foreach($this->mapXml->xpath($xpath) as $map) {
					$fields = $map->xpath('./field | ./id');

					// TODO: This is very similar to EntityMapper::mapFields
					// I might want to do make this a utility method
					foreach ($fields as $field) {
						$attributes = $field->attributes();
						$col = (string)$attributes['column'];
						$field = (string)$attributes['name'];
						$type = (string)$attributes['type'];

						// Is the local model property a complex type?
						//var_dump(isset($attributes['column']));
						if ($field && isset($attributes['column'])) {
							//var_dump($name . ' => ' . $foreign); // Keep this one in here
							//var_dump($attributes);
							if ($field && $type && array_key_exists($field, $mapping))
								$mapping[$field] = $col;
						}

					}

					if (($children != 0) && array_key_exists('children', $params)) {
						$assoc = $map->xpath('./many-to-one');

						foreach ($assoc as $field) {
							$attributes = $field->attributes();
							$type = (string)$attributes['type'];
							$joinColAttr = false;
							$field = (string)$attributes['field'];
							$childEntityName = (string)$attributes['target-entity'];

							// Children should have been provided in params
							if (array_key_exists($childEntityName, $params['children'])) {
								$childMapping = [];


								$this->mapDoctrineEntity($childMapping, array(
									$childEntityName => $params['children'][$childEntityName]
								), false); // Do not automatically map children

								$mapping[$field] = $childMapping;
							}
						}

						$assoc = $map->xpath('./one-to-many');

						foreach ($assoc as $field) {
							$attributes = $field->attributes();
							$type = (string)$attributes['type'];
							$joinColAttr = false;
							$field = (string)$attributes['field'];
							$childEntityName = (string)$attributes['target-entity'];

							// Children should have been provided in params
							if (array_key_exists($childEntityName, $params['children'])) {
								$childMapping = [];

								$this->mapDoctrineEntity($childMapping, array(
									$childEntityName => $params['children'][$childEntityName]
								), false); // Do not automatically map children

								$mapping[$field] = array($childMapping);
							}
						}
					}

					/*$tableize = false; // TODO: What is this for again?

					if (isset($metadata)) {
						if (property_exists($metadata, 'associationmapping')) {
							foreach ($metadata->associationmapping as $field => $mapping) {
								$key = ($tableize == true) ? Inflector::tableize($field) : $field;
							}
						}
					}*/
				}
			} else {
				// Just tableize the field name if we're mapping a local entity
				foreach ($mapping as $field => $col) {
					$mapping[$field] = Inflector::tableize($field);
				}

				// TODO: Fix me!
				if (($children != 0) && array_key_exists('children', $params)) {
					$assoc = $meta->getAssociationMappings();

					foreach ($assoc as $field) {
						$childEntityName = (string)$field['targetEntity'];

						//if ($childEntityName != 'OcStore') continue; // TODO: TEMP Fix

						// Children should have been provided in params
						if (array_key_exists($childEntityName, $params['children'])) {
							$childMapping = [];

							$this->mapDoctrineEntity($childMapping, array(
								$childEntityName => $params['children'][$childEntityName]
							), false, false); // Do not automatically map children and it's a local op too

							$mapping[$field['fieldName']] = $childMapping;
						}
					}

					// TODO: Other association types?
				}
			}

			$mappings = array_merge($mappings, $mapping);
		} catch (Exception $e) {
			// Fail silently for now
			throw $e;
		}
	}

	public function index() {
		$this->load->language('transaction/purchase_order');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('resource/transaction');
		$tModel = &$this->model_resource_transaction;

		$tService = new TransactionPurchaseOrder($this, 'OcPurchaseOrder');
		$tService->setEntityManager($this->em);
		$tModel->setTransactionType($tService);

		$this->getList();

		//$this->testMax($tService); // Test
	}

	/**
	 * Test methods - move these to unit testing later
	 */
	/*private function testMax($service) {
		//var_dump(get_class_methods($service));

		$where = new \Doctrine\ORM\Query\Expr();
		$where = $where->eq('i.invoicePrefix', (new \Doctrine\ORM\Query\Expr())->literal('CT'));
		$result = $service->getMax('i', 'invoiceNo', $where);

		Debug::dump($result[0]);
	}*/

	/**
	 * Credits mcuadros/currency-detector
	 * https://github.com/mcuadros/currency-detector
	 * TODO: Move this to a utility class or something
	 */
	public static function cleanCurrency($money) {
		$cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
		$onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);

		$separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;

		$stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
		$removedThousendSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '',  $stringWithCommaOrDot);

		return (float) str_replace(',', '.', $removedThousendSeparator);
	}

	public function post($close = false) {
		// Test run
		$this->load->language('api/order');

		$json = array();

		//$lines = $this->model_resource_transaction->getLineItems($purchaseOrderId);
		$lines = $this->load->controller('api/lines/lines', array('export' => false));
		//var_dump($json);
		if (!$json) {
			$purchaseOrderData = array();

			// Store Details
			$purchaseOrderData['purchase_order_prefix'] = $this->config->get('config_purchase_order_prefix');
			$purchaseOrderData['store_id'] = ($this->config->get('config_store_id') != null) ? $this->config->get('config_store_id') : 0;
			$purchaseOrderData['store_name'] = $this->config->get('config_name');
			$purchaseOrderData['store_url'] = $this->config->get('config_url');

			$purchaseOrderData['oc_entity_id'] = null; // TODO: WTF

			$vendor = ModelOcSessionVendor::getVendor($this);

			$purchaseOrderData = array_merge($purchaseOrderData, $vendor);
			$purchaseOrderData['bill_email'] = $vendor['email'];
			$purchaseOrderData['bill_telephone'] = $vendor['telephone'];
			$purchaseOrderData['bill_fax'] = $vendor['fax'];

			$payment = ModelOcSessionPayment::getPayment($this);
			//echo 'Payment';
			//var_dump($payment);
			$purchaseOrderData = array_merge($purchaseOrderData, array(
				'bill_addr_line1' => $payment['payment_address_1'],
				'bill_addr_line2' => $payment['payment_address_2'],
				'bill_addr_firstname' => $payment['payment_firstname'],
				'bill_addr_lastname' => $payment['payment_lastname'],
				'bill_addr_company' => $payment['payment_company'],
				'bill_addr_city' => $payment['payment_city'],
				'bill_addr_country' => $payment['payment_country'],
				'bill_addr_country_id' => $payment['payment_country_id'],
				'bill_addr_zone' => $payment['payment_zone'],
				'bill_addr_zone_id' => $payment['payment_zone_id']
			));

			// Shipping Details
			//if ($this->lines->hasShipping()) {
			$shipping = ModelOcSessionShipping::getShipping($this);
			$purchaseOrderData = array_merge($purchaseOrderData, array(
				'ship_addr_line1' => $shipping['shipping_address_1'],
				'ship_addr_line2' => $shipping['shipping_address_2'],
				'ship_addr_firstname' => $shipping['shipping_firstname'],
				'ship_addr_lastname' => $shipping['shipping_lastname'],
				'ship_addr_company' => $shipping['shipping_company'],
				'ship_addr_city' => $shipping['shipping_city'],
				'ship_addr_country' => $shipping['shipping_country'],
				'ship_addr_country_id' => $shipping['shipping_country_id'],
				'ship_addr_zone' => $shipping['shipping_zone'],
				'ship_addr_zone_id' => $shipping['shipping_zone_id']
			));

			// Products
			$purchaseOrderData['products'] = array();

			// Need to set lines to session
			foreach ($lines['lines'] as $line) {
				// Get extra line props from raw key
				$line['line_id'] = (isset($line['rawkey']['line_id'])) ? $line['rawkey']['line_id'] : null;
				$line['detail_type'] = (isset($line['rawkey']['detail_type'])) ? $line['rawkey']['detail_type'] : null;
				$line['purchase_order_id'] = (isset($line['rawkey']['purchase_order_id'])) ? $line['rawkey']['purchase_order_id'] : null;
				$line['order_product_id'] = (isset($line['rawkey']['order_product_id'])) ? $line['rawkey']['order_product_id'] : null;

				$options = array(); // TODO: I need to enforce order product id - it is required later
				if (isset($line['purchase_order_id']) && isset($line['order_product_id'])) {
					$options = $this->model_sale_order->getOrderOptions($line['purchase_order_id'], $line['order_product_id']);
				}

				// TODO: Modify whatever's converting the entity to an array so we don't have to do this
				// Either that or make a utility method to get assoc keys
				$productId = null;
				if (isset($line['product']) && isset($line['product']['productId'])) {
					$productId = $line['product']['productId'];
				} elseif (isset($line['product_id'])) {
					$productId = $line['product_id'];
				}

				$purchaseOrderData['lines'][] = array(
					'line_id'			=> $line['line_id'],
					'detail_type'		=> $line['detail_type'],
					'purchase_order_id'			=> $line['purchase_order_id'],
					'order_product_id'	=> $line['purchase_order_id'],
					'product_id'		=> $productId,
					'name'			=> (isset($line['name'])) ? $line['name'] : '',
					'model'			=> (isset($line['model'])) ? $line['model'] : '',
					//'option'		=> (isset($line['purchase_order_id'])) ? $this->model_sale_order->getOrderOptions($data['purchase_order_id'], $line['order_product_id']) : null,
					'quantity'		=> (isset($line['quantity'])) ? (int)$line['quantity'] : 1,
					'revenue'    	=> (isset($line['revenue'])) ? self::cleanCurrency($line['revenue']) : '',
					'vest'       	=> (isset($line['vest'])) ? self::cleanCurrency($line['vest']) : '',
					'price'      	=> (isset($line['price'])) ? self::cleanCurrency($line['price']) : 0.00,
					'royalty'    	=> (isset($line['royalty'])) ? self::cleanCurrency($line['royalty']) : '',
					'reward'     	=> (isset($line['reward'])) ? self::cleanCurrency($line['reward']) : null,
					'total'      	=> (isset($line['total'])) ? self::cleanCurrency($line['total']) : 0.00,
					'tax'      		=> (isset($line['tax'])) ? $line['tax'] : 0.00,
					'tax_class_id'  => (isset($line['tax_class_id'])) ? (int)$line['tax_class_id'] : 4
				);
			}

			// Gift Voucher
			$purchaseOrderData['vouchers'] = array();

			$vouchers = ModelOcSessionVoucher::getVouchers($this);
			if ($vouchers) {
				$purchaseOrderData = array_merge($purchaseOrderData, $vouchers);
			}

			// Order Totals
			$this->load->model('extension/extension');

			$purchaseOrderData['totals'] = array();
			$total = 0;
			$taxes = $this->lines->getTaxes();

			$sortOrder = array();

			$results = $this->model_extension_extension->getExtensions('total');

			foreach ($results as $key => $value) {
				$sortOrder[$key] = $this->config->get($value['code'] . '_sort_order');
			}

			array_multisort($sortOrder, SORT_ASC, $results);

			foreach ($results as $result) {
				if ($this->config->get($result['code'] . '_status')) {
					$this->load->model('total/' . $result['code']);

					$this->{'model_total_' . $result['code']}->getTotal($purchaseOrderData['totals'], $total, $taxes, true);
				}
			}

			$sortOrder = array();

			foreach ($purchaseOrderData['totals'] as $key => $value) {
				$sortOrder[$key] = $value['sort_order'];
			}

			array_multisort($sortOrder, SORT_ASC, $purchaseOrderData['totals']);

			$purchaseOrderData['total'] = $total;

			if (isset($this->request->post['affiliate_id']) && is_int($this->request->post['affiliate_id'])) {
				$subtotal = $this->lines->getSubTotal();

				// Affiliate
				$this->load->model('affiliate/affiliate');

				$affiliateInfo = $this->model_affiliate_affiliate->getAffiliate($this->request->post['affiliate_id']);

				if ($affiliateInfo) {
					$purchaseOrderData['affiliate_id'] = $affiliateInfo['affiliate_id'];
					$purchaseOrderData['commission'] = ($subtotal / 100) * $affiliateInfo['commission'];
				} else {
					$purchaseOrderData['affiliate_id'] = 0;
					$purchaseOrderData['commission'] = 0;
				}

				// Marketing
				$purchaseOrderData['marketing_id'] = 0;
				$purchaseOrderData['tracking'] = '';
			} else {
				$purchaseOrderData['affiliate_id'] = 0;
				$purchaseOrderData['commission'] = 0;
				$purchaseOrderData['marketing_id'] = 0;
				$purchaseOrderData['tracking'] = '';
			}

			$purchaseOrderData['language_id'] = $this->config->get('config_language_id');
			$purchaseOrderData['currency_id'] = $this->currency->getId();
			$purchaseOrderData['currency_code'] = $this->currency->getCode();
			$purchaseOrderData['currency_value'] = $this->currency->getValue($this->currency->getCode());
			$purchaseOrderData['ip'] = $this->request->server['REMOTE_ADDR'];

			//var_dump($purchaseOrderData);

			if (!empty($this->request->server['HTTP_X_FORWARDED_FOR'])) {
				$purchaseOrderData['forwarded_ip'] = $this->request->server['HTTP_X_FORWARDED_FOR'];
			} elseif (!empty($this->request->server['HTTP_CLIENT_IP'])) {
				$purchaseOrderData['forwarded_ip'] = $this->request->server['HTTP_CLIENT_IP'];
			} else {
				$purchaseOrderData['forwarded_ip'] = '';
			}

			if (isset($this->request->server['HTTP_USER_AGENT'])) {
				$purchaseOrderData['user_agent'] = $this->request->server['HTTP_USER_AGENT'];
			} else {
				$purchaseOrderData['user_agent'] = '';
			}

			if (isset($this->request->server['HTTP_ACCEPT_LANGUAGE'])) {
				$purchaseOrderData['accept_language'] = $this->request->server['HTTP_ACCEPT_LANGUAGE'];
			} else {
				$purchaseOrderData['accept_language'] = '';
			}

			// TODO: Create order if necessary?
			//$this->load->model('checkout/order');
			//$json['purchase_order_id'] = $this->model_checkout_order->addOrder($purchaseOrderData);

			//$this->load->model('transaction/purchase_order');

			if (isset($this->request->post['purchase_order_status_id'])) {
				$purchaseOrderData['purchase_order_status_id'] = $this->request->post['purchase_order_status_id'];
			} else {
				$purchaseOrderData['purchase_order_status_id'] = 1; //$this->config->get('config_purchase_order_status_id');
			}

			// Set the order history
			if (isset($this->request->post['order_status_id'])) {
				$purchaseOrderData['order_status_id'] = $this->request->post['order_status_id'];
			} else {
				$purchaseOrderData['order_status_id'] = $this->config->get('config_order_status_id');
			}

			if (isset($this->request->post['purchase_order_date'])) {
				$purchaseOrderData['purchase_order_date'] = new DateTime($this->request->post['purchase_order_date']);
			} else {
				$purchaseOrderData['purchase_order_date'] = new DateTime('now');
			}

			if (isset($this->request->post['due_date'])) {
				$purchaseOrderData['due_date'] = new DateTime($this->request->post['due_date']);
			} else {
				if (isset($purchaseOrderData['purchase_order_date'])) {
					$purchaseOrderData['due_date'] = (new DateTime($this->request->post['purchase_order_date']))->modify('+1 month');
				}
			}

			if (isset($this->request->post['customer_memo'])) {
				$purchaseOrderData['customer_memo'] = htmlspecialchars($this->request->post['customer_memo']);
			} else {
				$purchaseOrderData['customer_memo'] = '';
			}

			if (isset($this->request->post['statement_memo'])) {
				$purchaseOrderData['statement_memo'] = htmlspecialchars($this->request->post['statement_memo']);
			} else {
				$purchaseOrderData['statement_memo'] = '';
			}

			//$this->model_checkout_order->addOrderHistory($json['purchase_order_id'], $order_status_id);

			$json['success'] = $this->language->get('text_success');
		}
		//}

		return $purchaseOrderData;
	}

	public function add() {
		$this->load->language('transaction/purchase_order');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('resource/transaction');
		$tModel = &$this->model_resource_transaction;

		$tService = new TransactionPurchaseOrder($this, 'OcPurchaseOrder');
		$tService->setEntityManager($this->em);
		$tModel->setTransactionType($tService);

		$this->load->model('sale/order');

		unset($this->session->data['cookie']);

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$model = null;
			if ($this->validate()) {
				$model = $this->post();
				$now = date('Y-m-d H:i:s');

				$model = array(
					'transaction_id' => null, // No way these can be set
					'store_id' => (isset($model['store_id'])) ? $model['store_id'] : 0,
					'store_name' => (isset($model['store_name'])) ? $model['store_name'] : '',
					'store_url' => (isset($model['store_url'])) ? $model['store_url'] : '',
					'currency_id' => (isset($model['currency_id'])) ? $model['currency_id'] : '',
					'currency_code' => (isset($model['currency_code'])) ? $model['currency_code'] : '',
					'currency_value' => (isset($model['currency_value'])) ? $model['currency_value'] : '',
					'forwarded_ip' => (isset($model['forwarded_ip'])) ? $model['forwarded_ip'] : '',
					'user_agent' => (isset($model['accept_language'])) ? $model['accept_language'] : '',
					'date_added' => (isset($model['date_added'])) ? $model['date_added'] : $now, // Now
					'date_modified' => $now, // Now,
					'purchase_order' => $model
				);

				//$model['invoice']['lines'] = array();
			}

			if ($model != null) {
				$this->event->trigger('pre.admin.purchase_order.add');

				$purchaseOrderId = $tService->addTransaction($model);

				// TODO: If success...
				$this->response->addHeader('Content-Type: application/json');

				if ($purchaseOrderId) {
					$this->event->trigger('post.admin.purchase_order.add', $purchaseOrderId);

					$json['purchase_order_id'] = $purchaseOrderId;
					$json['redirect'] = $this->url->link('transaction/purchase_order/edit&purchase_order_id=' . $purchaseOrderId . '&token=' . $this->session->data['token'], 'SSL');
				}

				$this->response->setOutput(json_encode($json));

				return;
			}
		} else {
			$this->getForm('add');
		}
	}

	public function edit() {
		$this->load->model('sale/order');

		$this->load->model('resource/transaction');
		$tModel = &$this->model_resource_transaction;

		$tService = new TransactionPurchaseOrder($this, 'OcPurchaseOrder');
		$tService->setEntityManager($this->em);
		$tModel->setTransactionType($tService);

		unset($this->session->data['cookie']);

		if (isset($this->request->get['purchase_order_id']) && ($this->request->server['REQUEST_METHOD'] == 'POST')) {
			$purchaseOrderInfo = $this->model_resource_transaction->getTransaction($this->request->get['purchase_order_id']);

			$model = null;
			if ($this->validate()) {
				$model = $this->post();

				$lines = $model['lines'];
				unset($model['lines']); // TODO: Lines are/may be attached to the wrong branch of the array? Quick fix

				$model = array(
					'transaction_id' => (isset($model['transaction_id'])) ? $model['transaction_id'] : $purchaseOrderInfo['transaction_id'],
					'oc_entity_id' => (isset($model['oc_entity_id'])) ? $model['oc_entity_id'] : $purchaseOrderInfo['oc_entity_id'],
					'store_id' => (isset($model['store_id'])) ? $model['store_id'] : $purchaseOrderInfo['store_id'],
					'store_name' => (isset($model['store_name'])) ? $model['store_name'] : $purchaseOrderInfo['store_name'],
					'store_url' => (isset($model['store_url'])) ? $model['store_url'] : $purchaseOrderInfo['store_url'],
					'currency_id' => (isset($model['currency_id'])) ? $model['currency_id'] : $purchaseOrderInfo['currency_id'],
					'currency_code' => (isset($model['currency_code'])) ? $model['currency_code'] : $purchaseOrderInfo['currency_code'],
					'currency_value' => (isset($model['currency_value'])) ? $model['currency_value'] : $purchaseOrderInfo['currency_value'],
					'forwarded_ip' => (isset($model['forwarded_ip'])) ? $model['forwarded_ip'] : $purchaseOrderInfo['forwarded_ip'],
					'user_agent' => (isset($model['accept_language'])) ? $model['accept_language'] : $purchaseOrderInfo['accept_language'],
					'date_added' => (isset($model['date_added'])) ? $model['date_added'] : $purchaseOrderInfo['date_added'],
					'date_modified' => date('Y-m-d H:i:s'), // Now
					'purchase_order' => array_merge($purchaseOrderInfo, $model)
				);

				$model['purchase_order']['lines'] =  $lines;
				//var_dump($model);
				//exit;
			}

			if ($model != null) {
				$this->event->trigger('pre.admin.purchase_order.edit', $this->request->get['purchase_order_id']);

				$tService->editTransaction($model);

				$this->event->trigger('post.admin.purchase_order.edit', $this->request->get['purchase_order_id']);
			}
		} else {
			$this->getForm('edit');
		}
	}

	public function delete() {
		$this->load->language('transaction/purchase_order');

		$this->document->setTitle($this->language->get('heading_title'));

		unset($this->session->data['cookie']);

		$this->load->model('sale/order');

		$this->load->model('resource/transaction');
		$tModel = &$this->model_resource_transaction;

		$tService = new TransactionPurchaseOrder($this, 'OcPurchaseOrder');
		$tService->setEntityManager($this->em);
		$tModel->setTransactionType($tService);

		if (isset($this->request->get['purchase_order_id']) && ($this->request->server['REQUEST_METHOD'] == 'GET')) {
			$purchaseOrderInfo = $tService->getEntity($this->request->get['purchase_order_id']);

			$model = null;
			if ($this->validate()) {
				$model = $this->post();
				$model = array_merge(array(
					'transaction_id' => $purchaseOrderInfo['transaction_id'],
					'oc_entity_id' => $purchaseOrderInfo['oc_entity_id'],
					'store_id' => $purchaseOrderInfo['store_id'],
					'store_name' => $purchaseOrderInfo['store_name'],
					'store_url' => $purchaseOrderInfo['store_url'],
					'date_added' => $purchaseOrderInfo['date_added'],
					'date_modified' => $purchaseOrderInfo['date_added'],
					'purchase_order' => $purchaseOrderInfo
				), $model);

				//var_dump($model);
				//exit;
				$model['purchase_order']['lines'] = $model['lines'];
				unset($model['lines']); // TODO: Lines are/may be attached to the wrong branch of the array? Quick fix
			}

			if ($model != null) {
				$tService->deleteTransaction($model);
			}
		}

		if (isset($response['error'])) {
			$this->error['warning'] = $response['error'];
		}

		// Quick fix
		//if (isset($response['success'])) {
		$this->session->data['success'] = $response['success'];

		$url = '';

		if (isset($this->request->get['filter_purchase_order_id'])) {
			$url .= '&filter_purchase_order_id=' . $this->request->get['filter_purchase_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_purchase_order_status'])) {
			$url .= '&filter_purchase_order_status=' . $this->request->get['filter_purchase_order_status'];
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

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$this->response->redirect($this->url->link('transaction/purchase_order', 'token=' . $this->session->data['token'] . $url, 'SSL'));
		//}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['filter_purchase_order_id'])) {
			$filterPurchaseOrderId = $this->request->get['filter_purchase_order_id'];
		} else {
			$filterPurchaseOrderId = null;
		}

		if (isset($this->request->get['filter_customer'])) {
			$filterVendor = $this->request->get['filter_customer'];
		} else {
			$filterVendor = null;
		}

		if (isset($this->request->get['filter_purchase_order_status'])) {
			$filterPurchaseOrderStatus = $this->request->get['filter_purchase_order_status'];
		} else {
			$filterPurchaseOrderStatus = null;
		}

		if (isset($this->request->get['filter_total'])) {
			$filterTotal = $this->request->get['filter_total'];
		} else {
			$filterTotal = null;
		}

		if (isset($this->request->get['filter_date_added'])) {
			$filter_date_added = $this->request->get['filter_date_added'];
		} else {
			$filter_date_added = null;
		}

		if (isset($this->request->get['filter_date_modified'])) {
			$filterDateModified = $this->request->get['filter_date_modified'];
		} else {
			$filterDateModified = null;
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'o.purchase_order_id';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_purchase_order_id'])) {
			$url .= '&filter_purchase_order_id=' . $this->request->get['filter_purchase_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_purchase_order_status'])) {
			$url .= '&filter_purchase_order_status=' . $this->request->get['filter_purchase_order_status'];
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

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data = $this->load->language('transaction/purchase_order');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('transaction/purchase_order', 'token=' . $this->session->data['token'] . $url, 'SSL')
		);

		$data['purchase_order'] = $this->url->link('transaction/purchase_order/purchase_order', 'token=' . $this->session->data['token'], 'SSL');
		$data['shipping'] = $this->url->link('transaction/purchase_order/shipping', 'token=' . $this->session->data['token'], 'SSL');
		$data['add'] = $this->url->link('transaction/purchase_order/add', 'token=' . $this->session->data['token'], 'SSL');

		$data['purchase_orders'] = array();

		$filterData = array(
			'filter_purchase_order_id'    => $filterPurchaseOrderId,
			'filter_customer'	   => $filterVendor,
			'filter_purchase_order_status'  => $filterPurchaseOrderStatus,
			'filter_total'         => $filterTotal,
			'filter_date_added'    => $filter_date_added,
			'filter_date_modified' => $filterDateModified,
			'sort'                 => $sort,
			'order'                => $order,
			'start'                => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'                => $this->config->get('config_limit_admin')
		);

		$orderTotal = $this->model_resource_transaction->getTotalTransactions($filterData);

		$results = $this->model_resource_transaction->getTransactions($filterData);

		// Not sure if this is the right model to be dumping everything... should really just call the appropriate service
		$data['purchase_order_statuses'] = $this->model_resource_transaction->getPurchaseOrderStatuses();

		if ($results != null) {
			$this->load->model('vendor/vendor');

			foreach ($results as $result) {
				$vendor = false;
				if (isset($result['vendor']) && isset($result['vendor']['vendorId'])) {
					$vendor = array();
					$vendor['vendor_id'] = $result['vendor']['vendorId'];
					// Shouldn't be an purchase_order without a customer ID
					if (is_numeric($vendor['vendor_id'])) {
						$vendor = $this->model_vendor_vendor->getVendor($vendor['vendor_id']);
					}
				}

				// TODO: This is temporary implementation for customers, I think I'm going to get getTransactions return unserialized entities
				$status = $this->model_resource_transaction->getPurchaseOrderStatus($result['purchase_order_id']);

				// TODO: I am using an anon func to do this in getForm... change to use convertDateArray
				$purchaseOrderDate = '';
				if (isset($result['purchase_order_date'])) {
					$purchaseOrderDate = date($this->language->get('date_format_short'), self::convertDateArray($result['purchase_order_date'])->getTimestamp());
				}

				$dueDate = '';
				if (isset($result['due_date'])) {
					$dueDate = date($this->language->get('date_format_short'), self::convertDateArray($result['due_date'])->getTimestamp());
				}

				$data['purchase_orders'][] = array(
					'transaction_id'    => $result['transaction_id'],
					'purchase_order_id'    => $result['purchase_order_id'],
					'purchase_order_no'    => $result['purchase_order_no'],
					'order_id'      => (isset($result['oc_entity_id'])) ? $result['oc_entity_id'] : '',
					'order_url'     => (isset($result['oc_entity_id'])) ? $this->url->link('sale/order/info', 'token=' . $this->session->data['token'] . '&order_id=' . $result['oc_entity_id'] . $url, 'SSL') : '',
					'feed_id'		=> $result['feed_id'],
					'customer_name' => htmlspecialchars_decode(implode(' ', [$result['firstname'], $result['lastname']])),
					'vendor'      => $vendor,
					'email'         => $result['bill_email'],
					//'status'        => (isset($status) && isset($status['name'])) ? $status['name'] : 'N/A',
					// TODO: This is a temporary status implementation
					'status'        => (isset($result['feed_id']) && (int)$result['feed_id'] > 0) ? 'Posted' : 'Not Posted',
					'total'         => $this->currency->format($result['total'], $result['currency_code'], $result['currency_value']),
					'date_added'    => date($this->language->get('date_format_short'), strtotime($result['date_added'])),
					'date_modified' => date($this->language->get('date_format_short'), strtotime($result['date_modified'])),
					'purchase_order_date'  => $purchaseOrderDate, // TODO: I am using an anon func to do this in getForm... change to use convertDateArray
					'due_date'      => $dueDate, // TODO: I am using an anon func to do this in getForm... change to use convertDateArray
					'date_estimated'      => $dueDate, // TODO: I am using an anon func to do this in getForm... change to use convertDateArray
					'shipping_code' => $result['shipping_code'],
					'view'          => $this->url->link('transaction/purchase_order/purchase_order', 'token=' . $this->session->data['token'] . '&purchase_order_id=' . $result['purchase_order_id'] . $url, 'SSL'),
					'edit'          => $this->url->link('transaction/purchase_order/edit', 'token=' . $this->session->data['token'] . '&purchase_order_id=' . $result['purchase_order_id'] . $url, 'SSL'),
					'delete'        => $this->url->link('transaction/purchase_order/delete', 'token=' . $this->session->data['token'] . '&purchase_order_id=' . $result['purchase_order_id'] . $url, 'SSL')
				);
			}
		} else {
			// Error
		}


		$data['token'] = $this->session->data['token'];

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$url = '';

		if (isset($this->request->get['filter_purchase_order_id'])) {
			$url .= '&filter_purchase_order_id=' . $this->request->get['filter_purchase_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
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

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['sort_order'] = $this->url->link('transaction/purchase_order', 'token=' . $this->session->data['token'] . '&sort=o.purchase_order_id' . $url, 'SSL');
		$data['sort_customer'] = $this->url->link('transaction/purchase_order', 'token=' . $this->session->data['token'] . '&sort=customer' . $url, 'SSL');
		$data['sort_status'] = $this->url->link('transaction/purchase_order', 'token=' . $this->session->data['token'] . '&sort=status' . $url, 'SSL');
		$data['sort_total'] = $this->url->link('transaction/purchase_order', 'token=' . $this->session->data['token'] . '&sort=o.total' . $url, 'SSL');
		$data['sort_date_added'] = $this->url->link('transaction/purchase_order', 'token=' . $this->session->data['token'] . '&sort=o.date_added' . $url, 'SSL');
		$data['sort_date_modified'] = $this->url->link('transaction/purchase_order', 'token=' . $this->session->data['token'] . '&sort=o.date_modified' . $url, 'SSL');

		$url = '';

		if (isset($this->request->get['filter_purchase_order_id'])) {
			$url .= '&filter_purchase_order_id=' . $this->request->get['filter_purchase_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
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

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $orderTotal;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('transaction/purchase_order', 'token=' . $this->session->data['token'] . $url . '&page={page}', 'SSL');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($orderTotal) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($orderTotal - $this->config->get('config_limit_admin'))) ? $orderTotal : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $orderTotal, ceil($orderTotal / $this->config->get('config_limit_admin')));

		$data['filter_purchase_order_id'] = $filterPurchaseOrderId;
		$data['filter_customer'] = $filterVendor;
		$data['filter_purchase_order_status'] = $filterPurchaseOrderStatus;
		$data['filter_total'] = $filterTotal;
		$data['filter_date_added'] = $filter_date_added;
		$data['filter_date_modified'] = $filterDateModified;

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		// Get purchase_order statuses
		// Not sure if this is the right model to be dumping everything... should really just call the appropriate service
		$data['purchase_order_statuses'] = $this->model_resource_transaction->getPurchaseOrderStatuses();

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('transaction/purchase_order_list.tpl', $data));
	}

	public function getForm($action = 'add') {
		$this->load->model('vendor/vendor');
		$this->load->model('catalog/product');

		$purchaseOrderId = (isset($this->request->get['purchase_order_id'])) ? $this->request->get['purchase_order_id'] : null;

		$data = array_merge(
			$this->language->load('module/payment_processor'),
			$this->load->language('transaction/purchase_order')
		);

		$data['action'] = $action;

		if ($action == 'add') {
			$this->document->setTitle($this->language->get('heading_title_add'));
			$data['text_form'] = $this->language->get('text_add');
		} elseif ($action == 'edit') {
			$this->document->setTitle($this->language->get('heading_title_edit'));
			$data['text_form'] = $this->language->get('text_edit');
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['heading_title_add'] = $this->language->get('heading_title_add');
		$data['heading_title_edit'] = $this->language->get('heading_title_edit');

		$data['token'] = $this->session->data['token'];

		$this->error = (isset($this->session->data['ipp_error'])) ? array_merge($this->session->data['ipp_error']) : $this->error;

		if (isset($this->error['warning'])) {
			if (is_array($this->error['warning'])) {
				$warning = $this->error['warning'];

				$msg = '<b>' . $warning['error'] . '</b> ' . $warning['message'] . '.<br>';
				$msg .= '<ul style="list-style-type: none; padding-left: 12px"><li><b>' . $warning['code'] . '</b>: ' . $warning['detail'] . '</li></ul>';

				$data['error_warning'] = $msg;
			} else {
				$data['error_warning'] = $this->error['warning'];
			}
		} else {
			$data['error_warning'] = '';

			if (isset($this->session->data['success'])) {
				$data['success'] = $this->session->data['success'];

				unset($this->session->data['success']);
			} else {
				$data['success'] = '';
			}
		}

		if (isset($this->request->get['filter_date_start'])) {
			$filterDateStart = $this->request->get['filter_date_start'];
		} else {
			$filterDateStart = '2015-12-30'; // TODO: Set to one month before last sale
		}

		if (isset($this->request->get['filter_date_end'])) {
			$filterDateEnd = $this->request->get['filter_date_end'];
		} else {
			$filterDateEnd = '2016-02-29'; // TODO: Set to date of last sale
		}

		$url = '';

		if (isset($this->request->get['filter_purchase_order_id'])) {
			$url .= '&filter_purchase_order_id=' . $this->request->get['filter_purchase_order_id'];
		}

		if (isset($this->request->get['filter_customer'])) {
			$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
		}

		if (isset($this->request->get['filter_order_status'])) {
			$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
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

		if (isset($this->request->get['filter_date_start'])) {
			$url .= '&filter_date_start=' . $this->request->get['filter_date_start'];
		} else {
			$url .= 'filter_date_start=2015-12-30';
		}

		if (isset($this->request->get['filter_date_end'])) {
			$url .= '&filter_date_end=' . $this->request->get['filter_date_end'];
		} else {
			$url .= '&filter_date_end=2016-02-29';
		}

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
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
			'href' => $this->url->link('transaction/purchase_order', 'token=' . $this->session->data['token'] . $url, 'SSL')
		);

		if (isset($this->request->get['purchase_order_id'])) {
			$data['shipping'] = $this->url->link('transaction/purchase_order/shipping', 'token=' . $this->session->data['token'] . '&purchase_order_id=' . (int)$this->request->get['purchase_order_id'], 'SSL'); // TODO: This should be using order id instead?
			$data['purchase_order'] = $this->url->link('transaction/purchase_order/purchase_order', 'token=' . $this->session->data['token'] . '&purchase_order_id=' . (int)$this->request->get['purchase_order_id'], 'SSL');
		} else {
			$data['shipping'] = false;
			$data['purchase_order'] = false;
		}

		$data['cancel'] = $this->url->link('transaction/purchase_order', 'token=' . $this->session->data['token'] . $url, 'SSL');

		$data['filter_date_start'] = $filterDateStart;
		$data['filter_date_end'] = $filterDateEnd;

		if (isset($this->request->get['purchase_order_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$purchaseOrderInfo = $this->model_resource_transaction->getTransaction($purchaseOrderId);
		}

		// TODO: Static method in utility class
		$checkIfIsDateArray = function($arr) {
			$isDate = (array_key_exists('date', $arr) && array_key_exists('timezone_type', $arr) && array_key_exists('timezone', $arr));

			return $isDate;
		};

		// TODO: Static method in utility class
		$convertDateArray = function($arr) {
			$date = new DateTime($arr['date']);
			$date->setTimezone(new DateTimeZone($arr['timezone']));

			return $date->format('Y-m-d');
		};

		// Clear session and get any existing line items
		$this->lines->clear();

		if (!empty($purchaseOrderInfo)) {
			foreach ($purchaseOrderInfo as $field => $val) {
				if (is_array($val) && $checkIfIsDateArray($val)) {
					$date = $convertDateArray($val);
					$data[$field] = $date;
				} else {
					$data[$field] = $val;
				}
			}

			unset($data['custom_field']);
			$data['account_custom_field'] = (isset($purchaseOrderInfo['custom_field'])) ? $purchaseOrderInfo['custom_field'] : null;

			// TODO: Description, and other line types
			$data['lines'] = array();

			$lines = $this->model_resource_transaction->getLineItems($purchaseOrderId);
			//var_dump($lines);
			// We need to set lines to session
			foreach ($lines as $line) {
				$options = array();
				if (isset($line['order_id'])) {
					$options = $this->model_sale_order->getOrderOptions($data['order_id'], $line['order_product_id']);
				}

				// TODO: Modify whatever's converting the entity to an array so we don't have to do this
				// Either that or make a utility method to get assoc keys
				$productId = null;
				if (isset($line['product']) && isset($line['product']['productId'])) {
					$productId = $line['product']['productId'];
				}

				$tax_class_id = null;
				if (isset($line['tax_class']) && isset($line['tax_class']['taxClassId'])) {
					$tax_class_id = $line['tax_class']['taxClassId'];
				}

				$item = array(
					'product_id' 		=> $productId,
					'line_id'	 		=> $line['line_id'],
					'detail_type'	 	=> $line['detail_type'],
					'purchase_order_id'	 	=> $purchaseOrderId,
					'order_product_id'	=> $line['order_product_id'],
					'description'	 	=> $line['description'],
					'name'       		=> $line['name'],
					'model'      		=> $line['model'],
					'option'     		=> (isset($line['purchase_order_id'])) ? $this->model_sale_order->getOrderOptions($data['purchase_order_id'], $line['order_product_id']) : null,
					'quantity'  		=> (int)$line['quantity'],
					'price'      		=> (float)$line['price'],
					'revenue'     		=> (float)$line['revenue'],
					'royalty'     		=> (float)$line['royalty'],
					'total'     		=> (float)$line['total'],
					'tax'     			=> (float)$line['tax'],
					'tax_class_id'     	=> $tax_class_id,
					'reward'     		=> $line['reward']
				);

				$data['lines'][] = $item;

				$this->addLine($item, $item['quantity'], $item['price']);
			}

			$this->load->model('vendor/vendor'); // Load customer and customer addresses
			$vendor = $this->model_resource_transaction->getVendor($data['purchase_order_id']);

			$data['addresses'] = array();
			$vendorData['vendor_id'] = 0;
			$vendorData['vendor'] = '';
			$vendorData['account_custom_field'] = '';

			if (isset($vendor['vendor_id'])) {
				$data['addresses'] = $this->model_vendor_vendor->getAddresses($vendor['vendor_id']);

				// Clean fields - I noticed special characters in some of the purchase_order address fields
				$idx = 0;
				foreach ($data['addresses'] as $address) {
					foreach ($address as $field => $val) {
						$data['addresses'][$idx][$field] = htmlspecialchars_decode($val);
					}

					$idx++;
				}

				unset ($idx);

				$vendorData['vendor_id'] = (isset($purchaseOrderInfo['vendor_id'])) ? $purchaseOrderInfo['vendor_id'] : $vendor['vendor_id'];
				$vendorData['vendor'] = (isset($purchaseOrderInfo['fullname'])) ? htmlspecialchars_decode($purchaseOrderInfo['fullname']) : htmlspecialchars_decode($vendor['fullname']);
				$vendorData['account_custom_field'] = $vendor['custom_field'];
			}

			// Set customer display fields
			$vendorData['firstname'] = (isset($purchaseOrderInfo['firstname'])) ? $purchaseOrderInfo['firstname'] : $vendor['firstname']; // Could be possible that the customer's name changed
			$vendorData['lastname'] = (isset($purchaseOrderInfo['lastname'])) ? $purchaseOrderInfo['lastname'] : $vendor['lastname'];
			$vendorData['bill_email'] = (isset($purchaseOrderInfo['bill_email'])) ? $purchaseOrderInfo['bill_email'] : $vendor['bill_email'];
			$vendorData['bill_telephone'] = (isset($purchaseOrderInfo['bill_telephone'])) ? $purchaseOrderInfo['bill_telephone'] : $vendor['telephone'];
			$vendorData['bill_fax'] = (isset($purchaseOrderInfo['bill_fax'])) ? $purchaseOrderInfo['bill_fax'] : $vendor['fax'];

			ModelOcSessionVendor::setVendor($this, $vendorData);
			// TODO: Set payment and shipping

			$data = array_merge($data, $vendorData);

			$mapPurchaseOrderAddress = function ($purchaseOrder, &$data, $type, $prefix = '') {
				if (!in_array($type, array('bill', 'ship'))) {
					return false;
				}

				// Keys must match session keys set via api/payment/address and api/shipping/address
				$keys = array(
					'firstname' => htmlspecialchars_decode($purchaseOrder[$type . '_addr_firstname']),
					'lastname' => htmlspecialchars_decode($purchaseOrder[$type . '_addr_lastname']),
					'company' => htmlspecialchars_decode($purchaseOrder[$type . '_addr_company']),
					'address_1' => htmlspecialchars_decode($purchaseOrder[$type . '_addr_line1']),
					'address_2' => htmlspecialchars_decode($purchaseOrder[$type . '_addr_line2']),
					'postcode' => $purchaseOrder[$type . '_addr_postcode'],
					'city' => htmlspecialchars_decode($purchaseOrder[$type . '_addr_city']),
					'zone_id' => $purchaseOrder[$type . '_addr_zone_id'],
					'country_id' => $purchaseOrder[$type . '_addr_country_id']
				);

				foreach ($keys as $key => $value) {
					$data[$prefix . $key] = $value;
				}
			};

			// Field names in view do not match exactly to the purchase order table fields, and I'd rather reuse the logic anyway - typing sucks!
			$mapPurchaseOrderAddress($purchaseOrderInfo, $data, 'bill', 'payment_');
			$mapPurchaseOrderAddress($purchaseOrderInfo, $data, 'ship', 'shipping_');

			// Set map addresses and set them to session
			$mapPurchaseOrderAddress($purchaseOrderInfo, $this->request->post, 'bill');
			$payment = $this->load->controller('api/payment/address', array('export' => false));

			$mapPurchaseOrderAddress($purchaseOrderInfo, $this->request->post, 'ship');
			$shipping = $this->load->controller('api/shipping/address', array('export' => false));

			$data['payment_custom_field'] = (isset($purchaseOrderInfo['payment_custom_field'])) ? $purchaseOrderInfo['payment_custom_field'] : null;
			$data['payment_method'] = $purchaseOrderInfo['payment_method'];
			$data['payment_code'] = $purchaseOrderInfo['payment_code'];

			$data['shipping_custom_field'] = (isset($purchaseOrderInfo['shipping_custom_field'])) ? $purchaseOrderInfo['shipping_custom_field'] : null;
			$data['shipping_method'] = $purchaseOrderInfo['shipping_method'];
			$data['shipping_code'] = $purchaseOrderInfo['shipping_code'];

			//var_dump($purchaseOrderInfo);
			if (isset($this->session->data['purchase_order_info'])) {
				$purchaseOrderInfo = array_merge($purchaseOrderInfo, $this->session->data['purchase_order_info']);
			}

			if (isset($purchaseOrderInfo['payment_address_format'])) {
				$format = $purchaseOrderInfo['payment_address_format'];
				$data['payment_address_format'] = $format;
			} else {
				$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{city} {zone}' . "\n" . '{postcode} {country}';
				$data['payment_address_format'] = '{firstname} {lastname}\n{company}\n{address_1}\n{city} {zone}\n{postcode} {country}';
			}

			$data['payment_address'] = self::formatPaymentAddress($format, $purchaseOrderInfo, false, false);

			if (isset($purchaseOrderInfo['shipping_address_format'])) {
				$format = $purchaseOrderInfo['shipping_address_format'];
				$data['shipping_address_format'] = $format;
			} else {
				$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{city} {zone}' . "\n" . '{postcode} {country}';
				$data['shipping_address_format'] = '{firstname} {lastname}\n{company}\n{address_1}\n{city} {zone}\n{postcode} {country}';
			}

			$data['shipping_address'] = self::formatShippingAddress($format, $purchaseOrderInfo, false, false);

			// Add vouchers to the API
			$data['order_vouchers'] = $this->model_sale_order->getOrderVouchers($data['purchase_order_id']);

			$data['coupon'] = '';
			$data['voucher'] = '';
			$data['reward'] = '';

			$data['order_totals'] = array();

			$orderTotals = $this->model_sale_order->getOrderTotals($data['purchase_order_id']);

			foreach ($orderTotals as $orderTotal) {
				// If coupon, voucher or reward points
				$start = strpos($orderTotal['title'], '(') + 1;
				$end = strrpos($orderTotal['title'], ')');

				if ($start && $end) {
					if ($orderTotal['code'] == 'coupon') {
						$data['coupon'] = substr($orderTotal['title'], $start, $end - $start);
					}

					if ($orderTotal['code'] == 'voucher') {
						$data['voucher'] = substr($orderTotal['title'], $start, $end - $start);
					}

					if ($orderTotal['code'] == 'reward') {
						$data['reward'] = substr($orderTotal['title'], $start, $end - $start);
					}
				}
			}

			$data['order_status_id'] = (isset($purchaseOrderInfo['order_status_id'])) ? : null;
			$data['purchase_order_status_id'] = (isset($purchaseOrderInfo['purchase_order_status_id'])) ? : null;
			$data['customer_memo'] = (isset($purchaseOrderInfo['customer_memo'])) ? $purchaseOrderInfo['customer_memo'] : null;
			$data['statement_memo'] = (isset($purchaseOrderInfo['statement_memo'])) ? $purchaseOrderInfo['statement_memo'] : null;
			$data['affiliate_id'] = (isset($purchaseOrderInfo['affiliate_id'])) ? $purchaseOrderInfo['affiliate_id'] : null;
			$data['affiliate'] = (isset($purchaseOrderInfo['affiliate_firstname']) && isset($purchaseOrderInfo['affiliate_firstname'])) ? $purchaseOrderInfo['affiliate_firstname'] . ' ' . $purchaseOrderInfo['affiliate_lastname'] : '';
			$data['currency_code'] = (isset($purchaseOrderInfo['currency_code'])) ? $purchaseOrderInfo['currency_code'] : '';
		} else {
			$data['purchase_order_id'] = '';
			$data['purchase_order_no'] = '';
			$data['store_id'] = '';
			$data['order_id'] = '';

			$date = new DateTime();
			$data['purchase_order_date'] = $date->format('Y-m-d');
			$data['due_date'] = $date->modify('+1 month')->format('Y-m-d');

			$data['firstname'] = '';
			$data['lastname'] = '';
			$data['email'] = '';
			$data['telephone'] = '';
			$data['fax'] = '';

			$data['addresses'] = array();
			$data['vendor'] = null;
			$data['vendor_id'] = '';
			$data['firstname'] = ''; // Could be possible that the customer's name changed
			$data['lastname'] = '';
			$data['email'] = '';
			$data['telephone'] = '';
			$data['fax'] = '';

			$data['bill_email'] = '';
			$data['bill_telephone'] = '';
			$data['bill_fax'] = '';

			$data['account_custom_field'] = null;

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

			$data['payment_address'] = '';
			$data['shipping_address'] = '';

			if (isset($purchaseOrderInfo['payment_address_format'])) {
				$format = $purchaseOrderInfo['payment_address_format'];
				$data['payment_address_format'] = $format;
			} else {
				$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{city} {zone}' . "\n" . '{postcode} {country}';
				$data['payment_address_format'] = '{firstname} {lastname}\n{company}\n{address_1}\n{city} {zone}\n{postcode} {country}';
			}

			if (isset($purchaseOrderInfo['shipping_address_format'])) {
				$format = $purchaseOrderInfo['shipping_address_format'];
				$data['shipping_address_format'] = $format;
			} else {
				$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{city} {zone}' . "\n" . '{postcode} {country}';
				$data['shipping_address_format'] = '{firstname} {lastname}\n{company}\n{address_1}\n{city} {zone}\n{postcode} {country}';
			}

			$data['lines'] = array();
			$data['order_vouchers'] = array();
			$data['order_totals'] = array();

			$data['order_status_id'] = $this->config->get('config_order_status_id');
			$data['customer_memo'] = '';
			$data['statement_memo'] = '';
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

		$filterData = array(
			'sort'  => 'cf.sort_order',
			'order' => 'ASC'
		);

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		// Get purchase order statuses
		// Not sure if this is the right model to be dumping everything... should really just call the appropriate service
		$data['purchase_order_statuses'] = $this->model_resource_transaction->getPurchaseOrderStatuses();

		$this->load->model('localisation/country');

		$data['countries'] = $this->model_localisation_country->getCountries();

		$this->load->model('localisation/currency');

		$data['currencies'] = $this->model_localisation_currency->getCurrencies();

		$this->load->model('localisation/tax_class');

		$data['tax_classes'] = $this->model_localisation_tax_class->getTaxClasses();

		$data['voucher_min'] = $this->config->get('config_voucher_min');

		$this->load->model('sale/voucher_theme');

		$data['voucher_themes'] = $this->model_sale_voucher_theme->getVoucherThemes();

		//$this->load->controller('report/product_purchased');

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('transaction/purchase_order_form.tpl', $data));
	}

	protected function addLine($data = null, $quantity = null, $price = null) {
		// Determine appropriate line type
		$productId = null;
		$product = null;

		if ($data != null) {
			$this->load->model('catalog/product');

			$type = 'SalesItemLineDetail';
			$types = array(
				'SalesItemLineDetail',
				'DescriptionItemLineDetail',
				'DescriptionOnlyLineDetail',
				'CommissionLineDetail');

			if (isset($data['detail_type']) && !empty($data['detail_type'])) {
				if (in_array($data['detail_type'], $types)) {
					$type = $data['detail_type'];
				}
			}

			if ($type == 'SalesItemLineDetail') {
				if (is_numeric($data)) {
					$productId = (int)$productId;
				} elseif (is_array($data)) {
					$productId = (isset($data['product_id'])) ? $data['product_id'] : $productId;
				}

				$product = $this->model_catalog_product->getProduct($productId);

				if ($product != null) {
					$price = (empty($price)) ? $product['price'] : $price;
					$quantity = (empty($quantity)) ? 1 : $quantity;

					$this->lines->add($product, $quantity, array(), $price);
				}
			}

			if ($type == 'DescriptionOnlyLineDetail' || $type == 'DescriptionItemLineDetail') {
				if (!empty($data['description']) xor !empty($data['name'])) {
					$description = (!empty($data['name'])) ? $data['name'] : $data['description'];

					if ($quantity == null && $price == null) {
						// If no price or quantity necessary, create a DescriptionOnly line item
						$this->lines->addDescription($description);
					} else {
						$taxClassId = (isset($data['tax_class_id'])) ? $data['tax_class_id'] : null;
						$tax = (isset($data['tax'])) ? $data['tax'] : 0.0000;
						$this->lines->addDescriptionItem($description, $quantity, $price, $taxClassId, $tax);
					}
				}
			}

			if ($type == 'CommissionLineDetail') {
				if (!empty($data['description']) xor !empty($data['name'])) {
					$description = (!empty($data['name'])) ? $data['name'] : $data['description'];

					$this->lines->addCommission($description, 1, $data);
				}
			}
		}
	}

	public function info() {
		$this->load->model('sale/order');

		if (isset($this->request->get['purchase_order_id'])) {
			$purchaseOrderId = $this->request->get['purchase_order_id'];
		} else {
			$purchaseOrderId = 0;
		}

		$purchaseOrderInfo = $this->model_sale_order->getOrder($purchaseOrderId);

		if ($purchaseOrderInfo) {
			$data = $this->load->language('transaction/purchase_order');

			$this->document->setTitle($this->language->get('heading_title'));

			$data['heading_title'] = $this->language->get('heading_title');

			$data['token'] = $this->session->data['token'];

			$url = '';

			if (isset($this->request->get['filter_purchase_order_id'])) {
				$url .= '&filter_purchase_order_id=' . $this->request->get['filter_purchase_order_id'];
			}

			if (isset($this->request->get['filter_customer'])) {
				$url .= '&filter_customer=' . urlencode(html_entity_decode($this->request->get['filter_customer'], ENT_QUOTES, 'UTF-8'));
			}

			if (isset($this->request->get['filter_order_status'])) {
				$url .= '&filter_order_status=' . $this->request->get['filter_order_status'];
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

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
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
				'href' => $this->url->link('transaction/purchase_order', 'token=' . $this->session->data['token'] . $url, 'SSL')
			);

			$data['shipping'] = $this->url->link('transaction/purchase_order/shipping', 'token=' . $this->session->data['token'] . '&purchase_order_id=' . (int)$this->request->get['purchase_order_id'], 'SSL');
			$data['purchase_order'] = $this->url->link('transaction/purchase_order/purchase_order', 'token=' . $this->session->data['token'] . '&purchase_order_id=' . (int)$this->request->get['purchase_order_id'], 'SSL');
			$data['edit'] = $this->url->link('transaction/purchase_order/edit', 'token=' . $this->session->data['token'] . '&purchase_order_id=' . (int)$this->request->get['purchase_order_id'], 'SSL');
			$data['cancel'] = $this->url->link('transaction/purchase_order', 'token=' . $this->session->data['token'] . $url, 'SSL');

			$data['purchase_order_id'] = $this->request->get['purchase_order_id'];
			$data['purchase_order_id'] = $purchaseOrderInfo['purchase_order_id'];

			if ($purchaseOrderInfo['purchase_order_no']) {
				$data['purchase_order_no'] = $purchaseOrderInfo['purchase_order_prefix'] . $purchaseOrderInfo['purchase_order_no'];
			} else {
				$data['purchase_order_no'] = '';
			}

			$data['store_name'] = $purchaseOrderInfo['store_name'];
			$data['store_url'] = $purchaseOrderInfo['store_url'];
			$data['firstname'] = $purchaseOrderInfo['firstname'];
			$data['lastname'] = $purchaseOrderInfo['lastname'];

			if ($purchaseOrderInfo['purchase_order_info_id']) {
				$data['purchase_order_info'] = $this->url->link('sale/purchase_order_info/edit', 'token=' . $this->session->data['token'] . '&purchase_order_info_id=' . $purchaseOrderInfo['purchase_order_info_id'], 'SSL');
			} else {
				$data['purchase_order_info'] = '';
			}

			$data['email'] = $purchaseOrderInfo['email'];
			$data['telephone'] = $purchaseOrderInfo['telephone'];
			$data['fax'] = $purchaseOrderInfo['fax'];

			// Uploaded files
			$this->load->model('tool/upload');

			$data['comment'] = nl2br($purchaseOrderInfo['comment']);
			$data['shipping_method'] = $purchaseOrderInfo['shipping_method'];
			$data['payment_method'] = $purchaseOrderInfo['payment_method'];
			$data['total'] = $this->currency->format($purchaseOrderInfo['total'], $purchaseOrderInfo['currency_code'], $purchaseOrderInfo['currency_value']);

			$this->load->model('sale/purchase_order_info');

			$data['reward'] = $purchaseOrderInfo['reward'];

			// TODO: Replace with order id from data
			$data['reward_total'] = $this->model_sale_purchase_order_info->getTotalVendorRewardsByOrderId($this->request->get['purchase_order_id']);

			$data['affiliate_firstname'] = $purchaseOrderInfo['affiliate_firstname'];
			$data['affiliate_lastname'] = $purchaseOrderInfo['affiliate_lastname'];

			if ($purchaseOrderInfo['affiliate_id']) {
				$data['affiliate'] = $this->url->link('marketing/affiliate/edit', 'token=' . $this->session->data['token'] . '&affiliate_id=' . $purchaseOrderInfo['affiliate_id'], 'SSL');
			} else {
				$data['affiliate'] = '';
			}

			$data['commission'] = $this->currency->format($purchaseOrderInfo['commission'], $purchaseOrderInfo['currency_code'], $purchaseOrderInfo['currency_value']);

			$this->load->model('marketing/affiliate');

			// TODO: Replace with order id from data
			$data['commission_total'] = $this->model_marketing_affiliate->getTotalTransactionsByOrderId($this->request->get['purchase_order_id']);

			$this->load->model('localisation/order_status');

			// TODO: Fix me
			/*$order_status_info = $this->model_localisation_order_status->getPurchaseOrderStatus($purchaseOrderInfo['order_status_id']);

			if ($order_status_info) {
				$data['order_status'] = $order_status_info['name'];
			} else {
				$data['order_status'] = '';
			}*/

			$data['ip'] = $purchaseOrderInfo['ip'];
			$data['forwarded_ip'] = $purchaseOrderInfo['forwarded_ip'];
			$data['user_agent'] = $purchaseOrderInfo['user_agent'];
			$data['accept_language'] = $purchaseOrderInfo['accept_language'];
			$data['date_added'] = date($this->language->get('date_format_short'), strtotime($purchaseOrderInfo['date_added']));
			$data['date_modified'] = date($this->language->get('date_format_short'), strtotime($purchaseOrderInfo['date_modified']));

			// Payment
			$data['payment_firstname'] = $purchaseOrderInfo['payment_firstname'];
			$data['payment_lastname'] = $purchaseOrderInfo['payment_lastname'];
			$data['payment_company'] = $purchaseOrderInfo['payment_company'];
			$data['payment_address_1'] = $purchaseOrderInfo['payment_address_1'];
			$data['payment_address_2'] = $purchaseOrderInfo['payment_address_2'];
			$data['payment_city'] = $purchaseOrderInfo['payment_city'];
			$data['payment_postcode'] = $purchaseOrderInfo['payment_postcode'];
			$data['payment_zone'] = $purchaseOrderInfo['payment_zone'];
			$data['payment_zone_code'] = $purchaseOrderInfo['payment_zone_code'];
			$data['payment_country'] = $purchaseOrderInfo['payment_country'];

			// Custom fields
			$data['payment_custom_fields'] = array();

			// Shipping
			$data['shipping_firstname'] = $purchaseOrderInfo['shipping_firstname'];
			$data['shipping_lastname'] = $purchaseOrderInfo['shipping_lastname'];
			$data['shipping_company'] = $purchaseOrderInfo['shipping_company'];
			$data['shipping_address_1'] = $purchaseOrderInfo['shipping_address_1'];
			$data['shipping_address_2'] = $purchaseOrderInfo['shipping_address_2'];
			$data['shipping_city'] = $purchaseOrderInfo['shipping_city'];
			$data['shipping_postcode'] = $purchaseOrderInfo['shipping_postcode'];
			$data['shipping_zone'] = $purchaseOrderInfo['shipping_zone'];
			$data['shipping_zone_code'] = $purchaseOrderInfo['shipping_zone_code'];
			$data['shipping_country'] = $purchaseOrderInfo['shipping_country'];

			$data['shipping_custom_fields'] = array();

			$data['products'] = array();

			$products = $this->model_sale_order->getOrderProducts($data['purchase_order_id']);

			foreach ($products as $product) {
				$option_data = array();

				$options = $this->model_sale_order->getOrderOptions($data['purchase_order_id'], $product['order_product_id']);

				foreach ($options as $option) {
					if ($option['type'] != 'file') {
						$option_data[] = array(
							'name'  => $option['name'],
							'value' => $option['value'],
							'type'  => $option['type']
						);
					} else {
						$uploadInfo = $this->model_tool_upload->getUploadByCode($option['value']);

						if ($uploadInfo) {
							$option_data[] = array(
								'name'  => $option['name'],
								'value' => $uploadInfo['name'],
								'type'  => $option['type'],
								'href'  => $this->url->link('tool/upload/download', 'token=' . $this->session->data['token'] . '&code=' . $uploadInfo['code'], 'SSL')
							);
						}
					}
				}

				$data['products'][] = array(
					'order_product_id' => $product['order_product_id'],
					'product_id'       => $product['product_id'],
					'name'    	 	   => $product['name'],
					'model'    		   => $product['model'],
					'option'   		   => $option_data,
					'quantity'		   => $product['quantity'],
					'price'    		   => $this->currency->format($product['price'] + ($this->config->get('config_tax') ? $product['tax'] : 0), $purchaseOrderInfo['currency_code'], $purchaseOrderInfo['currency_value']),
					'total'    		   => $this->currency->format($product['total'] + ($this->config->get('config_tax') ? ($product['tax'] * $product['quantity']) : 0), $purchaseOrderInfo['currency_code'], $purchaseOrderInfo['currency_value']),
					'href'     		   => $this->url->link('catalog/product/edit', 'token=' . $this->session->data['token'] . '&product_id=' . $product['product_id'], 'SSL')
				);
			}

			$data['vouchers'] = array();

			$vouchers = $this->model_sale_order->getOrderVouchers($data['purchase_order_id']);

			foreach ($vouchers as $voucher) {
				$data['vouchers'][] = array(
					'description' => $voucher['description'],
					'amount'      => $this->currency->format($voucher['amount'], $purchaseOrderInfo['currency_code'], $purchaseOrderInfo['currency_value']),
					'href'        => $this->url->link('sale/voucher/edit', 'token=' . $this->session->data['token'] . '&voucher_id=' . $voucher['voucher_id'], 'SSL')
				);
			}

			$data['totals'] = array();

			$totals = $this->model_sale_order->getOrderTotals($data['purchase_order_id']);

			foreach ($totals as $total) {
				$data['totals'][] = array(
					'title' => $total['title'],
					'text'  => $this->currency->format($total['value'], $purchaseOrderInfo['currency_code'], $purchaseOrderInfo['currency_value']),
				);
			}

			$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

			$data['order_status_id'] = $purchaseOrderInfo['order_status_id'];

			// Unset any past sessions this page date_added for the api to work.
			unset($this->session->data['cookie']);

			// Set up the API session
			if ($this->user->hasPermission('modify', 'transaction/purchase_order')) {
				$this->load->model('user/api');

				$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

				if ($api_info) {
					$curl = curl_init();

					// Set SSL if required
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
						$data['error_warning'] = sprintf($this->language->get('error_curl'), curl_error($curl), curl_errno($curl));
					} else {
						$response = json_decode($json, true);
					}

					if (isset($response['cookie'])) {
						$this->session->data['cookie'] = $response['cookie'];
					}
				}
			}

			if (isset($response['cookie'])) {
				$this->session->data['cookie'] = $response['cookie'];
			} else {
				$data['error_warning'] = $this->language->get('error_permission');
			}

			$data['payment_action'] = $this->load->controller('payment/' . $purchaseOrderInfo['payment_code'] . '/action');

			$data['frauds'] = array();

			$this->load->model('extension/extension');

			$extensions = $this->model_extension_extension->getInstalled('fraud');

			foreach ($extensions as $extension) {
				if ($this->config->get($extension . '_status')) {
					$this->load->language('fraud/' . $extension);

					$content = $this->load->controller('fraud/' . $extension . '/order');

					if ($content) {
						$data['frauds'][] = array(
							'code'    => $extension,
							'title'   => $this->language->get('heading_title'),
							'content' => $content
						);
					}
				}
			}

			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view('transaction/purchase_order_info.tpl', $data));
		} else {
			$this->load->language('error/not_found');

			$this->document->setTitle($this->language->get('heading_title'));

			$data['heading_title'] = $this->language->get('heading_title');

			$data['text_not_found'] = $this->language->get('text_not_found');

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('error/not_found', 'token=' . $this->session->data['token'], 'SSL')
			);

			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['footer'] = $this->load->controller('common/footer');

			$this->response->setOutput($this->load->view('error/not_found.tpl', $data));
		}
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'transaction/purchase_order')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function createPurchaseOrderNo() {
		$this->load->language('transaction/purchase_order');

		$json = array();

		if (!$this->user->hasPermission('modify', 'transaction/purchase_order')) {
			$json['error'] = $this->language->get('error_permission');
		} elseif (isset($this->request->get['purchase_order_id'])) {
			if (isset($this->request->get['purchase_order_id'])) {
				$purchaseOrderId = $this->request->get['purchase_order_id'];
			} else {
				$purchaseOrderId = 0;
			}

			$this->load->model('sale/order');

			$purchaseOrderNo = $this->model_sale_order->createPurchaseOrderNo($purchaseOrderId);

			if ($purchaseOrderNo) {
				$json['purchase_order_no'] = $purchaseOrderNo;
			} else {
				$json['error'] = $this->language->get('error_action');
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function country() {
		$json = array();

		$this->load->model('localisation/country');

		$countryInfo = $this->model_localisation_country->getCountry($this->request->get['country_id']);

		if ($countryInfo) {
			$this->load->model('localisation/zone');

			$json = array(
				'country_id'        => $countryInfo['country_id'],
				'name'              => $countryInfo['name'],
				'iso_code_2'        => $countryInfo['iso_code_2'],
				'iso_code_3'        => $countryInfo['iso_code_3'],
				'address_format'    => $countryInfo['address_format'],
				'postcode_required' => $countryInfo['postcode_required'],
				'zone'              => $this->model_localisation_zone->getZonesByCountryId($this->request->get['country_id']),
				'status'            => $countryInfo['status']
			);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	public function history() {
		$this->load->language('transaction/purchase_order');

		$data['text_no_results'] = $this->language->get('text_no_results');

		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_notify'] = $this->language->get('column_notify');
		$data['column_comment'] = $this->language->get('column_comment');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$data['histories'] = array();

		$this->load->model('sale/order');

		$results = $this->model_sale_order->getOrderHistories($this->request->get['purchase_order_id'], ($page - 1) * 10, 10);

		foreach ($results as $result) {
			$data['histories'][] = array(
				'notify'     => $result['notify'] ? $this->language->get('text_yes') : $this->language->get('text_no'),
				'status'     => $result['status'],
				'comment'    => nl2br($result['comment']),
				'date_added' => date($this->language->get('date_format_short'), strtotime($result['date_added']))
			);
		}

		$historyTotal = $this->model_sale_order->getTotalPurchaseOrderHistories($this->request->get['purchase_order_id']);

		$pagination = new Pagination();
		$pagination->total = $historyTotal;
		$pagination->page = $page;
		$pagination->limit = 10;
		$pagination->url = $this->url->link('transaction/purchase_order/history', 'token=' . $this->session->data['token'] . '&purchase_order_id=' . $this->request->get['purchase_order_id'] . '&page={page}', 'SSL');

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($historyTotal) ? (($page - 1) * 10) + 1 : 0, ((($page - 1) * 10) > ($historyTotal - 10)) ? $historyTotal : ((($page - 1) * 10) + 10), $historyTotal, ceil($historyTotal / 10));

		$this->response->setOutput($this->load->view('transaction/purchase_order_history.tpl', $data));
	}

	public function purchase_order() {
		$data = $this->load->language('transaction/purchase_order');

		$data['title'] = $this->language->get('text_purchase_order');

		if ($this->request->server['HTTPS']) {
			$data['base'] = HTTPS_SERVER;
		} else {
			$data['base'] = HTTP_SERVER;
		}

		$data['direction'] = $this->language->get('direction');
		$data['lang'] = $this->language->get('code');

		$this->load->model('sale/order');

		$this->load->model('setting/setting');

		$data['purchase_orders'] = array();

		$purchaseOrders = array();

		if (isset($this->request->post['selected'])) {
			$purchaseOrders = $this->request->post['selected'];
		} elseif (isset($this->request->get['purchase_order_id'])) {
			$purchaseOrders[] = $this->request->get['purchase_order_id'];
		}

		$this->load->model('resource/transaction');
		$tModel = &$this->model_resource_transaction;

		$tService = new TransactionPurchaseOrder($this, 'OcPurchaseOrder');
		$tService->setEntityManager($this->em);
		$tModel->setTransactionType($tService);

		foreach ($purchaseOrders as $purchaseOrderId) {
			$i = $this->model_resource_transaction->getTransaction($purchaseOrderId);

			if ($i) {
				$store = $this->model_setting_setting->getSetting('config', $i['store_id']);

				if ($store) {
					$storeAddress = $store['config_address'];
					$storeEmail = $store['config_email'];
					$storeTelephone = $store['config_telephone'];
					$storeFax = $store['config_fax'];
				} else {
					$storeAddress = $this->config->get('config_address');
					$storeEmail = $this->config->get('config_email');
					$storeTelephone = $this->config->get('config_telephone');
					$storeFax = $this->config->get('config_fax');
				}

				if ($i['purchase_order_no']) {
					$purchaseOrderNo = $i['purchase_order_prefix'] . $i['purchase_order_no'];
				} else {
					$purchaseOrderNo = '';
				}

				$this->load->model('vendor/vendor'); // Load customer and customer addresses
				$c = $this->model_resource_transaction->getVendor($i['purchase_order_id']);

				$i['shipping_method'] = $i['shipping_method'];
				$i['shipping_code'] = $i['shipping_code'];

				if (isset($this->session->data['purchase_order_info'])) {
					$i = array_merge($i, $this->session->data['purchase_order_info']);
				}

				if (isset($i['payment_address_format'])) {
					$format = $i['payment_address_format'];
					$i['payment_address_format'] = $format;
				} else {
					$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{city} {zone}' . "\n" . '{postcode} {country}';
					$i['payment_address_format'] = '{firstname} {lastname}\n{company}\n{address_1}\n{city} {zone}\n{postcode} {country}';
				}

				$i['payment_address'] = self::formatPaymentAddress($format, $i, false, true); // Final "true" sets output to HTML

				if (isset($i['shipping_address_format'])) {
					$format = $i['shipping_address_format'];
					$i['shipping_address_format'] = $format;
				} else {
					$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{city} {zone}' . "\n" . '{postcode} {country}';
					$i['shipping_address_format'] = '{firstname} {lastname}\n{company}\n{address_1}\n{city} {zone}\n{postcode} {country}';
				}

				$i['shipping_address'] = self::formatShippingAddress($format, $i, false, true); // Final "true" sets output to HTML

				$i['order_id'] = (isset($i['oc_entity_id'])) ? $i['oc_entity_id'] : null;

				$this->load->model('tool/upload');

				// This alternate method loads lines from session
				// $this->getLinesFromSession();

				$productData = array();

				$lines = $this->model_resource_transaction->getLineItems($i['purchase_order_id']);
				// We need to set lines to session
				foreach ($lines as $line) {
					$options = array();
					if (isset($line['purchase_order_id'])) {
						$options = $this->model_sale_order->getOrderOptions($i['purchase_order_id'], $line['order_product_id']);
					}

					// TODO: Modify whatever's converting the entity to an array so we don't have to do this
					// Either that or make a utility method to get assoc keys
					$productId = null;
					if (isset($line['product']) && isset($line['product']['productId'])) {
						$productId = $line['product']['productId'];
					}

					$item = array(
						'product_id' 		=> $productId,
						'line_id'	 		=> $line['line_id'],
						'detail_type'	 	=> $line['detail_type'],
						'purchase_order_id'	 	=> $purchaseOrderId,
						'order_product_id'	=> $line['order_product_id'],
						'description'	 	=> $line['description'],
						'name'       		=> $line['name'],
						'model'      		=> $line['model'],
						'option'     		=> $options,
						'quantity'  		=> $line['quantity'],
						'revenue'    		=> (isset($line['revenue'])) ? $line['revenue'] : null, // ?
						//'track'       		=> (isset($line['track'])) ? $line['track'] : null, // ?
						'track'       		=> (isset($line['revenue'])) ? $line['revenue'] - $line['royalty'] - $line['total'] : null, // ?
						'price'      		=> (isset($line['price']) && (float)$line['price'] > 0) ? $line['price'] : null,
						'royalty'    		=> (isset($line['royalty'])) ? $line['royalty'] : null,
						'total'     		=> $line['total'],
						'reward'     		=> $line['reward']
					);

					$i['lines'][] = $item;
				}

				//$lines = $this->model_resource_transaction->getLineItems($purchaseOrderId);


				$voucherData = array();

				$vouchers = $this->model_sale_order->getOrderVouchers($purchaseOrderId);

				foreach ($vouchers as $voucher) {
					$voucherData[] = array(
						'description' => $voucher['description'],
						'amount'      => $this->currency->format($voucher['amount'], $i['currency_code'], $i['currency_value'])
					);
				}

				$totalData = array();

				// Totals
				$this->load->model('extension/extension');

				$totalData = array();
				$total = 0;
				$taxes = $this->lines->getTaxes();

				$sortOrder = array();

				$results = $this->model_extension_extension->getExtensions('total');

				foreach ($results as $key => $value) {
					$sortOrder[$key] = $this->config->get($value['code'] . '_sort_order');
				}

				array_multisort($sortOrder, SORT_ASC, $results);

				foreach ($results as $result) {
					if ($this->config->get($result['code'] . '_status')) {
						$this->load->model('total/' . $result['code']);

						$this->{'model_total_' . $result['code']}->getTotal($totalData, $total, $taxes, true);
					}
				}

				$sortOrder = array();

				foreach ($totalData as $key => $value) {
					$sortOrder[$key] = $value['sort_order'];
				}

				array_multisort($sortOrder, SORT_ASC, $totalData);

				$totals = array();

				foreach ($totalData as $total) {
					$totals[] = array(
						'title' => $total['title'],
						'text'  => $this->currency->format($total['value'])
					);
				}

				$data['purchase_orders'][] = array(
					'purchase_order_id'	     => $purchaseOrderId,
					'order_id'	     	 => $i['order_id'],
					'purchase_order_no'         => $purchaseOrderNo,
					'date_added'         => date($this->language->get('date_format_short'), strtotime($i['date_added'])),
					'store_name'         => $i['store_name'],
					'store_url'          => rtrim($i['store_url'], '/'),
					'store_address'      => nl2br($storeAddress),
					'store_email'        => $storeEmail,
					'store_telephone'    => $storeTelephone,
					'store_fax'          => $storeFax,
					'email'              => $i['bill_email'],
					'telephone'          => $i['bill_telephone'],
					'shipping_address'   => $i['shipping_address'],
					'shipping_method'    => $i['shipping_method'],
					'payment_address'    => $i['payment_address'],
					'payment_method'     => $i['payment_method'],
					'lines'            	 => (isset($i['lines'])) ? $i['lines'] : array(),
					'voucher'            => $voucherData,
					'total'              => $totals, //$total_data,
					'customer_memo'      => nl2br($i['customer_memo']),
					'statement_memo'     => nl2br($i['statement_memo'])
				);
			}
		}

		$this->response->setOutput($this->load->view('transaction/purchase_order_purchase_order.tpl', $data));
	}

	protected static function formatPaymentAddress($format, $data, $replace = false, $html = true) {
		return self::formatAddress('bill', $format, $data, $replace, $html);
	}

	protected static function formatShippingAddress($format, $data, $replace = false, $html = true) {
		return self::formatAddress('ship', $format, $data, $replace, $html);
	}

	protected static function formatAddress($type, $format, $data, $replace = false, $html = true) {
		$prefix = '';
		if ($type == 'ship' || $type == 'bill') {
			$prefix = $type . '_addr_';
		}

		$find = array(
			'{firstname}',
			'{lastname}',
			'{company}',
			'{address_1}',
			'{address_2}',
			'{city}',
			'{postcode}',
			'{zone}',
			//'{zone_code}',
			'{country}'
		);

		if (!$replace) {
			$replace = array(
				'firstname' => (isset($data[$prefix . 'firstname'])) ? $data[$prefix . 'firstname'] : '',
				'lastname'  => (isset($data[$prefix . 'lastname'])) ? $data[$prefix . 'lastname'] : '',
				'company'   => (isset($data[$prefix . 'company'])) ? $data[$prefix . 'company'] : '',
				'address_1' => (isset($data[$prefix . 'line1'])) ? $data[$prefix . 'line1'] : '',
				'address_2' => (isset($data[$prefix . 'line2'])) ? $data[$prefix . 'line2'] : '',
				'city'      => (isset($data[$prefix . 'city'])) ? $data[$prefix . 'city'] : '',
				'postcode'  => (isset($data[$prefix . 'postcode'])) ? $data[$prefix . 'postcode'] : '',
				'zone'      => (isset($data[$prefix . 'zone'])) ? $data[$prefix . 'zone'] : '',
				//'zone_code' => $data[$prefix . 'zone_code'],
				'country'   => (isset($data[$prefix . 'country'])) ? $data[$prefix . 'country'] : ''
			);

		}

		if ($html) {
			$address = str_replace(array("\r\n", "\r", "\n"), '<br />', preg_replace(array("/\s\s+/", "/\r\r+/", "/\n\n+/"), '<br />', trim(str_replace($find, $replace, $format))));
		} else {
			$address = trim(str_replace($find, $replace, $format));
		}

		return $address;
	}

	// TODO: Static method in utility class
	protected static function convertDateArray($arr) {
		$date = new DateTime($arr['date']);
		$date->setTimezone(new DateTimeZone($arr['timezone']));

		return $date;
	}

	public function shipping() {
		$data = $this->load->language('transaction/purchase_order');

		$data['title'] = $this->language->get('text_shipping');

		if ($this->request->server['HTTPS']) {
			$data['base'] = HTTPS_SERVER;
		} else {
			$data['base'] = HTTP_SERVER;
		}

		$data['direction'] = $this->language->get('direction');
		$data['lang'] = $this->language->get('code');

		$this->load->model('sale/order');

		$this->load->model('catalog/product');

		$this->load->model('setting/setting');

		$data['purchase_orders'] = array();

		$purchaseOrders = array();

		if (isset($this->request->post['selected'])) {
			$purchaseOrders = $this->request->post['selected'];
		} elseif (isset($this->request->get['purchase_order_id'])) {
			$purchaseOrders[] = $this->request->get['purchase_order_id'];
		}

		$this->load->model('resource/transaction');
		$tModel = &$this->model_resource_transaction;

		$tService = new TransactionPurchaseOrder($this, 'OcPurchaseOrder');
		$tService->setEntityManager($this->em);
		$tModel->setTransactionType($tService);

		foreach ($purchaseOrders as $purchaseOrderId) {
			$i = $tModel->getTransaction($purchaseOrderId);

			if ($i) {
				$store = $this->model_setting_setting->getSetting('config', $i['store_id']);

				if ($store) {
					$storeAddress = $store['config_address'];
					$storeEmail = $store['config_email'];
					$storeTelephone = $store['config_telephone'];
					$storeFax = $store['config_fax'];
				} else {
					$storeAddress = $this->config->get('config_address');
					$storeEmail = $this->config->get('config_email');
					$storeTelephone = $this->config->get('config_telephone');
					$storeFax = $this->config->get('config_fax');
				}

				if ($i['purchase_order_no']) {
					$purchaseOrderNo = $i['purchase_order_prefix'] . $i['purchase_order_no'];
				} else {
					$purchaseOrderNo = '';
				}

				$this->load->model('vendor/vendor'); // Load customer and customer addresses
				$c = $tModel->getVendor($i['purchase_order_id']);

				if (isset($i['payment_address_format'])) {
					$format = $i['payment_address_format'];
					$i['payment_address_format'] = $format;
				} else {
					$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{city} {zone}' . "\n" . '{postcode} {country}';
					$i['payment_address_format'] = '{firstname} {lastname}\n{company}\n{address_1}\n{city} {zone}\n{postcode} {country}';
				}

				$i['payment_address'] = self::formatPaymentAddress($format, $i, false, true); // Final "true" sets output to HTML

				if (isset($i['shipping_address_format'])) {
					$format = $i['shipping_address_format'];
					$i['shipping_address_format'] = $format;
				} else {
					$format = '{firstname} {lastname}' . "\n" . '{company}' . "\n" . '{address_1}' . "\n" . '{city} {zone}' . "\n" . '{postcode} {country}';
					$i['shipping_address_format'] = '{firstname} {lastname}\n{company}\n{address_1}\n{city} {zone}\n{postcode} {country}';
				}

				$i['shipping_address'] = self::formatShippingAddress($format, $i, false, true); // Final "true" sets output to HTML

				$i['order_id'] = (isset($i['oc_entity_id'])) ? $i['oc_entity_id'] : null;

				$i['shipping_method'] = $i['shipping_method'];
				$i['shipping_code'] = $i['shipping_code'];

				$this->load->model('tool/upload');

				// This alternate method loads lines from session
				// $this->getLinesFromSession();

				$productData = array();

				$lines = $tModel->getLineItems($purchaseOrderId);
				// We need to set lines to session
				foreach ($lines as $line) {
					$options = array();
					if (isset($line['order_id'])) {
						$options = $this->model_sale_order->getOrderOptions($i['order_id'], $line['order_product_id']);
					}

					// TODO: Modify whatever's converting the entity to an array so we don't have to do this
					// Either that or make a utility method to get assoc keys
					$productId = null;
					$product = array();

					if (isset($line['product']) && isset($line['product']['productId'])) {
						$productId = $line['product']['productId'];

						$this->load->model('catalog/product');
						$product = $this->model_catalog_product->getProduct($productId);
					}

					$item = array(
						'product_id' 		=> $productId,
						'line_id'	 		=> $line['line_id'],
						'detail_type'	 	=> $line['detail_type'],
						'order_id'	 		=> $line['order_id'],
						'order_product_id'	=> $line['order_product_id'],
						'product'			=> $product,
						'description'	 	=> $line['description'],
						'name'       		=> $line['name'],
						'model'      		=> $line['model'],
						'option'     		=> (isset($line['order_id'])) ? $this->model_sale_order->getOrderOptions($i['order_id'], $line['order_product_id']) : array(),
						'quantity'  		=> $line['quantity'],
						'revenue'    		=> (isset($line['revenue'])) ? $line['revenue'] : null, // ?
						//'track'       		=> (isset($line['track'])) ? $line['track'] : null, // ?
						'track'       		=> (isset($line['revenue'])) ? $line['revenue'] - $line['royalty'] - $line['total'] : null, // ?
						'price'      		=> (isset($line['price']) && (float)$line['price'] > 0) ? $line['price'] : null,
						'royalty'    		=> (isset($line['royalty'])) ? $line['royalty'] : null,
						'total'     		=> $line['total'],
						'reward'     		=> $line['reward']
					);

					$i['lines'][] = $item;
				}

				$voucherData = array();

				$vouchers = $this->model_sale_order->getOrderVouchers($i['order_id']);

				foreach ($vouchers as $voucher) {
					$voucherData[] = array(
						'description' => $voucher['description'],
						'amount'      => $this->currency->format($voucher['amount'], $i['currency_code'], $i['currency_value'])
					);
				}

				$totalData = array();

				/*$totals = $this->model_sale_order->getOrderTotals($purchaseOrderId);

				foreach ($totals as $total) {
					$total_data[] = array(
						'title' => $total['title'],
						'text'  => $this->currency->format($total['value'], $purchaseOrderInfo['currency_code'], $purchaseOrderInfo['currency_value']),
					);
				}*/

				// Totals
				$this->load->model('extension/extension');

				$totalData = array();
				$total = 0;
				$taxes = $this->lines->getTaxes();

				$sort = array();

				$results = $this->model_extension_extension->getExtensions('total');

				foreach ($results as $key => $value) {
					$sort[$key] = $this->config->get($value['code'] . '_sort_order');
				}

				array_multisort($sort, SORT_ASC, $results);

				foreach ($results as $result) {
					if ($this->config->get($result['code'] . '_status')) {
						$this->load->model('total/' . $result['code']);

						$this->{'model_total_' . $result['code']}->getTotal($totalData, $total, $taxes, true);
					}
				}

				$sort = array();

				foreach ($totalData as $key => $value) {
					$sort[$key] = $value['sort_order'];
				}

				array_multisort($sort, SORT_ASC, $totalData);

				$totals = array();

				foreach ($totalData as $total) {
					$totals[] = array(
						'title' => $total['title'],
						'text'  => $this->currency->format($total['value'])
					);
				}

				$data['purchase_orders'][] = array(
					'purchase_order_id'	     => $purchaseOrderId,
					'order_id'	     	 => $i['order_id'],
					'purchase_order_no'         => $purchaseOrderNo,
					'date_added'         => date($this->language->get('date_format_short'), strtotime($i['date_added'])),
					'store_name'         => $i['store_name'],
					'store_url'          => rtrim($i['store_url'], '/'),
					'store_address'      => nl2br($storeAddress),
					'store_email'        => $storeEmail,
					'store_telephone'    => $storeTelephone,
					'store_fax'          => $storeFax,
					'email'              => $i['bill_email'],
					'telephone'          => $i['bill_telephone'],
					'shipping_address'   => $i['shipping_address'],
					'shipping_method'    => $i['shipping_method'],
					'payment_address'    => $i['payment_address'],
					'payment_method'     => $i['payment_method'],
					'lines'            	 => (isset($i['lines'])) ? $i['lines'] : array(),
					'voucher'            => $voucherData,
					'total'              => $totals, //$total_data,
					'customer_memo'      => nl2br($i['customer_memo']),
					'statement_memo'     => nl2br($i['statement_memo'])
				);
			}
		}

		$this->response->setOutput($this->load->view('transaction/purchase_order_shipping.tpl', $data));
	}

	public function api() {
		$this->load->language('transaction/purchase_order');

		if ($this->validate()) {
			// Store
			if (isset($this->request->get['store_id'])) {
				$storeId = $this->request->get['store_id'];
			} else {
				$storeId = 0;
			}

			$this->load->model('setting/store');

			$storeInfo = $this->model_setting_store->getStore($storeId);

			if ($storeInfo) {
				$url = $storeInfo['ssl'];
			} else {
				$url = HTTPS_CATALOG;
			}

			if (isset($this->session->data['cookie']) && isset($this->request->get['api'])) {
				// Include any URL perameters
				$urlData = array();

				foreach($this->request->get as $key => $value) {
					if ($key != 'route' && $key != 'token' && $key != 'store_id') {
						$urlData[$key] = $value;
					}
				}

				$curl = curl_init();

				// Set SSL if required
				if (substr($url, 0, 5) == 'https') {
					curl_setopt($curl, CURLOPT_PORT, 443);
				}

				curl_setopt($curl, CURLOPT_HEADER, false);
				curl_setopt($curl, CURLINFO_HEADER_OUT, true);
				curl_setopt($curl, CURLOPT_USERAGENT, $this->request->server['HTTP_USER_AGENT']);
				curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
				curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($curl, CURLOPT_FORBID_REUSE, false);
				curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($curl, CURLOPT_URL, $url . 'index.php?route=' . $this->request->get['api'] . ($urlData ? '&' . http_build_query($urlData) : ''));

				if ($this->request->post) {
					curl_setopt($curl, CURLOPT_POST, true);
					curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($this->request->post));
				}

				curl_setopt($curl, CURLOPT_COOKIE, session_name() . '=' . $this->session->data['cookie'] . ';');

				$json = curl_exec($curl);

				curl_close($curl);
			}
		} else {
			$response = array();

			$response['error'] = $this->error;

			$json = json_encode($response);
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput($json);
	}
}