<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Controller\Adminhtml\Finder;
use Magento\Framework\Controller\ResultFactory;


class ImportHistoryGrid extends \Amasty\Finder\Controller\Adminhtml\Finder
{
    use \Amasty\Finder\MyTrait\FinderController;
    public function execute()
    {
        $this->_initFinder();
        /** @var \Magento\Framework\View\Result\Layout $resultLayout */
        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        return $resultLayout;
    }
}
