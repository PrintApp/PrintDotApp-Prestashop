<?php
/**
* 2024 Print.App
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PitchPrint to newer
* versions in the future. If you wish to customize PitchPrint for your
* needs please refer to http://print.app for more information.
*
*  @author    Print.App <hello@print.app>
*  @copyright 2024 Print.App
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of Print.App.
*/

if (!defined('_PS_VERSION_'))
    exit();

    define('PRINT_DOT_APP_ID_CUSTOMIZATION_NAME', 'Print.App');
	define('PRINT_DOT_APP_TABLE_NAME', 'print_dot_app_customization_values');
    define('PRINT_DOT_APP_DOMAIN_KEY', 'print_dot_app_DOMAIN_KEY');
    define('PRINT_DOT_APP_SECRET_KEY', 'print_dot_app_SECRET_KEY');
	define('PRINT_DOT_APP_CLIENT_RUN_JS', 'https://run.print.app');
	define('PRINT_DOT_APP_CLIENT_JS', 'https://editor.print.app/js/client.js');

    
class Print_Dot_App extends Module
{
    public function __construct() {
        $this->name = 'print_dot_app';
        $this->tab = 'front_office_features';
        $this->version = '1.2.0';
        $this->author = 'Print.App';
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.7.1.0', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Print.App', 'print_dot_app');
        $this->description = $this->l('A Web2Print product customization module', 'print_dot_app');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?', 'print_dot_app');
        
        $this->clearCustomization();
        $this->createCustomization();
    }
    
    public function install() {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        if (!parent::install())
            return false;

		$_pKey = Configuration::get(PRINT_DOT_APP_DOMAIN_KEY);
		$_pSec = Configuration::get(PRINT_DOT_APP_SECRET_KEY);

		if (empty($_pKey)) Configuration::updateValue(PRINT_DOT_APP_DOMAIN_KEY, '');
		if (empty($_pSec)) Configuration::updateValue(PRINT_DOT_APP_SECRET_KEY, '');

		// Create table
        $sql = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . PRINT_DOT_APP_TABLE_NAME . '`
            (
                cId INT NOT NULL PRIMARY KEY,
                value TEXT NOT NULL
            )';

        $db = Db::getInstance()->execute($sql);

        return $this->registerHook('displayHeader') &&
        $this->registerHook('displayProductActions') &&
        $this->registerHook('displayBackOfficeHeader') &&
        $this->registerHook('actionOrderStatusPostUpdate') &&
        $this->registerHook('displayAdminProductsExtra') &&
        $this->registerHook('displayCustomization') &&
        $this->registerHook('displayCustomerAccount') &&
        $this->registerHook('actionCartUpdateQuantityBefore');
    }

    public function uninstall()
    {
        if (parent::uninstall()) {
            return true;
        }

        return false;
    }

    public function createCustomization()
    {
        $productId = (int) Tools::getValue('id_product');
        $pa_values = (string) Tools::getValue('values');
        if (!empty($pa_values) and ($this->context->controller->php_self === 'product'
          || $this->context->controller->php_self === 'category')) {
            $indexval = Db::getInstance()->getValue('SELECT `id_customization_field` FROM `' . _DB_PREFIX_ . "customization_field` WHERE `id_product` = {$productId} AND `type` = 1  AND `is_module` = 1");

            if (empty($indexval)) {
                $indexval = $this->createCustomizationField((int) $productId);
            }

            if (!$this->context->cart->id && isset($_COOKIE[$this->context->cookie->getName()])) {
                $this->context->cart->add();
                $this->context->cookie->id_cart = (int) $this->context->cart->id;
            }

            $cCid = $this->context->cart->getProductCustomization($productId, null, true);
            if (empty($cCid)) {
                Db::getInstance()->insert('customization', [
                    'id_cart' => $this->context->cart->id,
                    'id_product' => $productId,
                    // 'id_product_attribute' => $id_product_attribute,
                    'quantity' => 0,
                    'in_cart' => 0,
                ]);

                $cCid = [[]];
                $cCid[0]['id_customization'] = Db::getInstance()->Insert_ID();
            }

            // Add shop id
            $open_values = json_decode(urldecode($pa_values));
            $open_values->shop_id = (int) Context::getContext()->shop->id;
            $pa_values = urlencode(json_encode($open_values));

            // Store projectId in core table
            $db = Db::getInstance();
            $db->insert('customized_data', [
                'id_customization' => $cCid[0]['id_customization'],
                'type' => 1,
                'index' => $indexval,
                'value' => Db::getInstance()->escape($open_values->projectId),
                'id_module' => $this->id,
            ], false, true, Db::INSERT_IGNORE);
            // Then store full detail in our table
            $db->insert(PRINT_DOT_APP_TABLE_NAME, [
                'cId' => $cCid[0]['id_customization'],
                'value' => Db::getInstance()->escape($pa_values),
            ], false, true, Db::INSERT_IGNORE);

            // Store pa_project in session cookie
            if (isset(Context::getContext()->cookie->pa_projects)) {
                $oldCookie = json_decode(Context::getContext()->cookie->pa_projects, true);
                $oldCookie[$productId] = $pa_values;
                Context::getContext()->cookie->pa_projects = json_encode($oldCookie);
            } else {
                Context::getContext()->cookie->pa_projects = json_encode([$productId => $pa_values]);
            }

            $is_ajax = Tools::getValue('ajax');
            if ($is_ajax == true) {
                exit(json_encode(['product_customization_id' => $cCid[0]['id_customization']]));
            }
        }
    }
    
    private function createCustomizationField($id_product)
    {
        $p_designs = unserialize(Configuration::get(PRINT_DOT_APP_DESIGNS));
        if (!isset($p_designs[$id_product]) && empty($p_designs[$id_product])) return null;
        $arr = explode(':', $p_designs[$id_product]);

		Db::getInstance()->insert('customization_field', array('id_product' => $id_product, 'type' => 1, 'required' => $arr[2], 'is_module' => 1));
		$custmz_field =	(int) Db::getInstance()->Insert_ID();
		
		if (!empty($custmz_field)) {
            $languages = Language::getLanguages(false);
            foreach ($languages as $lang) {
				Db::getInstance()->execute("INSERT INTO `" . _DB_PREFIX_ . "customization_field_lang` (`id_customization_field`, `id_lang`, `name`) VALUES ('{$custmz_field}', '{$lang['id_lang']}', '" . PRINT_DOT_APP_ID_CUSTOMIZATION_NAME . "') ON DUPLICATE KEY UPDATE `id_lang` = '{$lang['id_lang']}', `name` = '" . PRINT_DOT_APP_ID_CUSTOMIZATION_NAME . "'");
            }
        }

        return $custmz_field;
    }

    public function clearCustomization()
    {
        if ((Tools::getValue('clear') == true) and $this->context->controller->php_self === 'product') {
            $productId = (int) Tools::getValue('id_product');
            $indexval = Db::getInstance()->getValue('SELECT `id_customization_field` FROM `' . _DB_PREFIX_ . "customization_field` WHERE `id_product` = {$productId} AND `type` = 1  AND `is_module` = 1");
            $this->context->cart->deleteCustomizationToProduct($productId, (int) $indexval);

            // Clear product from session cookie
            if (isset(Context::getContext()->cookie->pa_projects)) {
                $current = json_decode(Context::getContext()->cookie->pa_projects, true);
                unset($current[$productId]);
                Context::getContext()->cookie->pa_projects = json_encode($current);
            }
            exit(json_encode(['cleared' => true]));
        }
    }

    public function hookDisplayCustomerAccount($params)
    {
        $smarty = new Smarty();
        $html = $smarty->fetch(__DIR__ . '/views/templates/front/displayCustomerAccount.tpl');

        return $html;
    }

    public function hookDisplayCustomization($params)
    {
        if (isset($params['customization']['id_customization'])) {
            $customization_id = $params['customization']['id_customization'];
        } else {
            return 'Customization ID not found.';
        }
    
        // Prepare the database query
        $request = 'SELECT `value` FROM `' . _DB_PREFIX_ . PRINT_DOT_APP_TABLE_NAME . "` WHERE `cId` = " . (int)$customization_id;
        $raw = Db::getInstance()->getValue($request);
    
        if ($raw === false)
            return 'No data found for the given customization ID.';
    
        // Decode the JSON data, handling the case where decoding fails
        $value = json_decode(rawurldecode($raw), true);
        if (json_last_error() !== JSON_ERROR_NONE) 
            return 'Failed to decode JSON data.';
    
        $current_context = Context::getContext();
        if ($current_context->controller->controller_type == 'front') {
            $this->smarty->assign('print_dot_app_customization', $value);
            return $this->fetch('module:print_dot_app/views/templates/front/displayCustomization.tpl');
            
        } else if ($current_context->controller->controller_type == 'admin') {
            return 'PrintApp_Customization' . json_encode($value) . 'PrintApp_Customization';
        }
    
        return "Project ID: {$params['customization']['value']}";
    }


    public function hookActionOrderStatusPostUpdate($params)
    {
        $statusId = (int) $params['newOrderStatus']->id;
        $doHook = ($statusId === 3 || $statusId === 4);
        if (!$doHook) {
            return;
        } // At this stage we only provide webhook for order processing or completed

        $order = new Order((int) $params['id_order']);

        $id_cart = $order->id_cart;

        $status = '';

        switch ($statusId) {
            case 3:
                $status = 'processing';
                break;
            case 4:
                $status = 'complete';
                break;
        }

        $products = $order->getCartProducts();
        $customer = $order->getCustomer();
        $items = [];

        $address = new Address($order->id_address_delivery);

        foreach ($products as $prod) {
            $printapp = '';

            if ($prod['customizedDatas'] != null) {
                $data_in = Db::getInstance()->executeS('SELECT `value` FROM `' . _DB_PREFIX_ . 'customized_data` WHERE
					`id_customization` =' . $prod['id_customization']);

                foreach ($data_in as $data_item) {
                    $array_data = (array) json_decode(rawurldecode($data_item['value']));

                    if (is_array($array_data)
                        && count(array_keys($array_data))
                            && in_array('type', array_keys($array_data)) && $array_data['type'] == 'p') {
                        $printapp = $array_data;
                    }
                }
            }

            $items[] = [
                'name' => $prod['product_name'],
                'id' => $prod['product_id'],
                'qty' => $prod['cart_quantity'],
                'printapp' => json_encode($printapp),
            ];
        }

        $pa_empty = true;
        foreach ($items as $item) {
            $ppItemDecoded = json_decode($item['printapp']);
            if (!empty($ppItemDecoded)) {
                $pa_empty = false;
            }
        }
        if ($pa_empty) {
            return;
        }

        $items = json_encode($items);

        $timestamp = time();
        $printapp_api_value = Configuration::get(PRINT_DOT_APP_DOMAIN_KEY);
        $printapp_secret_value = Configuration::get(PRINT_DOT_APP_SECRET_KEY);
        $signature = md5($printapp_api_value . html_entity_decode($printapp_secret_value) . $timestamp);

        $body = [
            'products' => $items,
            'client' => 'ps',
            'billingEmail' => $customer->email,
            'billingPhone' => $address->phone,
            'shippingName' => $address->firstname . ' ' . $address->lastname,
            'shippingAddress' => $address->company . ',\n' . $address->address1 . ',\n' . $address->address2 . ',\n' . $address->city . ',\n' . $address->postcode . ',\n' . $address->country,
            'orderId' => $params['id_order'],
            'customer' => $customer->id,
            'apiKey' => $printapp_api_value,
            'signature' => $signature,
            'status' => $status,
            'timestamp' => $timestamp,
            'shop_id' => (int) Context::getContext()->shop->id,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_URL, "https://api.print.app/runtime/order-{$status}");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 300);

        $output = curl_exec($ch);
        $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $curlerr = curl_error($ch);
        curl_close($ch);

        if ($curlerr && $http_status != 200) {
            $error_message = ['error' => $curlerr];
            error_log(print_r($error_message, true));
        }
    }

    public function hookDisplayProductActions($params)
    {
        $productId = (int)Tools::getValue('id_product');
   

		 //update product customizable
        // Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'product` SET `customizable` = 1 WHERE `id_product` = '.(int)$productId);

        //update product_shop count fields labels
        ObjectModel::updateMultishopTable('product', array('customizable' => 1), 'a.id_product = '.(int)$productId);

        Configuration::updateGlobalValue('PS_CUSTOMIZATION_FEATURE_ACTIVE', '1');

		$pdaData = array(
			'client'				=> 'ps',
			'userId'				=> $this->context->cookie->id_customer,
			'langCode'				=> $this->context->language->iso_code,
			'product'				=> array(
				'id'					=> $productId,
				'name'					=> addslashes($params['product']['name']),
				'url'					=> Tools::getHttpHost(true) . __PS_BASE_URI__ . 'index.php?controller=product&id_product='.$productId
			),
			// 'id_customization'		=> $pda_customization_id,
			'values'				=> '{}',
		);
		
		if (isset($pda_values) && !empty($pda_values)) {
			$pdaData['values'] = $pda_values;
		}

		
		if ($this->context->customer->isLogged()) {
			$fname = addslashes($this->context->cookie->customer_firstname);
			$lname = addslashes($this->context->cookie->customer_lastname);

			$cus = new Customer((int)$this->context->cookie->id_customer);
			$cusInfo = $cus->getAddresses((int)Configuration::get('PS_LANG_DEFAULT'));
			$cusInfo = $cusInfo[0];
			$addr = "{$cusInfo['address1']}<br>";
			if (!empty($cusInfo['address2'])) $addr .= "{$cusInfo['address2']}<br>";
			$addr .= "{$cusInfo['city']} {$cusInfo['postcode']}<br>";
			if (!empty($cusInfo['state'])) $addr .= "{$cusInfo['state']}<br>";
			$addr .= "{$cusInfo['country']}";

			$addr = trim($addr);

			$pdaData['launchData'] = array(
				'email' => $this->context->cookie->email,
				'name' => $fname.' '.$lname,
				'firstname' => $fname,
				'lastname' => $lname,
				'telephone' => $cusInfo['phone'],
				'fax' => '',
				'address' => addslashes($addr)
			);
        }
        
        $jsonEncodedPdaData = json_encode($pdaData);

        return '<script type="text/javascript">
        		var pdaData = ' . $jsonEncodedPdaData . ';
			    window.printAppParams = {
			        ...pdaData,
			        ...JSON.parse(decodeURIComponent(pdaData.values)),
			    };
    		</script>';
    }
    
    // Reset product upon add to cart
    public function hookActionCartUpdateQuantityBefore($params)
    {
        $productId = $params['product']->id;
        if (isset(Context::getContext()->cookie->pa_projects)) {
            $current = json_decode(Context::getContext()->cookie->pa_projects, true);
            unset($current[$productId]);
            Context::getContext()->cookie->pa_projects = json_encode($current);
        }
    }

    public function hookDisplayHeader($params)
    {
		if ($this->context->controller->php_self === 'product') {
			
			$productId = (int)Tools::getValue('id_product');
			$this->context->controller->registerJavascript(
				'pda-client-js',
				PRINT_DOT_APP_CLIENT_RUN_JS . '/' . Configuration::get(PRINT_DOT_APP_DOMAIN_KEY) . '/'. $productId . '/ps?lang=' . $this->context->language->iso_code,
				['server' => 'remote', 'position' => 'bottom', 'priority' => 200]
			);
			return '';

		} else if (substr($this->context->controller->php_self, 0, 5) === 'cart' || $this->context->controller->php_self === 'order-detail' || $this->context->controller->php_self === 'order-confirmation' || $this->context->controller->php_self === 'my-account') {
			
			$this->context->controller->registerJavascript(
				'pda-client-js',
				PRINT_DOT_APP_CLIENT_JS,
				['server' => 'remote', 'position' => 'head', 'priority' => 200]
			);
			
			$page = $this->context->controller->php_self;
			if (substr($this->context->controller->php_self, 0, 5) === 'cart') $page = 'cart';
			
			$pa_apiKey = Configuration::get(PRINT_DOT_APP_DOMAIN_KEY);
			$ppData = array(
				'noInstance' => true,
				'client' => 'ps',
				'page' => $page,
				'userId' => $this->context->cookie->id_customer,
				'langCode' => $this->context->language->iso_code,
				'apiKey' => $pa_apiKey,
			);

			return '<script type="text/javascript">
				window.printAppParams = '.json_encode($ppData).';
				document.addEventListener("DOMContentLoaded", function() {
					if (typeof PrintAppClient !== "undefined") window.printAppInstance = new PrintAppClient(window.printAppParams);
				});
				</script>';

		}
    }

    // Admin functions =====================================================================================

    public function hookDisplayBackOfficeHeader($params)
    {
        if (Tools::getValue('ajax')) return;
		
		$_controller = Context::getContext()->controller;
		
		if ($_controller->controller_name === 'AdminOrders') {
			$this->context->controller->addJS($this->_path.'/views/js/psAdmin.js');
		}
    }

    public function hookDisplayAdminProductsExtra($params)
    {
        $id_product = (int)$params['id_product'];
        if (Validate::isLoadedObject($product = new Product($id_product))) {
        	$api_key = Configuration::get(PRINT_DOT_APP_DOMAIN_KEY);
        	$this->context->smarty->assign([
				'pa_product_title' => array_pop($product->name),
				'pa_api_key' => $api_key,
				'pa_product_id' => $id_product,
                'pa_module_uri' =>   __PS_BASE_URI__ . 'modules/print_dot_app'
            ]);
			return $this->display(__FILE__, '/views/templates/admin/displayAdminProductsExtra.tpl');
        } else
			$this->context->controller->errors[] = Tools::displayError('Please first save this new product before assigning a design!');
    }

    public function getContent()
    {
      $output = null;

      if (Tools::isSubmit('submit'.$this->name)) {
          $print_dot_app_api = strval(Tools::getValue(PRINT_DOT_APP_DOMAIN_KEY));
          $print_dot_app_secret = strval(Tools::getValue(PRINT_DOT_APP_SECRET_KEY));

          if (!$print_dot_app_api  || empty($print_dot_app_api) || !Validate::isGenericName($print_dot_app_api) || !$print_dot_app_secret  || empty($print_dot_app_secret) || !Validate::isGenericName($print_dot_app_secret)) {
              $output .= $this->displayError( $this->l('Invalid Configuration value') );
          } else {
                $print_dot_app_api = str_replace(' ', '', $print_dot_app_api);
                $print_dot_app_secret = str_replace(' ', '', $print_dot_app_secret);
                Configuration::updateValue(PRINT_DOT_APP_DOMAIN_KEY, $print_dot_app_api);
                Configuration::updateValue(PRINT_DOT_APP_SECRET_KEY, $print_dot_app_secret);
                
                $output .= $this->displayConfirmation($this->l('Settings updated'));
          }
      }
      return $output.$this->renderForm();
    }

    public function renderForm() {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');

        $fields_form[0]['form'] = array(
            'legend' => array(
                'title' => $this->l('Settings'),
				'icon' => 'icon-cogs'
            ),
            'input' => array(
                array(
                    'type' => 'text',
                    'label' => $this->l('Print.App Domain Key'),
                    'name' => PRINT_DOT_APP_DOMAIN_KEY,
                    'suffix' => '&nbsp; &nbsp; :&nbsp; <a href="https://admin.print.app/domains" target="_blank">Get your new keys here</a>, &nbsp; &nbsp; : &nbsp; &nbsp; <a target="_blank" href="https://docs.print.app">Online Documentation</a>',
                    'size' => 40,
                    'required' => true
                ),
                array(
                    'type' => 'text',
                    'label' => $this->l('Print.App Secret Key'),
                    'name' => PRINT_DOT_APP_SECRET_KEY,
                    'size' => 40,
                    'required' => true
                )
                // ToDo: Category Customization
            ),
            'submit' => array(
                'title' => $this->l('Save'),
                'class' => 'button'
            )
        );

        $helper = new HelperForm();

        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;

        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = array(
            'save' =>
            array(
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.
                '&token='.Tools::getAdminTokenLite('AdminModules'),
            ),
            'back' => array(
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list')
            )
        );

        // Load current value
        $helper->fields_value[PRINT_DOT_APP_DOMAIN_KEY] = Configuration::get(PRINT_DOT_APP_DOMAIN_KEY);
        $helper->fields_value[PRINT_DOT_APP_SECRET_KEY] = Configuration::get(PRINT_DOT_APP_SECRET_KEY);

        return $helper->generateForm($fields_form);
    }
}
