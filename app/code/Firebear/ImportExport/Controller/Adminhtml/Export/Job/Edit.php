<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Magento\Framework\Controller\ResultFactory;

class Edit extends \Firebear\ImportExport\Controller\Adminhtml\Export\Job
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Edit constructor.
     *
     * @param \Magento\Backend\App\Action\Context                     $context
     * @param \Magento\Framework\Registry                             $coreRegistry
     * @param \Firebear\ImportExport\Model\ExportJobFactory           $exportJobFactory
     * @param \Firebear\ImportExport\Api\ExportJobRepositoryInterface $exportRepository
     * @param \Magento\Framework\View\Result\PageFactory              $resultPageFactory
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
     * @return $this|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $jobId = $this->getRequest()->getParam('entity_id');
        $model = $this->exportJobFactory->create();
        if ($jobId) {
            $model = $this->exportRepository->getById($jobId);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This job is no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('export_job', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Firebear_ImportExport::export_job');
        $resultPage->getConfig()->getTitle()->prepend(__('Export Jobs'));
        $resultPage->addBreadcrumb(__('Export'), __('Export'));
        $resultPage->addBreadcrumb(
            $jobId ? __('Edit Job') : __('New Job'),
            $jobId ? __('Edit Job') : __('New Job')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Jobs'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getTitle() : __('New Job'));

        return $resultPage;
    }
}
