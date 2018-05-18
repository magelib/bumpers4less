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

namespace Itoris\DynamicProductOptions\Model\ResourceModel\Option;

class Value extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /** @var \Magento\Framework\ObjectManagerInterface|null  */
    protected $_objectManager = null;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param null $resourcePrefix
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $resourcePrefix = null
    ){
        $this->_objectManager = $objectManager;
        parent::__construct($context, $resourcePrefix);
    }

    protected function _construct() {
        $this->_init('itoris_dynamicproductoptions_option_value', 'value_id');
    }

    public function _getWriteAdapter(){
        return $this->_getConnection('write');
    }

    public function _getReadAdapter(){

        $writeAdapter = $this->_getWriteAdapter();
        if ($writeAdapter && $writeAdapter->getTransactionLevel() > 0) {
            // if transaction is started we should use write connection for reading
            return $writeAdapter;
        }
        return $this->_getConnection('read');

    }

    protected function _getLoadSelect($field, $value, $object) {
        $select = parent::_getLoadSelect($field, $value, $object);

        $storeField  = $this->_getReadAdapter()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), 'store_id'));
        $select->where($storeField . ' in(?, 0)', $this->getStoreId());

        $valueCheck = $this->_getReadAdapter()->fetchAll($select->__toString());
        if (count($valueCheck) > 1) $select->where($storeField . '=?', $this->getStoreId());

        $productId = $this->getProductId();
        if ($productId > 0) {
            $productField  = $this->_getReadAdapter()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), 'product_id'));
            $select->where($productField . '=?', $this->getProductId());
        }
        return $select;
    }

    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object) {
        parent::_afterSave($object);
        $valueCustomerGroupTable = $this->getTable('itoris_dynamicproductoptions_option_value_customergroup');
        $valueId = intval($object->getId());
        $this->_getConnection('write')->query("delete from {$valueCustomerGroupTable} where value_id=" . $valueId);

        $customerGroups = null;
        $configuration = $object->getConfiguration();
        if ($configuration) {
            $configuration = \Zend_Json::decode($configuration);
            if (isset($configuration['customer_group'])) {
                $customerGroups = $configuration['customer_group'];
            }
        }
        if ($customerGroups != "") {
            $customerGroups = explode(',', $customerGroups);
            $toInsert = [];
            foreach ($customerGroups as $group) {
                $toInsert[] = '(' . $valueId . ', ' . intval($group) . ')';
            }
            if (!empty($toInsert)) {
                $values = implode(',', $toInsert);
                $this->_getConnection('write')->query("insert into {$valueCustomerGroupTable} (value_id, group_id) values {$values}");
            }
        }
        return $this;
    }

    public function getStoreId() {
        return $this->getStore()->getStoreId();
    }

    public function getProductId() {
        if ($this->getRegistry()->registry('current_product')) return $this->getRegistry()->registry('current_product')->getId();
        return (int) $this->getRequest()->getParam('product');
    }
    /**
     * @return \Magento\Framework\App\RequestInterface
     */
    protected function getRequest(){
        return $this->_objectManager->get('Magento\Framework\App\RequestInterface');
    }

    /**
     * @return \Magento\Framework\Registry
     */
    protected  function getRegistry(){
        return $this->_objectManager->get('Magento\Framework\Registry');
    }
    /**
     * @return \Magento\Store\Model\Store
     */
    protected function getStore(){
        return $this->_objectManager->create('\Magento\Store\Model\StoreManagerInterface')->getStore();
    }
}