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

namespace Itoris\DynamicProductOptions\Model\Rewrite\ResourceModel\Option;

class Value extends \Magento\Catalog\Model\ResourceModel\Product\Option\Value
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

    public function duplicate(\Magento\Catalog\Model\Product\Option\Value $object, $oldOptionId, $newOptionId)
    {
        $writeAdapter = $this->_getWriteAdapter();
        $readAdapter  = $this->_getReadAdapter();
        $select       = $readAdapter->select()
            ->from($this->getMainTable())
            ->where('option_id = ?', $oldOptionId);
        $valueData = $readAdapter->fetchAll($select);

        $valueCond = [];

        foreach ($valueData as $data) {
            $optionTypeId = $data[$this->getIdFieldName()];
            unset($data[$this->getIdFieldName()]);
            $data['option_id'] = $newOptionId;

            $writeAdapter->insert($this->getMainTable(), $data);
            $valueCond[$optionTypeId] = $writeAdapter->lastInsertId($this->getMainTable());
        }

        unset($valueData);

        /** dynamic options */
        $dynamicOption = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option')->load($newOptionId, 'orig_option_id');
        $productId = $dynamicOption->getProductId();
        /** dynamic options */
        foreach ($valueCond as $oldTypeId => $newTypeId) {
            // price
            $priceTable = $this->getTable('catalog_product_option_type_price');
            $columns= [
                new \Zend_Db_Expr($newTypeId),
                'store_id', 'price', 'price_type'
            ];

            $select = $readAdapter->select()
                ->from($priceTable, [])
                ->where('option_type_id = ?', $oldTypeId)
                ->columns($columns);
            $insertSelect = $writeAdapter->insertFromSelect($select, $priceTable,
                ['option_type_id', 'store_id', 'price', 'price_type']);
            $writeAdapter->query($insertSelect);

            // title
            $titleTable = $this->getTable('catalog_product_option_type_title');
            $columns= [
                new \Zend_Db_Expr($newTypeId),
                'store_id', 'title'
            ];

            $select = $this->_getReadAdapter()->select()
                ->from($titleTable, [])
                ->where('option_type_id = ?', $oldTypeId)
                ->columns($columns);
            $insertSelect = $writeAdapter->insertFromSelect($select, $titleTable,
                ['option_type_id', 'store_id', 'title']);
            $writeAdapter->query($insertSelect);

            /** dynamic options */
            if ($productId) {
                $dynamicValue = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option\Value')->load($oldTypeId, 'orig_value_id');
                if ($dynamicValue->getId()) {
                    $dynamicValueOldId = $dynamicValue->getId();
                    $dynamicValue->setId(null)
                        ->setOrigValueId($newTypeId)
                        ->setProductId($productId)
                        ->save();
                    $dynamicValueNewId = $dynamicValue->getId();

                    $tableCustomerGroups = $this->getTable('itoris_dynamicproductoptions_option_value_customergroup');
                    $columns= [
                        new \Zend_Db_Expr($dynamicValueNewId),
                        'group_id',
                    ];
                    $select = $this->_getReadAdapter()->select()
                        ->from($tableCustomerGroups, [])
                        ->where('value_id = ?', $dynamicValueOldId)
                        ->columns($columns);
                    $insertSelect = $writeAdapter->insertFromSelect($select, $tableCustomerGroups,
                        ['value_id', 'group_id']);
                    $writeAdapter->query($insertSelect);
                }
            }
            /** dynamic options */
        }

        return $object;
    }

    public function deleteValue($optionId) {
        $statement = $this->_getReadAdapter()->select()
            ->from($this->getTable('catalog_product_option_type_value'))
            ->where('option_id = ?', $optionId);

        $rowSet = $this->_getReadAdapter()->fetchAll($statement);

        $titles = [0];
        foreach ($rowSet as $optionType) {
            $titles[] = $optionType['option_type_id'];
        }

        $option_type_title_table = $this->getTable('catalog_product_option_type_title');
        $this->_getConnection('write')->query("delete from {$option_type_title_table} where option_type_id in (".implode(',',$titles).') and store_id = '.$this->getStoreId());

        $option_type_price_table = $this->getTable('catalog_product_option_type_price');
        $this->_getConnection('write')->query("delete from {$option_type_price_table} where option_type_id in (".implode(',',$titles).') and store_id = '.$this->getStoreId());


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