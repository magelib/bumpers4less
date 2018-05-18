<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Block\Adminhtml\Finder\Edit\Tab\Import;

class HistoryGrid extends \Amasty\Finder\Block\Adminhtml\Finder\Edit\Tab\AbstractGrid
{
    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $finder = $this->getFinder();
        /** @var \Amasty\Finder\Model\ResourceModel\ImportLog\Collection $collection */
        $collection = $this->objectManager->create('Amasty\Finder\Model\ResourceModel\ImportHistory\Collection');
        $collection->addFieldToFilter('finder_id', $finder->getId());
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }


}
