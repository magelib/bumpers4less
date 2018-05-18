<?php
/**
 * ITORIS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the ITORIS's Magento Extensions License Agreement
 * which is available through the world-wide-web at this URL:
 * http://www.itoris.com/magento-extensions-license.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to sales@itoris.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extensions to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to the license agreement or contact sales@itoris.com for more information.
 *
 * @category   ITORIS
 * @package    ITORIS_M2_DYNAMIC_PRODUCT_OPTIONS
 * @copyright  Copyright (c) 2016 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

namespace Itoris\DynamicProductOptions\Helper;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $alias = 'dynamic_product_options';
    protected $settings = [];
    protected $messageManager;
    protected $_storeManager;
    protected $_objectManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        $this->messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->_objectManager = $objectManager;
        $this->_coreRegistry = $registry;
        $this->_localeDate = $localeDate;
        parent::__construct($context);
    }


    public function isAdminRegistered() {
        try {
            return true;// Itoris_Installer_Client::isAdminRegistered($this->getAlias());
        } catch(\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            return false;
        }
    }

    public function isRegisteredAutonomous($website = null) {
        return true;//Itoris_Installer_Client::isRegisteredAutonomous($this->getAlias(), $website);
    }

    public function registerCurrentStoreHost($sn) {
        return true;//Itoris_Installer_Client::registerCurrentStoreHost($this->getAlias(), $sn);
    }

    public function isRegistered($website) {
        return true;//Itoris_Installer_Client::isRegistered($this->getAlias(), $website);
    }

    public function getAlias() {
        return $this->alias;
    }

    /**
     * @param bool $admin
     * @return \Itoris\DynamicProductOptions\Model\Settings
     */
    public function getSettings($admin = false) {
        if ($admin) {
            $storeId = $this->_getRequest()->getParam('store');
            $websiteId = $storeId ? $this->_storeManager->getStore($storeId)->getWebsiteId() : 0;
        } else {
            $store = $this->_storeManager->getStore();
            $storeId = $store->getId();
            $websiteId = $store->getWebsite()->getId();
        }
        $settingsKey = $websiteId . '_' . $storeId;
        if (!isset($this->settings[$settingsKey])) {
            $this->settings[$settingsKey] = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Settings')->load($websiteId, $storeId);
        }
        return $this->settings[$settingsKey];
    }

    public function isEnabledOnFrontend() {
        return $this->isRegisteredAutonomous() && $this->getSettings()->getEnabled();
    }

    public function getRefererUrl() {
        $refererUrl = $this->_getRequest()->getServer('HTTP_REFERER');
        if ($url = $this->_getRequest()->getParam(\Magento\Store\App\Response\Redirect::PARAM_NAME_REFERER_URL)) {
            $refererUrl = $url;
        }
        if ($url = $this->_getRequest()->getParam(\Magento\Framework\App\Action\Action::PARAM_NAME_BASE64_URL)) {
            $refererUrl = $this->getEncryption()->decode($url);
        }
        if ($url = $this->_getRequest()->getParam(\Magento\Framework\App\Action\Action::PARAM_NAME_URL_ENCODED)) {
            $refererUrl = $this->getEncryption()->decode($url);
        }

        $refererUrl = $this->getEscaper()->escapeUrl($refererUrl);

        if (!$this->_isUrlInternal($refererUrl)) {
            $refererUrl = $this->_storeManager->getStore()->getBaseUrl();
        }
        return $refererUrl;
    }

    protected function _isUrlInternal($url) {
        if (strpos($url, 'http') !== false) {
            if ((strpos($url, $this->_storeManager->getStore()->getBaseUrl()) === 0)
                || (strpos($url, $this->_storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_LINK, true)) === 0)
            ) {
                return true;
            }
        }
        return false;
    }

    public function addOptionError($option, $product, $errorMessage) {
        if ($product->getData('skip_required_option' . $option->getId())) {
            return $this;
        }
        $errors = $this->_objectManager->get('Magento\Backend\Model\Session')->getDynamicOptionsErrors();
        if (!is_array($errors)) {
            $errors = [];
        }
        $errors[$option->getId()] = $this->prepareErrorMessage($errorMessage);
        $this->_objectManager->get('Magento\Backend\Model\Session')->setDynamicOptionsErrors($errors);
        return $this;
    }

    public function getOptionErrorsMessage() {
        $errors = $this->_objectManager->get('Magento\Backend\Model\Session')->getDynamicOptionsErrors();
        $this->_objectManager->get('Magento\Backend\Model\Session')->setDynamicOptionsErrors(false);
        if(is_array($errors)){
            return new \Magento\Framework\Phrase(implode(', ',$errors));
        } else {
            return new \Magento\Framework\Phrase($errors);
        }
    }

    public function prepareErrorMessage($message) {
        $errors = explode("\n", $message);
        $errors = array_map('trim', $errors);
        $errors = array_unique($errors);
        return implode("\n", $errors);
    }

    public function getCustomerGroupId() {
        if ($this->isAdmin() && $this->_getRequest()->getControllerName() == 'order_create') {
            $customerId = (int)$this->_objectManager->get('Magento\Backend\Model\Session\Quote')->getCustomerId();
            $customer = $this->_objectManager->create('Magento\Customer\Model\Customer')->load($customerId);
            return (int)$customer->getGroupId();
        }
        return (int)$this->_objectManager->get('Magento\Customer\Model\Session')->getCustomerGroupId();
    }
    /**
     * @return \Magento\Framework\Encryption\UrlCoder
     */
    public function getEncryption(){
        return $this->_objectManager->create('\Magento\Framework\Encryption\UrlCoder');
    }

    /**
     * @return \Magento\Framework\Escaper
     */
    public function getEscaper(){
        return $this->_objectManager->create('Magento\Framework\Escaper');
    }

    public function htmlEscape($data, $allowedTags = null){
        return $this->getEscaper()->escapeHtml($data, $allowedTags);
    }
    
    public function isAdmin(){
        $session = $this->_objectManager->get('Magento\Backend\Model\Auth\Session');
        return $session->isLoggedIn();
    }
    
    public function isFrontend() {
        return !$this->isAdmin();
    }
    
    public function applyToProduct($productId, $objectToStore) {
        $mapOptions = []; $mapValues = [];
        $hasOptions = 0; $hasRequiredOptions = 0;
        $defaultStoreFields = [];
        $assignedTemplates = [];
        
        //implementing a faster method of applying options through SQL queries
        /** @var \Magento\Framework\App\ResourceConnection $res */
        $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $con = $res->getConnection('write');
        
        $productData = $con->fetchRow("select * from {$res->getTableName('catalog_product_entity')} where `entity_id`={$productId}");
        //check for M2 EE constraint
        $dummyId = isset($productData['row_id']) ? $productData['row_id'] : $productId;
        //check for giftcard type in M2 EE
        if ($productData['type_id'] == 'giftcard') {
            $hasOptions = 1;
            $hasRequiredOptions = 1;
        }

        //clean all options in magento tables
        $con->delete($res->getTableName('catalog_product_option'), $con->quoteInto('product_id=?', $dummyId)); // row_id for EE, entity_id for CE

        //clean all options in dynamic product options tables
        $con->delete($res->getTableName('itoris_dynamicproductoptions_option'), $con->quoteInto('product_id=?', $productId)); // entity_id for CE and EE
        $con->delete($res->getTableName('itoris_dynamicproductoptions_options'), $con->quoteInto('product_id=?', $productId)); // entity_id for CE and EE
        
        //getting all templates
        $templateIds = $con->fetchCol("select `template_id` from {$res->getTableName('itoris_dynamicproductoptions_template')} where `store_id` = 0");
        
        foreach($objectToStore as $storeId => &$object) {
 
            $sections = json_decode($object->setStoreId($storeId)->getConfiguration(), true); //$object->setStoreId($storeId)->getSections();
        
            //checking if associated template was removed
            $sectionOrder = -1;
            foreach($sections as $key => $section) {
                $sectionOrder++;
                if (!is_array($section['fields'])) continue;
                if (isset($section['template_id']) && !in_array((int) $section['template_id'], $templateIds)) {
                    unset($sections[$key]);
                    $sectionOrder--;
                    continue;
                }
                $sections[$key]['order'] = $sectionOrder;
                foreach($section['fields'] as $key2 => $_field) {
                    if (!is_array($_field)) continue;
                    $sections[$key]['fields'][$key2]['section_order'] = $sectionOrder;
                }
            }
            $sections = array_values($sections);
            if (count($sections) == 1) {
                //removed all sections, we should add an empty one
                $sections[1] = [
                    'order' => 1,
                    'cols' => 3,
                    'rows' => 3,
                    'removable' => 1,
                    'title' => '',
                    'fields' => [],
                    'visibility_action' => 'hidden',
                    'visibility' => 'visible'
                ];
            }
            
            //if field was added to the default config we should add it to the store view config as well, comparing fields            
            if ($storeId > 0) {
                $_defaultStoreFields = $defaultStoreFields;
                foreach ($sections as &$section) {
                    if (isset($section['fields'])) {
                        foreach ($section['fields'] as $field) unset($_defaultStoreFields[(int) $field['internal_id']]);
                    }
                }
                if (!empty($_defaultStoreFields)) {
                    $section['fields'] = (array) json_decode(json_encode($section['fields']), true);
                    foreach ($_defaultStoreFields as $field) { //adding missing fields to the last section
                        //trying to put the field into the first available spot
                        $order = $field['order'];
                        while(isset($section['fields'][$order]) && !empty($section['fields'][$order])) $field['order'] = $field['sort_order'] = ++$order;
                        $section['fields'][$order] = $field;
                    }
                }
                //re-checking the section size, increase if needed
                if (!empty($section['fields']) && max(array_keys($section['fields'])) > $section['cols'] * $section['rows']) {
                    $section['rows'] = ceil(max(array_keys($section['fields'])) / $section['cols']);
                }
            }
            foreach ($sections as &$section) {
                if (isset($section['fields'])) {
                    if ($storeId == 0 && isset($section['template_id']) && $section['template_id'] > 0) $assignedTemplates[(int) $section['template_id']] = 1;
                    foreach ($section['fields'] as $index => &$field) {
                        if ($field) {
                            if ($storeId > 0) {
                                //check if field is absent in the default config we should remove it for store view as well
                                if (!isset($field['internal_id']) || !isset($defaultStoreFields[(int) $field['internal_id']])) {
                                    unset($section['fields'][$index]);
                                    continue;
                                }
                            }
                            $hasOptions = 1;
                            if (isset($field['is_require']) && intval($field['is_require'])) $hasRequiredOptions = 1;
                            $optionId = NULL;
                            if (!in_array($field['type'], ['image', 'html'])) {
                                if ($field['internal_id'] && $field['internal_id'] > 0 && isset($mapOptions[$field['internal_id']])) {
                                    $optionId = $mapOptions[$field['internal_id']];
                                } else {
                                    $optionId = $this->getMaxDb('catalog_product_option', 'option_id', $field['option_id']);
                                    $con->insert($res->getTableName('catalog_product_option'), [
                                        'option_id'         => $optionId,
                                        'product_id'         => $dummyId, //row_id for EE, entity_id for CE
                                        'type'                 => $field['type'],
                                        'is_require'         => isset($field['is_require']) ? intval($field['is_require']) : 0,
                                        'sku'                 => isset($field['sku']) ? $field['sku'] : NULL,
                                        'max_characters'    => isset($field['max_characters']) ? $field['max_characters'] : NULL,
                                        'file_extension'    => isset($field['file_extension']) ? $field['file_extension'] : NULL,
                                        'file_extension'    => isset($field['file_extension']) ? $field['file_extension'] : NULL,
                                        'image_size_x'        => isset($field['image_size_x']) ? $field['image_size_x'] : NULL,
                                        'image_size_y'        => isset($field['image_size_y']) ? $field['image_size_y'] : NULL,
                                        'sort_order'        => isset($field['sort_order']) ? intval($field['sort_order']) : 0
                                    ]);
                                    $mapOptions[$field['internal_id']] = $optionId;
                                }
                                $field['id'] = $field['option_id'] = $optionId;
                                if ($storeId == 0) { //clean up
                                    $con->query("delete from {$res->getTableName('catalog_product_option_title')} where `option_id`={$optionId}");
                                    $con->query("delete from {$res->getTableName('catalog_product_option_price')} where `option_id`={$optionId}");
                                    $con->query("delete from {$res->getTableName('catalog_product_option_type_value')} where `option_id`={$optionId}");
                                }
                                $con->insert($res->getTableName('catalog_product_option_title'), [
                                    'option_title_id'         => $this->getMaxDb('catalog_product_option_title', 'option_title_id'),
                                    'option_id'         => $optionId,
                                    'store_id'             => $storeId,
                                    'title'             => $field['title']
                                ]);
                                if (isset($field['price_type'])) $con->insert($res->getTableName('catalog_product_option_price'), [
                                    'option_price_id'         => $this->getMaxDb('catalog_product_option_price', 'option_price_id'),
                                    'option_id'         => $optionId,
                                    'store_id'             => $storeId,
                                    'price'             => floatval($field['price']),
                                    'price_type'         => $field['price_type']
                                ]);
                            }
                            
                            //keep in mind fields of the default store
                            if ($storeId == 0) $defaultStoreFields[(int) $field['internal_id']] = (array) json_decode(json_encode($field), true);

                            if (isset($field['items'])) {
                                $values = [];
                                if ($storeId > 0) {
                                    $_values = [];
                                    foreach($defaultStoreFields[(int) $field['internal_id']]['items'] as $_value) if ($_value) {
                                        if (!isset($_value['option_type_id'])) $_value['option_type_id'] = 'tmp'.$optionId.'_'.$_value['order'];
                                        $_values[$_value['option_type_id']] = $_value;
                                    }
                                    foreach ($field['items'] as $index => $_value) if ($_value) {
                                        //checkig if value was removed in the default config, we should remove it in the store view config too
                                        if (!isset($_value['option_type_id'])) $_value['option_type_id'] = 'tmp'.$optionId.'_'.$_value['order'];
                                        if (!isset($_values[$_value['option_type_id']])) {
                                            unset($field['items'][$index]);
                                            continue;
                                        }
                                        
                                        //track new values in the default config, we should add them to the end of the store view config
                                        unset($_values[$_value['option_type_id']]);
                                    }
                                    foreach($_values as $_value) if ($_value) $field['items'][] = $_value; //adding missing values here
                                }
                                foreach ($field['items'] as &$value) {
                                    if ($value) {
                                        if (!isset($value['option_type_id']) || $value['option_type_id'] == -1) $value['option_type_id'] = 'tmp'.$optionId.'_'.$value['order'];
                                        if (isset($mapValues[$value['option_type_id']])) {
                                            $valueId = $mapValues[$value['option_type_id']];
                                        } else {
                                            $valueId = $this->getMaxDb('catalog_product_option_type_value', 'option_type_id', $value['option_type_id']);
                                            $con->insert($res->getTableName('catalog_product_option_type_value'), [
                                                'option_type_id'         => $valueId,
                                                'option_id'         => $optionId,
                                                'sku'                 => isset($value['sku']) ? $value['sku'] : NULL,
                                                'sort_order'         => isset($value['sort_order']) ? $value['sort_order'] : 0
                                            ]);                                            
                                            $mapValues[$value['option_type_id']] = $valueId;
                                        }
                                        $value['option_id'] = $optionId;
                                        $value['option_type_id'] = $valueId;
                                        if ($storeId == 0) { //clean up
                                            $con->query("delete from {$res->getTableName('catalog_product_option_type_title')} where `option_type_id`={$valueId}");
                                            $con->query("delete from {$res->getTableName('catalog_product_option_type_price')} where `option_type_id`={$valueId}");
                                        }
                                        $con->insert($res->getTableName('catalog_product_option_type_title'), [
                                            'option_type_title_id'         => $this->getMaxDb('catalog_product_option_type_title', 'option_type_title_id'),
                                            'option_type_id'     => $valueId,
                                            'store_id'             => $storeId,
                                            'title'         => $value['title']
                                        ]);
                                        if (isset($value['price_type'])) $con->insert($res->getTableName('catalog_product_option_type_price'), [
                                            'option_type_price_id'         => $this->getMaxDb('catalog_product_option_type_price', 'option_type_price_id'),
                                            'option_type_id'     => $valueId,
                                            'store_id'             => $storeId,
                                            'price'             => floatval($value['price']),
                                            'price_type'         => $value['price_type']
                                        ]);
                                        $_valueId = $this->getMaxDb('itoris_dynamicproductoptions_option_value', 'value_id');
                                        $con->insert($res->getTableName('itoris_dynamicproductoptions_option_value'), [
                                            'value_id'         => $_valueId,
                                            'orig_value_id'     => $valueId,
                                            'product_id'         => $productId,
                                            'store_id'             => $storeId,
                                            'configuration'     => json_encode($value)
                                        ]);
                                        if (isset($value['customer_group']) && $value['customer_group'] != '') {
                                            foreach(explode(',', $value['customer_group']) as $group_id) {
                                                $con->insert($res->getTableName('itoris_dynamicproductoptions_option_value_customergroup'), [
                                                    'value_id'     => $_valueId,
                                                    'group_id'         => $group_id
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }
                            $_field = $field;
                            //unset($_field['items']);
                            $_optionId = $this->getMaxDb('itoris_dynamicproductoptions_option', 'option_id');
                            $con->insert($res->getTableName('itoris_dynamicproductoptions_option'), [
                                'option_id'         => $_optionId,
                                'orig_option_id'     => $optionId,
                                'product_id'         => $productId,
                                'store_id'             => $storeId,
                                'configuration'     => json_encode($_field)
                            ]);
                            if (isset($field['customer_group']) && $field['customer_group'] != '') {
                                foreach(explode(',', $field['customer_group']) as $group_id) {
                                    $con->insert($res->getTableName('itoris_dynamicproductoptions_option_customergroup'), [
                                        'option_id'     => $_optionId,
                                        'group_id'         => $group_id
                                    ]);
                                }
                            }                       
                        }
                    }
                }
            }
            
            $configId = $this->getMaxDb('itoris_dynamicproductoptions_options', 'config_id');
            $con->insert($res->getTableName('itoris_dynamicproductoptions_options'), [
                'config_id'         => $configId,
                'product_id'         => $productId,
                'store_id'             => $storeId,
                'configuration'     => json_encode($sections),
                'form_style'         => $object->getFormStyle(),
                'appearance'         => $object->getAppearance(),
                'absolute_pricing'         => (int)$object->getAbsolutePricing(),
                'absolute_sku'         => (int)$object->getAbsoluteSku(),
                'absolute_weight'         => (int)$object->getAbsoluteWeight(),
                'css_adjustments'     => $object->getData('css_adjustments') ? $object->getData('css_adjustments') : '',
                'extra_js'             => $object->getData('extra_js') ? $object->getData('extra_js') : ''
            ]);
            
            if ($storeId == 0) foreach(array_keys($assignedTemplates) as $templateId) {
                $con->insert($res->getTableName('itoris_dynamicproductoptions_template_product'), [
                    'config_id'         => $configId,
                    'template_id'         => $templateId
                ]);
            }

        }
        $con->update($res->getTableName('catalog_product_entity'), [
            'has_options'         => $hasOptions,
            'required_options'    => $hasRequiredOptions
        ], $con->quoteInto('entity_id=?', $productId));

        return true;
    }
    
    public function getMaxDb($table, $column, $default = 0){
        $default = intval($default);
        $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $con = $res->getConnection('write');
        //check first if the default ID is vacant, then use it
        if ($default && intval($con->fetchOne("select `{$column}` from {$res->getTableName($table)} where `{$column}` = {$default}")) == 0 ) return $default;
        return (int) $con->fetchOne("select max(`{$column}`) from {$res->getTableName($table)}") + 1;
    }
}