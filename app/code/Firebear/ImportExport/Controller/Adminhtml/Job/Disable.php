<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Registry;
use Firebear\ImportExport\Api\Data\ImportInterface;

class Disable extends JobController
{
    /**
     * @var ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * Delete constructor.
     *
     * @param Context                $context
     * @param Registry               $coreRegistry
     * @param JobFactory             $jobFactory
     * @param JobRepositoryInterface $repository
     * @param ForwardFactory         $resultForwardFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        ForwardFactory $resultForwardFactory
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
    }

    /**
     * Delete a job
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // check if we know what should be deleted
        $jobId = $this->getRequest()->getParam('entity_id');
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($jobId) {
            try {
                $job = $this->repository->getById($jobId);
                $job->setIsActive(ImportInterface::STATUS_DISABLED);
                $this->repository->save($job);
                // display success message
                $this->messageManager->addSuccessMessage(__('The job changed status.'));
                // go to grid

                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                // display error message
                $this->messageManager->addErrorMessage($e->getMessage());
                // go back to edit form
                return $resultRedirect->setPath('*/*/');
            }
        }
        // display error message
        $this->messageManager->addErrorMessage(__('We can\'t find a job to сhange status.'));
        // go to grid
        return $resultRedirect->setPath('*/*/');
    }
}
