<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Helper\Additional;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class Check
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class Check extends JobController
{
    const SOURCE = 'export_source';

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\Framework\Json\DecoderInterface
     */
    protected $jsonDecoder;

    /**
     * Check constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param JsonFactory $jsonFactory
     * @param \Magento\Framework\Json\DecoderInterface $jsonDecoder
     * @param Additional $helper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        JsonFactory $jsonFactory,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        Additional $helper
    ) {
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
        $this->jsonFactory = $jsonFactory;
        $this->helper = $helper;
        $this->jsonDecoder = $jsonDecoder;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $result = false;
        if ($this->getRequest()->isAjax()) {
            $formData = $this->getRequest()->getParam('form_data');
            $exportData = [];
            foreach ($formData as $data) {
                $exData = explode('+', $data);
                $exportData[$exData[0]] = $exData[1];
            }
            $entity = $exportData['export_source_entity'];
            $source = $this->helper->getSourceModelByType($exportData['export_source_entity']);
            unset($exportData['export_source_entity']);
            $exportData = $this->getSourceData($exportData, $entity);

            $source->setData($exportData);

            $result = $source->check();

            return $resultJson->setData($result);
        }
    }

    /**
     * @param $sourceData
     * @param $entity
     * @return array
     */
    public function getSourceData($sourceData, $entity)
    {
        $source = [];
        $sourceKey = self::SOURCE . "_" . $entity . "_";

        foreach ($sourceData as $key => $param) {
            if (strpos($key, $sourceKey) !== false) {
                $source[substr($key, strlen($sourceKey))] = $param;
            }
        }

        return $source;
    }
}
