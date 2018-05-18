<?php
namespace Magedelight\Arp\Controller\Adminhtml\Index;
 
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\App\Action\Context;
 
class MassDelete extends \Magento\Backend\App\Action
{
    /**
     * @var Filter
     */
    public $filter;
 
    /**
     * @var CollectionFactory
     */
    public $collectionFactory;
    
    public function __construct(
        Context $context,
        \Magento\Ui\Component\MassAction\Filter $filter,
        \Magedelight\Arp\Model\ResourceModel\Productrule\CollectionFactory $collectionFactory
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context);
    }
    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $collectionSize = $collection->getSize(); 
        foreach ($collection as $item) {
            $this->dataDelete($item);
        }
        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $collectionSize));
 
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        return $resultRedirect->setPath('*/*/');
    }
    
    private function dataDelete($item)
    {
         $item->delete();
    }
}
