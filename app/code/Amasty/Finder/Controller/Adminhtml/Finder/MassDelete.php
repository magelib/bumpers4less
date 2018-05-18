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


class MassDelete extends \Amasty\Finder\Controller\Adminhtml\Finder
{
    public function execute()
    {

        $ids = $this->getRequest()->getParam('finder_ids');
        if ($ids) {
            try {
                /**
                 * @var $collection \Amasty\Finder\Model\ResourceModel\ImportLog\Collection
                 */
                $collection = $this->_objectManager->create('Amasty\Finder\Model\ResourceModel\Finder\Collection');

                $collection->addFieldToFilter('finder_id', array('in'=>$ids));
                $collection->walk('delete');
                $this->messageManager->addSuccess(__('You deleted the finder(s).'));
                $this->_redirect('amasty_finder/finder/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t delete finder(s) right now. Please review the log and try again.').$e->getMessage()
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_redirect('amasty_finder/finder/');
                return;
            }
        }
        $this->messageManager->addError(__('We can\'t find a file(s) to delete.'));
        $this->_redirect('amasty_finder/finder/');
    }
}
