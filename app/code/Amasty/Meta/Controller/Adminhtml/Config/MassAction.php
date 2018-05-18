<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Meta
 */

namespace Amasty\Meta\Controller\Adminhtml\Config;

class MassAction extends \Amasty\Meta\Controller\Adminhtml\Config
{
    public function execute()
    {

        $ids = $this->getRequest()->getParam('location');
        $action = $this->getRequest()->getParam('action');
        if ($ids && in_array($action, ['activate', 'inactivate', 'delete'])) {
            try {
                /**
                 * @var $collection \Amasty\Meta\Model\ResourceModel\Config\Collection
                 */
                $collection = $this->_objectManager->create('Amasty\Meta\Model\ResourceModel\Config\Collection');

                $collection->addFieldToFilter('id', ['in'=>$ids]);
                $collection->walk($action);
                $this->messageManager->addSuccess(__('You deleted the location(s).'));
                $this->_redirect('*/*/');
                return;
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __('We can\'t delete item(s) right now. Please review the log and try again.').$e->getMessage()
                );
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $this->_redirect('*/*/');
                return;
            }
        }
        $this->messageManager->addError(__('We can\'t find a item(s) to delete.'));
        $this->_redirect('*/*/');
    }
}