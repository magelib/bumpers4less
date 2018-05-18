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

namespace Itoris\DynamicProductOptions\Model\Rewrite\ResourceModel;


class Option extends \Magento\Catalog\Model\ResourceModel\Product\Option
{
    /** @var \Magento\Framework\ObjectManagerInterface|null  */
    protected $_objectManager = null;
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Model\ResourceModel\Db\Context $context
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $config
     * @param null $resourcePrefix
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        \Magento\Directory\Model\CurrencyFactory $currencyFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        $resourcePrefix = null
    ) {
        $this->_objectManager = $objectManager;
        parent::__construct($context, $currencyFactory, $storeManager, $config, $resourcePrefix);
    }

    public function duplicate(\Magento\Catalog\Model\Product\Option $object, $oldProductId, $newProductId) {
        $write  = $this->_getWriteAdapter();
        $read   = $this->_getReadAdapter();

        $optionsCond = [];
        $optionsData = [];

        // read and prepare original product options
        $select = $read->select()
            ->from($this->getTable('catalog/product_option'))
            ->where('product_id = ?', $oldProductId);

        $query = $read->query($select);

        while ($row = $query->fetch()) {
            $optionsData[$row['option_id']] = $row;
            $optionsData[$row['option_id']]['product_id'] = $newProductId;
            unset($optionsData[$row['option_id']]['option_id']);
        }

        // insert options to duplicated product
        foreach ($optionsData as $oId => $data) {
            $write->insert($this->getMainTable(), $data);
            $optionsCond[$oId] = $write->lastInsertId($this->getMainTable());
        }

        // copy options prefs
        foreach ($optionsCond as $oldOptionId => $newOptionId) {
            // title
            $table = $this->getTable('catalog/product_option_title');

            $select = $this->_getReadAdapter()->select()
                ->from($table, [new \Zend_Db_Expr($newOptionId), 'store_id', 'title'])
                ->where('option_id = ?', $oldOptionId);

            $insertSelect = $write->insertFromSelect(
                $select,
                $table,
                ['option_id', 'store_id', 'title'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
            );
            $write->query($insertSelect);

            // price
            $table = $this->getTable('catalog/product_option_price');

            $select = $read->select()
                ->from($table, [new \Zend_Db_Expr($newOptionId), 'store_id', 'price', 'price_type'])
                ->where('option_id = ?', $oldOptionId);

            $insertSelect = $write->insertFromSelect(
                $select, $table,
                [
                    'option_id',
                    'store_id',
                    'price',
                    'price_type'
                ],
                \Magento\Framework\DB\Adapter\AdapterInterface::INSERT_ON_DUPLICATE
            );
            $write->query($insertSelect);

            /** dynamic options  */
            $dynamicOption = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option')->load($oldOptionId, 'orig_option_id');
            if ($dynamicOption->getId()) {
                $dynamicOptionOldId = $dynamicOption->getId();
                $dynamicOption->setId(null)
                    ->setOrigOptionId($newOptionId)
                    ->setProductId($newProductId)
                    ->save();
                $dynamicOptionNewId = $dynamicOption->getId();

                $tableCustomerGroups = $this->getTable('itoris_dynamicproductoptions_option_customergroup');
                $columns = [
                    new\Zend_Db_Expr($dynamicOptionNewId),
                    'group_id',
                ];
                $select = $this->_getReadAdapter()->select()
                    ->from($tableCustomerGroups, [])
                    ->where('option_id = ?', $dynamicOptionOldId)
                    ->columns($columns);
                $insertSelect = $write->insertFromSelect($select, $tableCustomerGroups,
                    ['option_id', 'group_id']);
                $write->query($insertSelect);
            }
            /** dynamic options */

            $object->getValueInstance()->duplicate($oldOptionId, $newOptionId);
        }

        return $object;
    }

    public function deletePrices($optionId) {
        $this->_getWriteAdapter()->delete(
            $this->getTable('catalog/product_option_price'),
            [
                'option_id = ?' => $optionId,
                'store_id = ?' => $this->getStoreId()
            ]
        );

        return $this;
    }

    public function deleteTitles($optionId) {
        $this->_getWriteAdapter()->delete(
            $this->getTable('catalog/product_option_title'),
            [
                'option_id = ?' => $optionId,
                'store_id = ?' => $this->getStoreId()
            ]
        );

        return $this;
    }

    public function getStoreId() {
        return (int)$this->getRequest()->getParam('store');
    }
    /**
     * @return \Magento\Framework\App\RequestInterface
     */
    protected function getRequest(){
        return $this->_objectManager->get('Magento\Framework\App\RequestInterface');
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