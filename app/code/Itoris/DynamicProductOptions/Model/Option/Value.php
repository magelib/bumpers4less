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

namespace Itoris\DynamicProductOptions\Model\Option;

class Value extends \Magento\Framework\Model\AbstractModel
{
    protected $configuration = null;
    /** @var $_objectManager   \Magento\Framework\ObjectManagerInterface */
    protected  $_objectManager = null;
    /** @var $_request \Magento\Framework\App\RequestInterface */
    protected  $_request = null;
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\ObjectManagerInterface $objectManagerInterface,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_request = $request;
        $this->_objectManager = $objectManagerInterface;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct() {
        $this->_init('Itoris\DynamicProductOptions\Model\ResourceModel\Option\Value');
    }

    /**
     * @param $value \Itoris\DynamicProductOptions\Model\Option\Value
     * @return $this
     */
    public function saveValue($value) {        
        if ((int)$value->getItorisIsDelete() == 1) {
            //fixing Magento2 bug with deleting options
            $this->_objectManager->create('Magento\Catalog\Model\Product\Option\Value')->load((int)$value->getOptionTypeId())->delete();
            return;
        }
        $this->load($value->getOptionTypeId(), 'orig_value_id');
        $configurationKeys = ['title', 'image_src', 'is_selected', 'is_disabled', 'carriage_return', 'css_class', 'order', 'customer_group',
            'visibility_condition', 'visibility_action', 'visibility', 'sku_is_product_id', 'use_qty', 'tier_price', 'weight'
        ];
        $configuration = [];
        foreach ($configurationKeys as $key) {
            $configuration[$key] = $value->getData($key);
        }
        $this->setCustomerGroup($value->getCustomerGroup());
        $configuration = \Zend_Json::encode($configuration);
        $isUseGlobal = !!$this->_request->getPostValue('idpo_use_global');
        if ((int) $value->getOption()->getStoreId() == 0) $isUseGlobal = false;
        
        $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection('write');
        
        $rows = $connection->fetchAll( "SHOW COLUMNS FROM `{$resource->getTableName('catalog_product_entity')}`" );
        if ($rows[0]['Field'] == 'row_id') {
            $dummyId = (int)$connection->fetchOne("select `entity_id` from `{$resource->getTableName('catalog_product_entity')}` where `row_id`={$value->getOption()->getProductId()}");
        } else $dummyId = $value->getOption()->getProductId();
        
        if($this->getStoreId() != $value->getOption()->getStoreId()){
            /** @var  $newOptionsValue \Itoris\DynamicProductOptions\Model\Option\Value */
            $newOptionsValue = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option\Value');
            $newOptionsValue->setOrigValueId($value->getOptionTypeId())
                ->setStoreId($value->getOption()->getStoreId())
                ->setProductId($dummyId)
                ->setConfiguration($configuration)
                ;//->save();
        } else {
            if (!$this->getId()) {
                if ($isUseGlobal) return $this;
                $this->setOrigValueId($value->getOptionTypeId())
                    ->setStoreId($value->getOption()->getStoreId())
                    ->setProductId($dummyId);
            }
            /*elseif ($configuration == $this->getConfiguration()) {
                if ($isUseGlobal) $this->delete();
                return $this;
            }*/
            if (!$isUseGlobal) {
                $this->setConfiguration($configuration);//->save();
                $this->productTypePriceUpdate($value);
            } else $this->delete();
        }
        return $this;
    }

    public function getImageSrc() {
        if (is_null($this->configuration)) {
            $configuration = $this->getConfiguration();
            if ($configuration) {
                $configuration = \Zend_Json::decode($configuration);
            } else {
                $configuration = [];
            }
            $this->configuration = new \Magento\Framework\DataObject($configuration);
        }

        return $this->configuration->getImageSrc();
    }

    protected function productTypePriceUpdate($value){
        if ($value->getPriceType()) {
            /** @var \Magento\Framework\App\ResourceConnection $resource */
            $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
            $connection = $resource->getConnection('write');
            $option_type_price_table = $resource->getTableName('catalog_product_option_type_price');
            $id = (int) $connection->fetchOne("select `option_type_price_id` from {$option_type_price_table} where `option_type_id` = ".floatval($value->getOptionTypeId())." and store_id = ".intval($value->getStoreId()));
            if ($id) $connection->query("update {$option_type_price_table} set `price`=".floatval($value->getPrice()).", `price_type`='{$value->getPriceType()}' where `option_type_price_id`={$id}");
            else $connection->query("insert into {$option_type_price_table} set `option_type_id`={$value->getOptionTypeId()}, `store_id`=".intval($value->getStoreId()).", `price`=".floatval($value->getPrice()).", `price_type`='{$value->getPriceType()}'");
        }
    }
}