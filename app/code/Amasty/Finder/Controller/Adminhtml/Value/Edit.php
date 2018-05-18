<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Finder\Controller\Adminhtml\Value;


class Edit extends \Amasty\Finder\Controller\Adminhtml\Value
{
    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $this->_initModel();

        $newId = $this->getRequest()->getParam('id');
        $id = $this->_model->newSetterId($newId);
        $model = $this->_objectManager->create('Amasty\Finder\Model\Value');

        if ($id) {
            $model->load($id);
            if (!$model->getId()) {
                $this->messageManager->addError(__('Record does not exist'));
                $this->_redirect('amasty_finder/finder/edit', ['id'=>$this->_model->getId()]);
                return;
            }
        }
        // set entered data if was error when we do save
        $data = $this->_objectManager->get('Magento\Backend\Model\Session')->getPageData(true);
        if (!empty($data)) {
            $model->addData($data);
        }
        $model->setFinder($this->_model);
        $this->_coreRegistry->register('current_amasty_finder_value', $model);
        $this->_initAction();

        if($model->getId()) {
            $title = __('Edit Product');
        } else {
            $title = __("Add new Product");
        }
        $this->_view->getPage()->getConfig()->getTitle()->prepend($title);

        $this->_view->renderLayout();
    }
}
