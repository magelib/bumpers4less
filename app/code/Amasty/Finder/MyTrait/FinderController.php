<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\MyTrait;


trait FinderController
{
    /**
     * @return \Amasty\Finder\Model\Finder
     */
    protected function _initFinder()
    {
        $id = $this->getRequest()->getParam('id');
        $model = $this->_objectManager->create('Amasty\Finder\Model\Finder');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('This item no longer exists.'));
                $this->_redirect('amasty_finder/*');
                return;
            }
        }
        $this->_coreRegistry->register('current_amasty_finder_finder', $model);
        return $model;
    }
}
