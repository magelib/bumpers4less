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


class Delete extends \Amasty\Finder\Controller\Adminhtml\Value
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
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            try {

                $newId = $this->_model -> newSetterId($id);
                $this->_model->deleteMapRow($id);

                $currentId = $newId;
                while (	($currentId) && ( $this->_model->isDeletable($currentId))){
                    $value = $this->_objectManager->create('Amasty\Finder\Model\Value')->load($currentId);
                    $currentId = $value->getParentId();
                    $value->delete();
                }

                $this->messageManager->addSuccess(__('You deleted the item.'));
                $this->_redirect('amasty_finder/finder/edit',['id'=>$this->_model->getId()]);
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t delete item right now. Please review the log and try again.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_redirect('amasty_finder/finder/edit', ['id' => $this->_model->getId()]);
                return;
            }
        }
        $this->messageManager->addError(__('We can\'t find a item to delete.'));
        $this->_redirect('amasty_finder/finder/edit',['id'=>$this->_model->getId()]);
    }
}
