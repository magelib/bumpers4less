<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Controller\Result\JsonFactory;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Firebear\ImportExport\Model\Job\Processor;
use Magento\Framework\Registry;
use Firebear\ImportExport\Model\Import\Platforms;
use Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Import\Options;
use Firebear\ImportExport\Helper\Assistant;

class Loadcategories extends JobController
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var Processor
     */
    protected $processor;

    /**
     * @var Platforms
     */
    protected $platforms;

    /**
     * @var Options
     */
    protected $options;

    /**
     * @var \Magento\Framework\Json\DecoderInterface
     */
    protected $jsonDecoder;

    /**
     * @var \Firebear\ImportExport\Helper\Assistant
     */
    protected $ieAssistant;

    /**
     * Loadmap constructor.
     *
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param JsonFactory $jsonFactory
     * @param DirectoryList $directoryList
     * @param Processor $processor
     * @param Assistant $ieAssistant
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        JsonFactory $jsonFactory,
        DirectoryList $directoryList,
        Platforms $platforms,
        Processor $processor,
        Options $options,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        Assistant $ieAssistant
    ) {
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
        $this->jsonFactory = $jsonFactory;
        $this->directoryList = $directoryList;
        $this->platforms = $platforms;
        $this->processor = $processor;
        $this->options = $options;
        $this->jsonDecoder = $jsonDecoder;
        $this->ieAssistant = $ieAssistant;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $categories = [];
        if ($this->getRequest()->isAjax()) {
            //read required fields from xml file
            $type = $this->getRequest()->getParam('type');
            $locale = $this->getRequest()->getParam('language');
            $formData = $this->getRequest()->getParam('form_data');
            $sourceType = $this->getRequest()->getParam('source_type');
            $importData = [];
            foreach ($formData as $data) {
                if (is_array($data)) {
                    $importData['mappingData'][] = $data;
                } elseif (strpos($data, 'records+') !== false) {
                    $exData = explode('+', $data);
                    $exData = $this->getContents($exData[1], '[', ']');
                    if (!empty($exData[0])) {
                        $importData['mappingData'] = $this->jsonDecoder->decode('[' . $exData[0] . ']');
                    }
                } else {
                    $exData = explode('+', $data);
                    $index = str_replace($sourceType . '[', '', $exData[0]);
                    $index = str_replace(']', '', $index);
                    $importData[$index] = $exData[1];
                }
            }
            $importData['platforms'] = $type;
            $importData['locale'] = $locale;
            if (isset($importData['type_file'])) {
                $this->processor->setTypeSource($importData['type_file']);
            }
            $importData[$sourceType . '_file_path'] = $importData['file_path'];

            try {
                //load categories map
                $importModel = $this->processor->getImportModel()->setData($importData);
                if ($importModel->getEntity() == 'catalog_product') {
                    $categories = $importModel->getCategories($importData);
                    $categories = $this->ieAssistant->parsingCategories($categories);
                }
            } catch (\Exception $e) {
                return $resultJson->setData(['error' => $e->getMessage()]);
            }

            return $resultJson->setData(
                [
                    'categories' => $categories
                ]
            );
        }
    }

    public function getContents($str, $startDelimiter, $endDelimiter)
    {
        $contents = array();
        $startDelimiterLength = strlen($startDelimiter);
        $endDelimiterLength = strlen($endDelimiter);
        $startFrom = $contentStart = $contentEnd = 0;
        while (false !== ($contentStart = strpos($str, $startDelimiter, $startFrom))) {
            $contentStart += $startDelimiterLength;
            $contentEnd = strpos($str, $endDelimiter, $contentStart);
            if (false === $contentEnd) {
                break;
            }
            $contents[] = substr($str, $contentStart, $contentEnd - $contentStart);
            $startFrom = $contentEnd + $endDelimiterLength;
        }

        return $contents;
    }
}
