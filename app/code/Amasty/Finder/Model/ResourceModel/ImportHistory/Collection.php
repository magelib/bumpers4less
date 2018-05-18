<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */


/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Finder\Model\ResourceModel\ImportHistory;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected function _construct()
    {
        $this->_init('Amasty\Finder\Model\ImportHistory', 'Amasty\Finder\Model\ResourceModel\ImportHistory');
    }
}
