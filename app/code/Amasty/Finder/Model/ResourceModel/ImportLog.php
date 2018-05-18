<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Model\ResourceModel;


class ImportLog extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('amasty_finder_import_file_log', 'file_id');
    }

    public function addUniqueFile($fileName, $finderId)
    {
        $this->getConnection()->insertOnDuplicate($this->getMainTable(), array('file_name'=>$fileName, 'finder_id'=>$finderId));
    }

    public function hasIssetReplaceFile($finderId)
    {
        $db = $this->getConnection();
        $select = $db->select()->from($this->getMainTable(), "COUNT(*)")->where('finder_id = '.$finderId.' AND file_name = "replace.csv"');
        return (bool) $db->fetchOne($select);
    }
}
