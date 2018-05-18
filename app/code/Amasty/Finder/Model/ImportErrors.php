<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Model;


class ImportErrors extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Amasty\Finder\Model\ResourceModel\ImportErrors');
    }

    public function error($fileId, $line, $message)
    {
        $this
            ->setImportFileLogId($fileId)
            ->setCreatedAt(date('Y-m-d H:i:s'))
            ->setLine($line)
            ->setMessage($message)
            ->save();
    }

    public function archiveErrorHistory($fileId, $historyFileId)
    {
        $this->getResource()->archiveErrorHistory($fileId, $historyFileId);
    }
}
