<?php

/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

/**
 * Cms page grid inline edit controller
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class InlineEdit extends JobController
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * InlineEdit constructor.
     *
     * @param Context                $context
     * @param Registry               $coreRegistry
     * @param JobFactory             $jobFactory
     * @param JobRepositoryInterface $repository
     * @param JsonFactory            $jsonFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error      = false;
        $messages   = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && !empty($postItems))) {
            return $resultJson->setData(
                [
                    'messages' => [__('Please correct the data sent.')],
                    'error' => true,
                ]
            );
        }

        foreach ($postItems as $item) {
            $jobId = $item['entity_id'];
            try {
                if ($jobId) {
                    $job = $this->repository->getById($jobId);
                    $this->validatePost($item, $error, $messages);
                    $job->setData($item);
                    $this->repository->save($job);
                }
            } catch (LocalizedException $e) {
                $messages[] = $this->getErrorWithPageId($job, $e->getMessage());
                $error      = true;
            } catch (\RuntimeException $e) {
                $messages[] = $this->getErrorWithPageId($job, $e->getMessage());
                $error      = true;
            } catch (\Exception $e) {
                $messages[] = $this->getErrorWithPageId(
                    $job,
                    __('Something went wrong while saving the job.')
                );
                $error      = true;
            }
        }

        return $resultJson->setData(
            [
                'messages' => $messages,
                'error' => $error
            ]
        );
    }

    /**
     * Validate post data
     *
     * @param [] $jobData
     * @param bool &$error
     * @param [] &$messages
     *
     * @return $this
     */
    protected function validatePost($jobData, &$error, &$messages)
    {
        if (isset($jobData['title'])) {
            $title = trim($jobData['title']);
            if (empty($title)) {
                $error      = true;
                $messages[] = __("Job title can't be empty.");
            }
        }

        return $this;
    }
}
