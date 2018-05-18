<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Finder\Model\ResourceModel\ImportLog;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Amasty\Finder\Model\ImportLog', 'Amasty\Finder\Model\ResourceModel\ImportLog');
    }

    public function orderForImport()
    {
        $this
            ->addOrder('status',self::SORT_ORDER_DESC)
            ->addOrder('started_at', self::SORT_ORDER_ASC)
            ->addOrder('file_id', self::SORT_ORDER_ASC);
        return $this;
    }
}
