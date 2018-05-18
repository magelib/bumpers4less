<?php
 
namespace Magedelight\Arp\Controller\Catalog;
 
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class PageVisitor extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    public $ruleFactory;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    public $resultPageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magedelight\Arp\Model\ProductruleFactory $ruleFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->ruleFactory = $ruleFactory;
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    

    /**
     * Product view action
     *
     * @return \Magento\Framework\Controller\Result\Forward|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {   
        $ruleId = $this->getRequest()->getParam('rule_id');
        if ($ruleId) {
            $model = $this->ruleFactory->create();
            $model->load($ruleId);
            $currentPageVisitor = $model->getPageVisitor();
            $updatePageVisitor = $currentPageVisitor + 1 ; 
            $model->setData('page_visitor', $updatePageVisitor);
            $model->save();
        }    
    }
}