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


class ErrorsGrid extends \Amasty\Finder\Controller\Adminhtml\Finder
{
    public function execute()
    {
        $this->_forward('errors');
        /*
        $fileId     = (int) $this->getRequest()->getParam('file_id');
        $fileState     = $this->getRequest()->getParam('file_state');
        if($fileState == \Amasty\Finder\Helper\Data::FILE_STATE_PROCESSING) {
            $model = 'ImportLog';
        } else {
            $model = 'ImportHistory';
        }
        $model  = $this->_objectManager->create('Amasty\Finder\Model\\'.$model)->load($fileId);
        if (!$model->getId()) {
            $this->messageManager->addError(__('Record does not exist.'));
            $this->_redirect('amasty_finder/finder/');
            return;
        }
        $this->_coreRegistry->register('amfinder_importFile', $model);

        $resultLayout = $this->resultFactory->create(ResultFactory::TYPE_LAYOUT);
        return $resultLayout;*/
    }
}
