<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

class Index extends \Firebear\ImportExport\Controller\Adminhtml\Export\Job
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Index constructor.
     *
     * @param \Magento\Backend\App\Action\Context                      $context
     * @param \Magento\Framework\Registry                              $coreRegistry
     * @param \Firebear\ImportExport\Model\ExportJobFactory            $exportJobFactory
     * @param \Firebear\ImportExport\Api\ExportJobRepositoryInterface $exportRepository
     * @param \Magento\Framework\View\Result\PageFactory               $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Firebear\ImportExport\Model\ExportJobFactory $exportJobFactory,
        \Firebear\ImportExport\Api\ExportJobRepositoryInterface $exportRepository,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry, $exportJobFactory, $exportRepository);
    }
    
    /**
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Firebear_ImportExport::export_job')
            ->addBreadcrumb(__('Export Jobs'), __('Export Jobs'));
        $resultPage->getConfig()->getTitle()->prepend(__('Export Jobs'));

        return $resultPage;
    }
}
