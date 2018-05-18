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

namespace Itoris\DynamicProductOptions\Model\ResourceModel;

class Option extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
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
        $this->_init('itoris_dynamicproductoptions_option', 'option_id');
    }

    protected function _getLoadSelect($field, $value, $object) {
        $select = parent::_getLoadSelect($field, $value, $object);

        $storeField  = $this->_getReadAdapter()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), 'store_id'));
        $select->where($storeField . '=?', $this->getStoreId());

        $productField  = $this->_getReadAdapter()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), 'product_id'));
        $select->where($productField . '=?', $this->getProductId());

        return $select;
    }

    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object) {
        parent::_afterSave($object);
        $optionCustomerGroupTable = $this->getTable('itoris_dynamicproductoptions_option_customergroup');
        $optionId = intval($object->getId());
        $this->_getConnection('write')->query("delete from {$optionCustomerGroupTable} where option_id=" . $optionId);
        //$customerGroups = $object->getCustomerGroup();
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
                $toInsert[] = '(' . $optionId . ', ' . intval($group) . ')';
            }
            if (!empty($toInsert)) {
                $values = implode(',', $toInsert);
                $this->_getConnection('write')->query("insert into {$optionCustomerGroupTable} (option_id, group_id) values {$values}");
            }
        }
        return $this;
    }

    public function getStoreId() {
        return (int)$this->getRequest()->getParam('store');
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
     * @return false|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function _getWriteAdapter(){
        return $this->_getConnection('write');
    }

    /**
     * @return false|\Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function _getReadAdapter(){

        $writeAdapter = $this->_getWriteAdapter();
        if ($writeAdapter && $writeAdapter->getTransactionLevel() > 0) {
            // if transaction is started we should use write connection for reading
            return $writeAdapter;
        }
        return $this->_getConnection('read');

    }
}