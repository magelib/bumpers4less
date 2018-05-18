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

namespace Itoris\DynamicProductOptions\Model;

class Options extends \Magento\Framework\Model\AbstractModel
{
    /** @var null|\Magento\Catalog\Model\Product */
    protected $productModel = null;
    protected $sections = null;
    protected $customTypes = ['image', 'html'];
    /**
     * @var \Magento\Framework\ObjectManagerInterface|null
     */
    protected $_objectManager = null;
    /**
     * @var \Magento\Framework\Registry|null
     */
    protected $_coreRegistry = null;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_objectManager = $objectManager;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct() {
        $this->_init('Itoris\DynamicProductOptions\Model\ResourceModel\Options');
    }

    public function getSections() {
        if (is_null($this->sections)) {
            $sections = [];
            $usedOptionIds = [];
            $maxOrder = 0;
            $maxSectionOrder = 0;
            if ($this->getProductId()) {
                $defaultOptions = [];
                if ($this->getConfiguration()) {
                    $sections = \Zend_Json::decode($this->getConfiguration());
                    foreach ($sections as $key => $value) {
                        if (is_array($value) && isset($value['fields'])) {
                            (array)$sections[$key]['fields'];
                        }
                    }
                    /** @var $allOptions \Itoris\DynamicProductOptions\Model\ResourceModel\Option\Collection */
                    $allOptions = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option')->getCollection();
                    $allOptions->addFieldToFilter('product_id', $this->getProductId())
                        ->addFieldToFilter('store_id', $this->getStoreId());
                    if ($this->getDataHelper()->isFrontend() || $this->getRequest()->getControllerName() == 'order_create') {
                        $allOptions->addCustomerGroupFilter();
                    }
                    $resultOptions = [];
                    foreach ($allOptions as $option) {
                        $optionConfig = [];
                        foreach ($defaultOptions as $_defOption) {
                            if ($_defOption['option_id'] == $option->getOrigOptionId()) {
                                $optionConfig = $_defOption;
                                break;
                            }
                        }
                        if ($option->getOrigOptionId()) {
                            $usedOptionIds[] = $option->getOrigOptionId();
                        }
                        if ($option->getConfiguration()) {
                            $optionConfig = array_merge($optionConfig, \Zend_Json::decode($option->getConfiguration()));
                            if (!in_array($optionConfig['type'], $this->customTypes)) {
                                //lost option
                                if ($option->getOrigOptionId()) {
                                    if (!isset($optionConfig['option_id'])
                                        || !isset($optionConfig['section_order'])
                                        || !array_key_exists($optionConfig['section_order'], $sections)
                                    ) {
                                        $_defOptionObj = $this->_objectManager->create('Magento\Catalog\Model\Product\Option')->load($option->getOrigOptionId());
                                        if ($_defOptionObj) {
                                            $_defOptionObj->delete();
                                        }
                                        continue;
                                    }
                                } else {
                                    $option->delete();
                                    continue;
                                }
                            }
                            $optionConfig['img_src'] = $this->correctBaseImageUrl(@$optionConfig['img_src']);
                            $optionConfig['itoris_option_id'] = $option->getId();
                            $optionConfig['order'] = intval($optionConfig['order']);
                            if ($optionConfig['order'] > $maxOrder) {
                                $maxOrder = $optionConfig['order'];
                            }
                            if ($optionConfig['section_order'] > $maxSectionOrder) {
                                $maxSectionOrder = $optionConfig['section_order'];
                            }
                            if (!empty($optionConfig['items'])) {
                                $valuesIds = [];
                                foreach ($optionConfig['items'] as $key => $item) {
                                    $valuesIds[] = $item['option_type_id'];
                                }
                                $optionItems = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option\Value')->getCollection()
                                    ->addFieldToFilter('orig_value_id', ['in' => $valuesIds])
                                    ->addFieldToFilter('product_id', ['eq' => $this->getProductId()])
                                    ->addFieldToFilter('store_id', ['eq' => intval($this->getStoreId())]);
                                if ($this->getDataHelper()->isFrontend() || $this->getRequest()->getControllerName() == 'order_create') {
                                    $optionItems->addCustomerGroupFilter();
                                }
                                $dynamicItems = [];
                                foreach ($optionItems as $item) {
                                    if ($item->getConfiguration()) {
                                        foreach ($optionConfig['items'] as $_origItem) {
                                            if ($_origItem['option_type_id'] == $item['orig_value_id']) {
                                                $dynamicItems[] = array_merge($_origItem, \Zend_Json::decode($item->getConfiguration()));
                                            }
                                        }
                                    }
                                }
                                if ($this->getDataHelper()->isFrontend() || $this->getRequest()->getControllerName() == 'order_create') {
                                    foreach ($dynamicItems as &$_dynamicItem) {
                                        if (array_key_exists('sku_is_product_id', $_dynamicItem) && (int)$_dynamicItem['sku_is_product_id']) {
                                            $_dynamicItem['sku_is_product_id'] = 1;
                                            /** @var $product \Magento\Catalog\Model\Product */
                                            $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($_dynamicItem['sku']);
                                            $_dynamicItem['is_salable'] = $product->getId() && $product->getStatus() == 1 && $product->isSalable();
                                        } else {
                                            $_dynamicItem['sku_is_product_id'] = 0;
                                        }
                                        if (!array_key_exists('use_qty', $_dynamicItem)) {
                                            $_dynamicItem['use_qty'] = 0;
                                        } else $_dynamicItem['use_qty'] = !!intval($_dynamicItem['use_qty']);
                                    }
                                }
                                $optionConfig['items'] = $this->_sortByOrder($dynamicItems);
                                if (is_array($optionConfig['items'])) {
                                    foreach($optionConfig['items'] as $key => $item) {
                                        $optionConfig['items'][$key]['image_src'] = $this->correctBaseImageUrl(@$item['image_src']);
                                    }
                                }
                            }
                        }
                        if (!isset($resultOptions[$optionConfig['section_order']])) {
                            $resultOptions[$optionConfig['section_order']] = [];
                        }
                        $resultOptions[$optionConfig['section_order']][] = $optionConfig;
                    }

                    $defaultOptionsAdded = false;
                    foreach ($defaultOptions as $defOption) {
                        if (!in_array($defOption['option_id'], $usedOptionIds)) {
                            $defOption['section_order'] = $maxSectionOrder;
                            $defOption['order'] = ++$maxOrder;
                            $resultOptions[$maxSectionOrder][] = $defOption;
                            $defaultOptionsAdded = true;
                        }
                    }
                    if ($defaultOptionsAdded) {
                        if (!isset($sections[$maxSectionOrder]['cols'])) {
                            $sections[$maxSectionOrder]['cols'] = 3;
                        }
                        $minSectionRows = $maxOrder / $sections[$maxSectionOrder]['cols'];
                        if (!isset($sections[$maxSectionOrder]['rows'])) {
                            $sections[$maxSectionOrder]['rows'] = 3;
                        }
                        if ($minSectionRows > $sections[$maxSectionOrder]['rows']) {
                            $sections[$maxSectionOrder]['rows'] = $minSectionRows;
                        }
                    }
                    foreach ($resultOptions as $sectionOrder => $sectionOptions) {
                        if (isset($sections[$sectionOrder])) {
                            $sections[$sectionOrder]['fields'] = $this->_sortByOrder($sectionOptions);
                        }
                    }
                } else {
                    $defaultOptions = $this->_getDefaultOptions();
                    if (count($defaultOptions)) {
                        $order = 1;
                        foreach ($defaultOptions as &$_defOption) {
                            $_defOption['order'] = $order++;
                        }
                        $sections = [
                            [
                                'order'     => 1,
                                'cols'      => 1,
                                'rows'      => count($defaultOptions),
                                'removable' => 1,
                                'fields'    => $defaultOptions,
                            ],
                        ];
                        $this->setFormStyle('list_div')->setAppearance('on_product_view');
                    }
                }
            }
            if (!$sections) {
                $sections = [];
            }
            $this->sections = $sections;
        }
        return $this->sections;
    }

    protected function _getDefaultOptions() {
        $optionsArr = (array)$this->getProduct()->getOptions();
        $values = [];
        foreach ($optionsArr as $option) {
            /* @var $option \Magento\Catalog\Model\Product\Option */
            $value = [];

            $value['id'] = $option->getOptionId();
            $value['item_count'] = $this->getItemCount();
            $value['option_id'] = $option->getOptionId();
            $value['title'] = $this->htmlEscape($option->getTitle());
            $value['type'] = $option->getType();
            $value['is_require'] = $option->getIsRequire();
            $value['order'] = $option->getSortOrder();
            //$value['can_edit_price'] = $this->getCanEditPrice();

            if ($option->getGroupByType() == \Magento\Catalog\Model\Product\Option::OPTION_GROUP_SELECT) {
                $itemCount = 0;
                $value['items'] = [];
                foreach ((array)$option->getValues() as $_value) {
                    /* @var $_value \Magento\Catalog\Model\Product\Option\Value */
                    $value['items'][] = [
                        'item_count' => max($itemCount, $_value->getOptionTypeId()),
                        'option_id' => $_value->getOptionId(),
                        'option_type_id' => $_value->getOptionTypeId(),
                        'title' => $this->htmlEscape($_value->getTitle()),
                        'price' => number_format($_value->getPrice(), 2, null, ''),
                        'price_type' => $_value->getPriceType(),
                        'sku' => $this->htmlEscape($_value->getSku()),
                        'order' => $_value->getSortOrder(),
                    ];
                }
                $value['item_count'] = count($value['items']);
                $value['items'] = $this->_sortByOrder($value['items']);
            } else {
                $value['price'] = number_format($option->getPrice(), 2, null, '');
                $value['price_type'] = $option->getPriceType();
                $value['sku'] = $this->htmlEscape($option->getSku());
                $value['max_characters'] = $option->getMaxCharacters();
                $value['file_extension'] = $option->getFileExtension();
                $value['image_size_x'] = $option->getImageSizeX();
                $value['image_size_y'] = $option->getImageSizeY();
            }
            $values[] = $value;
        }
        $values = $this->_sortByOrder($values);


        return $values;
    }

    protected function _sortByOrder($items) {
        if (count($items)) {
            for ($i = 1; $i < count($items); $i++) {
                $item = $items[$i];
                $j = $i - 1;
                while ($j >= 0) {
                    if ($items[$j]['order'] > $item['order']) {
                        $items[$j + 1] = $items[$j];
                        $items[$j] = $item;
                    }
                    $j--;
                }
            }
            $order = 1;
            foreach ($items as &$_item) {
                if ($_item['order'] <= $order) {
                    $_item['order'] = $order++;
                } else {
                    $order = $_item['order'];
                }
            }
        }
        return $items;
    }

    public function getProduct() {
        if (is_null($this->productModel)) {
            if ($this->_coreRegistry->registry('current_product') && (!$this->getData('product_id') || $this->_coreRegistry->registry('current_product')->getId() == $this->getData('product_id'))) {
                $product = $this->_coreRegistry->registry('current_product');
            } else {
                $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->setStoreId($this->getStoreId())->load($this->getData('product_id'));
            }
            $this->productModel = $product;
        }

        return $this->productModel;
    }

    public function getProductId() {
        if ($this->getData('product_id')) {
            return $this->getData('product_id');
        }
        return $this->getProduct()->getId();
    }

    public function getFormStyle() {
        if ($this->getData('form_style')) {
            return $this->getData('form_style');
        }
        $sections = $this->getSections();
        if (!empty($sections)) {
            return 'list_div';
        }
        return 'table_sections';
    }

    public function getAppearance() {
        if ($this->getData('appearance')) {
            return $this->getData('appearance');
        }
        $sections = $this->getSections();
        if (!empty($sections)
            || ($this->getProduct()
                && ($this->getProduct()->getTypeId() == 'configurable' || ($this->getProduct()->getTypeId() == 'bundle'))
            )
        ) {
            return 'on_product_view';
        }
        return 'on_product_view';
        //return 'popup_configure';
    }

    /**
     * @return \Itoris\DynamicProductOptions\Helper\Data
     */
    protected function getDataHelper() {
        return $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Data');
    }

    /**
     * @return \Magento\Store\Model\Store
     */
    protected function getStore(){
        return $this->_objectManager->create('\Magento\Store\Model\StoreManagerInterface')->getStore();
    }

    /**
     * @return \Magento\Framework\App\RequestInterface
     */
    protected function getRequest(){
        return $this->_objectManager->get('\Magento\Framework\App\RequestInterface');
    }

    public function htmlEscape($text) {
        return $this->getDataHelper()->htmlEscape($text);
    }
    
    protected function correctBaseImageUrl($img_src) {
        if ($img_src) {
            $pos = strpos($img_src, 'itoris/files');
            if ($pos !== false) {
                $img_src = $this->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true).substr($img_src, $pos);
                $img_src = str_replace(['http://', 'https://'], '//', $img_src);
            }
        }
        return $img_src;
    }

}