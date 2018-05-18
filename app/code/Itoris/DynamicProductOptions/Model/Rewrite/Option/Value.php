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

namespace Itoris\DynamicProductOptions\Model\Rewrite\Option;

class Value extends \Magento\Catalog\Model\Product\Option\Value
{
    /** @var \Magento\Framework\ObjectManagerInterface|null  */
    protected $_objectManager = null;
    protected $dpoConfig = null;
    protected $dpoProduct = null;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\Value\CollectionFactory $valueCollectionFactory
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ResourceModel\Product\Option\Value\CollectionFactory $valueCollectionFactory,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        $this->_valueCollectionFactory = $valueCollectionFactory;
        parent::__construct(
            $context,
            $registry,
            $valueCollectionFactory,
            $resource,
            $resourceCollection,
            $data
        );
    }
    public function getValuesCollection(\Magento\Catalog\Model\Product\Option $option) {
        $collection = parent::getValuesCollection($option);
        $this->addItorisFilter($collection);
        return $collection;
    }

    public function getValuesByOption($optionIds, $option_id, $store_id) {
        $collection = parent::getValuesByOption($optionIds, $option_id, $store_id);
        $this->addItorisFilter($collection);

        return $collection;
    }

    public function getCollection() {
        $collection = parent::getCollection();
        $this->addItorisFilter($collection);
        return $collection;
    }

    public function addItorisFilter($collection) {
        if (($this->getItorisHelper()->isFrontend() || $this->getRequest()->getControllerName() == 'order_create') && $this->getItorisHelper()->isEnabledOnFrontend()) {
            $dynamicOptionValueTable = $collection->getTable('itoris_dynamicproductoptions_option_value');
            $dynamicOptionValueCustomerGroupTable = $collection->getTable('itoris_dynamicproductoptions_option_value_customergroup');
            $customerGroupId = (int)$this->getItorisHelper()->getCustomerGroupId();
            $collection->getSelect()
                ->joinLeft(
                    ['dynamic_option_values' => $dynamicOptionValueTable],
                    'dynamic_option_values.orig_value_id = main_table.option_type_id', []
                )
                ->joinLeft(
                    ['dynamic_option_values_customergroups' => $dynamicOptionValueCustomerGroupTable],
                    'dynamic_option_values_customergroups.value_id = dynamic_option_values.value_id',
                    ['dynamic_customer_group' => 'group_concat(dynamic_option_values_customergroups.group_id separator ",")']
                )
                ->having('dynamic_customer_group is null or find_in_set(?, dynamic_customer_group)', $customerGroupId)
                ->group('main_table.option_type_id');
            //    $wherePart = $collection->getSelect()->getPart('where');
            //    foreach ($wherePart as $key => $part) {
            //        $wherePart[$key] = str_replace('product_id', 'main_table.product_id', $part);
            //    }
            //    $collection->getSelect()->setPart('where', $wherePart);
            //    $collection->clear();
        }
        return $collection;
    }
    
    public function getPrice($flag = false)
    {
        $price = parent::getPrice($flag);
        if ($this->getItorisHelper()->isEnabledOnFrontend()) {
            $this->dpoConfig = $this->getDpoConfig();
            if ($this->dpoConfig) {                
                if ($this->isSkuProduct()) {
                    $this->dpoProduct = $this->getDpoProduct();
                    if ($this->dpoProduct->getId()) {
                        $_price =  $this->dpoProduct->getFinalPrice() ? $this->dpoProduct->getFinalPrice() : $this->dpoProduct->getPrice();
                        if ($_price) $price = $_price;
                    }
                }
            }
        }
        return $price;

    }
    
    public function getTitle()
    {
        $title = parent::getTitle();
        if ($this->getItorisHelper()->isEnabledOnFrontend()) {
            $this->dpoConfig = $this->getDpoConfig();
            if ($this->dpoConfig) {
                if ($this->isSkuProduct()) {
                    $this->dpoProduct = $this->getDpoProduct();
                    if ($this->dpoProduct->getId()) {
                        $_title =  $this->dpoProduct->getName();
                        if ($_title) $title = $_title;
                    }
                }
            }
        }
        return (string)__($title);
    }
    
    public function getSku()
    {
        $sku = parent::getSku();
        if ($this->getItorisHelper()->isEnabledOnFrontend()) {
            $this->dpoConfig = $this->getDpoConfig();
            if ($this->dpoConfig) {
                if ($this->isLinkedProduct()) {
                    $this->dpoProduct = $this->getDpoProduct();
                    if ($this->dpoProduct->getId()) {
                        $_sku =  $this->dpoProduct->getSku();
                        if ($_sku) $sku = $_sku;
                    }
                }
            }
        }
        return $sku;
    }   
    
    public function isSkuProduct() {
        $this->dpoConfig = $this->getDpoConfig();
        return $this->dpoConfig && isset($this->dpoConfig['sku_is_product_id']) && isset($this->dpoConfig['sku_is_product_id_linked']) && $this->dpoConfig['sku_is_product_id'] && $this->dpoConfig['sku_is_product_id_linked'];
    }
    
    public function isLinkedProduct() {
        $this->dpoConfig = $this->getDpoConfig();
        return $this->dpoConfig && isset($this->dpoConfig['sku_is_product_id']) && $this->dpoConfig['sku_is_product_id'];
    }
    
    public function getDpoConfig() {
        if (!$this->dpoConfig) {
            $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
            $con = $res->getConnection('read');
            if (!$this->dpoConfig) $this->dpoConfig = json_decode($con->fetchOne("select `configuration` from {$res->getTableName('itoris_dynamicproductoptions_option_value')} where `orig_value_id`={$this->getOptionTypeId()} and `store_id`={$this->getStore()->getId()}"), true);
            if (!$this->dpoConfig) $this->dpoConfig = json_decode($con->fetchOne("select `configuration` from {$res->getTableName('itoris_dynamicproductoptions_option_value')} where `orig_value_id`={$this->getOptionTypeId()} and `store_id`=0"), true);
        }
        return $this->dpoConfig;
    }
    
    public function getDpoProduct() {
        $this->dpoConfig = $this->getDpoConfig();
        if (!$this->dpoProduct && $this->dpoConfig) {
            $this->dpoProduct = $this->_objectManager->create('Magento\Catalog\Model\Product')->setStoreId($this->getStore()->getId())->load((int)$this->dpoConfig['sku']);
        }
        return $this->dpoProduct;
    }
    
    /**
     * @return \Magento\Store\Model\Store
     */
    protected function getStore(){
        return $this->_objectManager->get('\Magento\Store\Model\StoreManagerInterface')->getStore();
    }
    /**
     * @return \Magento\Framework\App\RequestInterface
     */
    protected function getRequest(){
        return $this->_objectManager->get('\Magento\Framework\App\RequestInterface');
    }

    /**
     * @return \Itoris\DynamicProductOptions\Helper\Data
     */
    public function getItorisHelper(){
        return $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Data');
    }
}