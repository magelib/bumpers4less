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


class Value extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Amasty\Finder\Model\ResourceModel\Value');

    }

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $data
     *
     * @return mixed
     */
    public function saveNewFinder(\Magento\Framework\ObjectManagerInterface $objectManager,array $data)
    {
        return $this->getResource()->saveNewFinder($objectManager, $data);
    }

    public function getSkuById($newId ,$id)
    {
        return $this->getResource()->getSkuById($newId, $id);
    }
}
