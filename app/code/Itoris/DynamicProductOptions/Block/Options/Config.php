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

//app/code/Itoris/DynamicProductOptions/Block/Options/Config.php
namespace Itoris\DynamicProductOptions\Block\Options;

class Config extends \Magento\Catalog\Block\Product\View\Options//\Magento\Framework\View\Element\Template//
{
    static protected $isJsCssAdded = false;

    protected $config = null;
    protected $isEnabled = false;
    protected $associatedProductBlocks = [];
    /** @var \Magento\Framework\ObjectManagerInterface|null  */
    public $_objectManager = null;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Catalog\Model\Product\Option $option
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\ArrayUtils $arrayUtils
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\Product\Option $option,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        parent::__construct($context, $pricingHelper, $catalogData, $jsonEncoder, $option, $registry, $arrayUtils, $data);
    }

    protected function _construct() {

        $this->isEnabled = $this->getDataHelper()->getSettings()->getEnabled() && $this->getDataHelper()->isRegisteredAutonomous() && $this->getProduct();
        if ($this->isEnabled) {
            if ($this->getProduct()->getTypeId() != 'grouped') {
                $this->setTemplate('Itoris_DynamicProductOptions::config.phtml');
                $this->isEnabled = $this->getConfig()->getId();
            }
        }
        parent::_construct();
    }

    /**
     * @return \Itoris\DynamicProductOptions\Model\Options
     */
    public function getConfig() {
        if (is_null($this->config)) {
            /** @var \Itoris\DynamicProductOptions\Model\Options config */
            $this->config = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Options')
                ->setStoreId(
                    $this->_storeManager->getStore()->getId())
                ->load($this->getProductId());
            if (!$this->config->getId()) {
                $this->config->setStoreId(0)->load($this->getProductId());
            }
            if (!$this->config->getId()) {
                $this->config->setProductId($this->getProductId());
            }
        }
        return $this->config;
    }

    public function getProductId() {
        return $this->getProduct()->getId();
    }

    /**
     * @return \Magento\Catalog\Model\Product
     */

    public function getProduct() {
        if ($this->getData('product')) {
            $this->setTemplate('grouped/config.phtml');
            return $this->getData('product');
        } else {
            if ($this->_request->getParam('handles')) return false; //fix for conflict with Varnish
            return parent::getProduct();
        }
    }

    public function getStyles() {
        return $this->getConfig()->getCssAdjustments();
    }

    public function getExtraJs() {
        return $this->getConfig()->getExtraJs();
    }
    
    public function getAbsolutePricing() {
        return $this->getConfig()->getAbsolutePricing();
    }
    
    public function getAbsoluteSku() {
        return $this->getConfig()->getAbsoluteSku();
    }
    
    public function getAbsoluteWeight() {
        return $this->getConfig()->getAbsoluteWeight();
    }    
    
    public function getJsObjectConfig(array $config = []) {
        $errorMessage = $this->getDataHelper()->getOptionErrorsMessage();
        if ($errorMessage) {
            $errorMessage = $this->getDataHelper()->prepareErrorMessage($errorMessage);
        }
        $defaultConfig = [
            'form_style'    => $this->getConfig()->getFormStyle(),
            'appearance'    => $this->getConfig()->getAppearance(),
            'absolute_pricing'    => $this->getConfig()->getAbsolutePricing(),
            'absolute_sku'    => $this->getConfig()->getAbsoluteSku(),
            'absolute_weight'    => $this->getConfig()->getAbsoluteWeight(),
            'product_id'    => $this->getProductId(),
            'is_configured' => $this->isEditAction(),
            'is_grouped'    => $this->hasData('product'),
            'option_errors' => $this->_getOptionErrors(),
            'error_message'   => $errorMessage,
            'product_type'    => $this->getProduct()->getTypeId(),
            'configure_product_message' => $this->escapeHtml(__('Please configure the product')),
            //magento 1.9 ??? false
            //'mage19'          => $this->getDataHelper()->isMagento19(),
            'out_of_stock_message' => $this->escapeHtml(__('out of stock')),
            'section_conditions' => [],
            'options_qty' => $this->getQuoteProductOptionsQtys()
        ];

        $config = array_merge($defaultConfig, $config);

        return \Zend_Json::encode($config);
    }

    public function getQuoteProductOptionsQtys(){
        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $this->_request;
        if ($request->getControllerName() == 'cart' && $request->getActionName() == 'configure' && intval($request->get('id')) > 0) {
            /** @var \Magento\Checkout\Model\Cart $cartModel */
            $cartModel = $this->_objectManager->create('Magento\Checkout\Model\Cart');
            $item = $cartModel->getQuote()->getItemById(intval($request->get('id')));
            if ($item && $item->getId()) {
                return (array) $item->getBuyRequest()->getData('options_qty');
            }
        }
        if ($this->_request->getControllerName() == 'order_create') { //moo
            /** @var \Magento\Backend\Model\Session\Quote $quoteSingleton */
            $quoteSingleton = $this->_objectManager->get('Magento\Backend\Model\Session\Quote');
            $item = $quoteSingleton->getQuote()->getItemById(intval($request->get('id')));
            if ($item && $item->getId()) {
                return (array) $item->getBuyRequest()->getData('options_qty');
            }
        }
        return [];
    }

    protected function _getOptionErrors() {
        $result = [];
        $errors = $this->_objectManager->get('Magento\Backend\Model\Session')->getDynamicOptionsErrors(true);
        if (is_array($errors)) {
            foreach ($errors as $optionId => $message) {
                $result[] = [
                    'option_id' => $optionId,
                    'message'   => $message,
                ];
            }
        }
        return $result;
    }

    public function isEditAction() {
        return $this->getRequest()->getActionName() == 'configure';
    }

    public function getSections() {
        $sections = $this->getConfig()->getSections();
        $sectionsObjects = [];
        foreach ($sections as $section) {
            if ($section) {
                if (!isset($section['fields'])) {
                    $section['fields'] = [];
                }
                $fieldsObjects = [];
                foreach ($section['fields'] as $field) {
                    if ($field) {
                        if (!isset($field['items'])) {
                            $field['items'] = [];
                        }
                        $itemsObjects = [];
                        foreach ($field['items'] as $item) {
                            if ($item) {
                                $itemsObjects[] = $item;
                            }
                        }
                        $field['items'] = $itemsObjects;
                        $fieldsObjects[] = new \Magento\Framework\DataObject($field);
                    }
                }
                $section['fields'] = $fieldsObjects;
                $sectionsObjects[] = new \Magento\Framework\DataObject($section);
            }
        }
        return $sectionsObjects;
    }

    public function getFieldHtml($field) {
        $block = null;
        switch ($field->getType()) {
            case 'image':
                $block = $this->getLayout()->createBlock('Itoris\DynamicProductOptions\Block\Options\Type\Image');
                break;
            case 'html':
                $block = $this->getLayout()->createBlock('Itoris\DynamicProductOptions\Block\Options\Type\Html');
                break;
        }
        if ($block) {
            return $block->setField($field)->toHtml();
        }

        return null;
    }

    public function isSystemOption($option) {
        return !($option->getType() == 'image' || $option->getType() == 'html');
    }

    public function getAllFieldsJson() {
        $sections = $this->getConfig()->getSections();
        $fields = [];
        $priceCurrency = $this->_objectManager->get('Magento\Framework\Pricing\PriceCurrencyInterface');
        foreach ($sections as $section) {
            if ($section && is_array($section['fields'])) {
                foreach ($section['fields'] as $field) {
                    if ($field) {
                        if (isset($field['comment']) && $field['comment']) $field['comment'] = (string)__($field['comment']);
                        if (isset($field['default_value']) && $field['default_value']) $field['default_value'] = (string)__($field['default_value']);
                        if (isset($field['items']) && is_array($field['items'])) {
                            foreach($field['items'] as $key => $item) {
                                if (isset($item['sku_is_product_id_linked']) && (int) $item['sku_is_product_id_linked'] && (int) $item['sku_is_product_id']) {
                                    $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load((int) $item['sku']);
                                    $tierPricesList = $product->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
                                    $tierPrices = [];
                                    foreach ($tierPricesList as $tierPrice) {
                                        $tierPrices[] = ['qty' => $tierPrice['price_qty'], 'price' => $priceCurrency->convert($tierPrice['website_price']), 'price_type' => 'fixed'];
                                    }
                                    if (count($tierPrices)) $field['items'][$key]['tier_price'] = json_encode($tierPrices); else unset($field['items'][$key]['tier_price']);
                                }
                            }
                        }
                        $fields[] = $field;
                    }
                }
            }
        }
        return \Zend_Json::encode($fields);
    }

    public function getOptionPrice($option) {
        if ($option->getPrice()) {
            if ($option->getPriceType() == 'percent') {
                $basePrice = $this->getProduct()->getFinalPrice();
                $price = $basePrice * ($option->getPrice() / 100);
            } else {
                $price = $option->getPrice();
            }
            return $this->_formatPrice($price);
        }
        return '';
    }

    protected function _formatPrice($price, $flag=true) {
        if ($price == 0) {
            return '';
        }
        $taxHelper = $this->getTaxHelper();
        $store = $this->getProduct()->getStore();

        $sign = '+';
        if ($price < 0) {
            $sign = '-';
            $price = 0 - $price;
        }

        $priceStr = $sign;
        $_priceInclTax = $this->getPrice($price, true);
        $_priceExclTax = $this->getPrice($price);
        /** @var \Magento\Framework\Pricing\Helper\Data $pricingHelper */
        $pricingHelper = $this->_objectManager->create('Magento\Framework\Pricing\Helper\Data');
        if ($taxHelper->displayPriceIncludingTax()) {
            $priceStr .= $pricingHelper->currencyByStore($_priceInclTax, $store, true, $flag);
        } elseif ($taxHelper->displayPriceExcludingTax()) {
            $priceStr .= $pricingHelper->currencyByStore($_priceExclTax, $store, true, $flag);
        } elseif ($taxHelper->displayBothPrices()) {
            $priceStr .= $pricingHelper->currencyByStore($_priceExclTax, $store, true, $flag);
            if ($_priceInclTax != $_priceExclTax) {
                $priceStr .= ' ('.$sign.$pricingHelper
                        ->currencyByStore($_priceInclTax, $store, true, $flag).' '.$this->escapeHtml(__('Incl. Tax')).')';
            }
        }

        if ($flag) {
            $priceStr = '<span class="price-notice">'.$priceStr.'</span>';
        }

        return $priceStr;
    }

    public function getPrice($price, $includingTax = null) {
        if (!is_null($includingTax)) {
            $price = $this->getCatalogHelper()->getTaxPrice($this->getProduct(), $price, true);
        } else {
            $price = $this->getCatalogHelper()->getTaxPrice($this->getProduct(), $price);
        }
        return $price;
    }

    protected function _toHtml() {
        if ($this->isEnabled) {
            if ($this->getProduct()->getTypeId() == 'grouped') {
                $this->setTemplate('grouped.phtml');
                $html = parent::_toHtml();
                foreach ($this->getProduct()->getTypeInstance(true)->getAssociatedProducts($this->getProduct()) as $product) {
                    $product->load($product->getId());
                    $subBlock = $this->_objectManager->create('Itoris\DynamicProductOptions\Block\Options\Config')
                        ->setProduct($product)->setTemplate('Itoris_DynamicProductOptions::grouped/config.phtml');
                    $html .= $subBlock->toHtml();
                }
                //foreach ($this->associatedProductBlocks as $blockName) {
                //    $html .= $this->getChildHtml($blockName);
                //}
                return $html;
            } else {
                return parent::_toHtml();
            }
        }
        return null;
    }

    protected function _prepareLayout() {
        if ($this->isEnabled) {
            $head = $this->getLayout()->getBlock('head');
            if (!self::$isJsCssAdded) {
                if ($head) {
                    //$head->addJs('itoris/dynamicproductoptions/options.js');
                    $head->addCss('main.css');
                    self::$isJsCssAdded = true;
                }
            }
            /*
            if ($this->getProduct()->getTypeId() == 'grouped' && $this->getDataHelper()->isModuleEnabled('Itoris_GroupedProductConfiguration')) {
                if ($head) {
                    $head->addCss('grouped.css');
                }
                foreach ($this->getProduct()->getTypeInstance(true)->getAssociatedProducts($this->getProduct()) as $product) {
                    $blockName = 'dynamic_options' . $product->getId();
                    $configBlock = $this->getLayout()->createBlock('Itoris\DynamicProductOptions\Block\Options\Config', $blockName, ['product' => $product]);
                    $this->setChild($blockName, $configBlock);
                    $this->associatedProductBlocks[] = $blockName;
                }
            }
            */
        }
        return parent::_prepareLayout();
    }

    public function prepareSectionConditions($section, $allConditions) {
        if ($section->getVisibilityCondition()) {
            $allConditions[] = [
                'order'                => $section->getOrder(),
                'visibility'           => $section->getVisibility(),
                'visibility_action'    => $section->getVisibilityAction(),
                'visibility_condition' => $section->getVisibilityCondition(),
            ];
        } else {
            $allConditions[] = [
                'order'                => $section->getOrder(),
                'visibility'           => $section->getVisibility(),
                'visibility_action'    => $section->getVisibilityAction(),
                'visibility_condition' => '{"type":"all","value":1,"conditions":[]}',
            ];
        }

        return $allConditions;
    }

    /**
     * @return \Itoris\DynamicProductOptions\Helper\Data
     */
    public function getDataHelper() {
        return $this->_objectManager->create('\Itoris\DynamicProductOptions\Helper\Data');
    }

    /**
     * @return \Magento\Tax\Helper\Data
     */
    public function getTaxHelper(){
        return $this->_objectManager->create('Magento\Tax\Helper\Data');
    }
    /**
     * @return \Magento\Catalog\Helper\Data
     */
    public function getCatalogHelper(){
        return $this->_objectManager->create('Magento\Catalog\Helper\Data');
    }
    
    public function getTierPrices() {
        $tierPrices = [];
        $product = $this->getProduct();
        $priceCurrency = $this->_objectManager->get('Magento\Framework\Pricing\PriceCurrencyInterface');
        $tierPricesList = $product->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
        foreach ($tierPricesList as $tierPrice) {
            $tierPrices[] = ['qty' => $tierPrice['price_qty'], 'price' => $priceCurrency->convert($tierPrice['website_price'])];
        }
        return $tierPrices;
    }
    
    public function getTranslations() {
        return [
            'Qty' => __('Qty'),
            'Buy %1 for %2 each' => __('Buy %1 for %2 each')
        ];
    }
}