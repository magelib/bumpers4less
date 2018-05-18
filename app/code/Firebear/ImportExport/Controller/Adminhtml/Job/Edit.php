<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Magento\Framework\Controller\ResultFactory;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;

class Edit extends \Firebear\ImportExport\Controller\Adminhtml\Job
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Newedit constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context
     * @param \Magento\Framework\Registry                $coreRegistry
     * @param JobFactory                                 $jobFactory
     * @param JobRepositoryInterface                     $repository
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
    }

    /**
     * @return $this|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $jobId = $this->getRequest()->getParam('entity_id');
        $model = $this->jobFactory->create();
        if ($jobId) {
            $model = $this->repository->getById($jobId);
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('This job is no longer exists.'));
                /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();

                return $resultRedirect->setPath('*/*/');
            }
        }

        $this->coreRegistry->register('import_job', $model);

        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Firebear_ImportExport::import_job');
        $resultPage->getConfig()->getTitle()->prepend(__('Import Jobs'));
        $resultPage->addBreadcrumb(__('Import'), __('Import'));
        $resultPage->addBreadcrumb(
            $jobId ? __('Edit Job') : __('New Job'),
            $jobId ? __('Edit Job') : __('New Job')
        );
        $resultPage->getConfig()->getTitle()->prepend(__('Jobs'));
        $resultPage->getConfig()->getTitle()->prepend($model->getId() ? $model->getTitle() : __('New Job'));

        return $resultPage;
    }
}
