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

namespace Itoris\DynamicProductOptions\Model\Rewrite;


class Option extends \Magento\Catalog\Model\Product\Option
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface|null
     */
    protected $_objectManager = null;
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory
     * @param \Magento\Catalog\Model\Product\Option\Value $productOptionValue
     * @param \Magento\Catalog\Model\Product\Option\Type\Factory $optionFactory
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Model\Product\Option\Validator\Pool $validatorPool
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        \Magento\Framework\Api\AttributeValueFactory $customAttributeFactory,
        \Magento\Catalog\Model\Product\Option\Value $productOptionValue,
        \Magento\Catalog\Model\Product\Option\Type\Factory $optionFactory,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Model\Product\Option\Validator\Pool $validatorPool,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $productOptionValue,
            $optionFactory,
            $string,
            $validatorPool,
            $resource,
            $resourceCollection,
            $data
        );
    }

    public function getProductOptionCollection(\Magento\Catalog\Model\Product $product) {
        $collection = parent::getProductOptionCollection($product);
        if (($this->getItorisHelper()->isFrontend() || $this->getRequest()->getControllerName() == 'order_create') && $this->getItorisHelper()->isEnabledOnFrontend()) {
            $dynamicOptionTable = $collection->getTable('itoris_dynamicproductoptions_option');
            $dynamicOptionCustomerGroupTable = $collection->getTable('itoris_dynamicproductoptions_option_customergroup');
            $customerGroupId = (int)$this->getItorisHelper()->getCustomerGroupId();
            $collection->getSelect()
                ->joinLeft(
                    ['dynamic_options' => $dynamicOptionTable],
                    'dynamic_options.orig_option_id = main_table.option_id', []
                )
                ->joinLeft(
                    ['dynamic_options_customergroups' => $dynamicOptionCustomerGroupTable],
                    'dynamic_options_customergroups.option_id = dynamic_options.option_id',
                    ['dynamic_customer_group' => 'group_concat(dynamic_options_customergroups.group_id separator ",")']
                )
                ->having('dynamic_customer_group is null or find_in_set(?, dynamic_customer_group)', $customerGroupId)
                ->group('main_table.option_id');
            $wherePart = $collection->getSelect()->getPart('where');
            foreach ($wherePart as $key => $part) {
                $wherePart[$key] = str_replace('product_id', 'main_table`.`product_id', $part);
            }
            $collection->getSelect()->setPart('where', $wherePart);
            $collection->clear();
            $collection->addValuesToResult($product->getStoreId());
        }


        return $collection;
    }

    public function getTitle() {
        $title = parent::getTitle();
        return (string)__($title);
    }
    
    public function groupFactory($type) {
        if (is_null($type)) {
            $type = $this->getType();
            if (!$type) {
                $type = 'field';
            }
        }

        return parent::groupFactory($type);
    }

    public function duplicate($fromProductId, $newProductId) {
        $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $con = $res->getConnection('read');
        $configs = $con->fetchAll("select `config_id`, `store_id` from {$res->getTableName('itoris_dynamicproductoptions_options')} where `product_id`={$fromProductId} order by `store_id`");
        $options = [];
        foreach($configs as $config) $options[$config['store_id']] = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Options')->setStoreId($config['store_id'])->load($fromProductId);
        if (count($options) > 0) $this->_objectManager->get('Itoris\DynamicProductOptions\Helper\Data')->applyToProduct($newProductId, $options);

        //parent::duplicate($oldProductId, $newProductId);
        //$this->_duplicateDynamicData($oldProductId, $newProductId);
        return $this;
    }

    protected function _duplicateDynamicData($oldProductId, $newProductId) {
        $options = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Options')->load($oldProductId);
        if ($options->getId()) {
            $options->setId(null)
                ->setProductId($newProductId)
                ->save();
        }
        return $this;
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

    /**
     * @return \Itoris\DynamicProductOptions\Helper\Data
     */
    public function getItorisHelper(){
        return $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Data');
    }
    
    public function getProduct() {
        $product = parent::getProduct();
        if (!$product && (int) $this->getProductId()) {
            return $this->_objectManager->create('Magento\Catalog\Model\Product')->load($this->getProductId());
        }
        return $product;
    }
    
    public function getProductSku(){
        $sku = parent::getProductSku();
        if (!$sku) {
            $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($this->getProductId());
            return $product->getSku();
        }
        return $sku;
    }

}