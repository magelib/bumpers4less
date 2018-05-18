<?php
namespace Magedelight\Arp\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Backend\App\Action;
 
class Index extends \Magedelight\Arp\Controller\Adminhtml\Index\Rule
{
    public function execute()
    {
        $this->_initAction()->_addBreadcrumb(__('Advance raleted product'), __('Advance raleted product'));
        $this->_view->getPage()->getConfig()->getTitle()->prepend(__('Product Rule'));
        $this->_view->renderLayout('root');
    }
}
