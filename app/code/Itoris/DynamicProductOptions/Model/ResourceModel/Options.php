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

class Options extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    protected function _construct() {
        $this->_init('itoris_dynamicproductoptions_options', 'config_id');
        //$this->_isPkAutoIncrement = false;
    }

    protected function _getLoadSelect($field, $value, $object) {
        $select = parent::_getLoadSelect('product_id', $value, $object);

        $storeField  = $this->_getReadAdapter()->quoteIdentifier(sprintf('%s.%s', $this->getMainTable(), 'store_id'));
        $select->where($storeField . '=?', $object->getStoreId());

        return $select;
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

}