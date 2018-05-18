<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use gento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;

class InlineEdit extends \Firebear\ImportExport\Controller\Adminhtml\Export\Job
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    /**
     * InlineEdit constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Firebear\ImportExport\Model\ExportJobFactory $exportJobFactory
     * @param \Firebear\ImportExport\Api\ExportJobRepositoryInterface $exportRepository
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Firebear\ImportExport\Model\ExportJobFactory $exportJobFactory,
        \Firebear\ImportExport\Api\ExportJobRepositoryInterface $exportRepository,
        JsonFactory $jsonFactory
    ) {
        $this->jsonFactory = $jsonFactory;
        parent::__construct($context, $coreRegistry, $exportJobFactory, $exportRepository);
    }

    /**
     * @return mixed
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error      = false;
        $messages   = [];
        $postItems  = $this->getRequest()->getParam('items', []);
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
                    $model = $this->exportRepository->getById($jobId);
                    $model->setData($item);
                    $this->exportRepository->save($model);
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $messages[] = $e->getMessage();
                $error      = true;
            } catch (\RuntimeException $e) {
                $messages[] = $e->getMessage();
                $error      = true;
            } catch (\Exception $e) {
                $messages[] = __('Something went wrong while saving the export job.');
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
}
