<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Block\Adminhtml\Finder\Edit\Tab\Products;

class Grid extends \Amasty\Finder\Block\Adminhtml\Finder\Edit\Tab\AbstractGrid
{

    /**
     * @return $this
     */
    protected function _prepareCollection()
    {
        $finder = $this->getFinder();
        /** @var \Amasty\Finder\Model\ResourceModel\Value\Collection $collection */
        $collection = $this->objectManager->create('Amasty\Finder\Model\ResourceModel\Value\Collection');
        $collection->joinAllFor($finder);
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
}
