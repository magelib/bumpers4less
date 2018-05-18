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


class Save  extends \Amasty\Finder\Controller\Adminhtml\Value
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
        $chainId = $this->getRequest()->getParam('id');
        //$id = $this->_objectManager->create('Amasty\Finder\Model\Finder')->newSetterId($chainId);
        $data = $this->getRequest()->getPostValue();
        if ($data && ($chainId || isset($data['new_finder']))) {
            try {
                //$model = $this->_objectManager->create('Amasty\Finder\Model\Value');

                $inputFilter = new \Zend_Filter_Input([],[],$data);
                $data = $inputFilter->getUnescaped();
                $session = $this->_objectManager->get('Magento\Backend\Model\Session');
                $session->setPageData($data);


                if ($chainId){
                    $newData = [];
                    foreach ($data as $element => $arrayValue)
                    {
                        if (substr($element, 0, 6) == 'label_')
                        {
                            $valueId = (int)(substr($element,6));
                            $value = $this->_objectManager->create('Amasty\Finder\Model\Value')->load($valueId);
                            $dropdownId =  $value->getDropdownId();
                            unset($data[$element]);
                            $newData['label_'.$dropdownId] = $arrayValue;
                        }
                    }
                    $data = array_merge($data, $newData);

                    $model = $this->_objectManager->create('Amasty\Finder\Model\Finder');
                    $newId = $model ->newSetterId($chainId);
                    $model->deleteMapRow($chainId);

                    $currentId = $newId;
                    $value = $this->_objectManager->create('Amasty\Finder\Model\Value')->load($currentId);
                    $dropdownId =  $value->getDropdownId();
                    while (    ($currentId) && ($model->isDeletable($currentId))){
                        $value = $this->_objectManager->create('Amasty\Finder\Model\Value')->load($currentId);
                        $currentId = $value->getParentId();
                        $dropdownId =  $value->getDropdownId();
                        $value->delete();
                    }

                    $this->messageManager->addSuccess(__('Product has been deleted'));
                }
                $model = $this->_objectManager->create('Amasty\Finder\Model\Value');
                $finderId = $model->saveNewFinder($this->_objectManager, $data);
                $this->messageManager->addSuccess(__('Record have been successfully saved'));
                $session->setPageData(false);

                $this->_redirect('*/finder/edit', ['id' => $finderId]);
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $id = (int)$this->getRequest()->getParam('id');
                if (!empty($id)) {
                    $this->_redirect('amasty_finder/value/edit', ['id' => $id, 'finder_id' => $this->_model->getId()]);
                } else {
                    $this->_redirect('amasty_finder/value/new', ['finder_id' => $this->_model->getId()]);
                }
                return;
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
                $this->messageManager->addError(
                    __('Something went wrong while saving the item data. Please review the error log.')
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_objectManager->get('Magento\Backend\Model\Session')->setPageData($data);
                $this->_redirect('*/*/edit', ['id' => $this->getRequest()->getParam('id'), 'finder_id' => $this->_model->getId()]);
                return;
            }
        }
        $this->_redirect('amasty_finder/finder/edit', ['id' => $this->_model->getId()]);
    }
}
