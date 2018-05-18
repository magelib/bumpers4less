<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
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
use Firebear\ImportExport\Helper\Data;

class Mapvalidate extends JobController
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
     * @var Data
     */
    protected $helper;

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
     * @var \Magento\Framework\FilesystemFactory
     */
    protected $fileSystem;

    /**
     * Mapvalidate constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param JsonFactory $jsonFactory
     * @param DirectoryList $directoryList
     * @param Platforms $platforms
     * @param Data $helper
     * @param Options $options
     * @param \Magento\Framework\FilesystemFactory $filesystemFactory
     * @param \Magento\Framework\Json\DecoderInterface $jsonDecoder
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        JsonFactory $jsonFactory,
        DirectoryList $directoryList,
        Platforms $platforms,
        Data $helper,
        Options $options,
        \Magento\Framework\FilesystemFactory $filesystemFactory,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder
    ) {
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
        $this->jsonFactory = $jsonFactory;
        $this->directoryList = $directoryList;
        $this->platforms = $platforms;
        $this->helper = $helper;
        $this->options = $options;
        $this->jsonDecoder = $jsonDecoder;
        $this->fileSystem = $filesystemFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $messages = [];
        if ($this->getRequest()->isAjax()) {
            //read required fields from xml file
            $type = $this->getRequest()->getParam('platforms');
            $locale = $this->getRequest()->getParam('language');
            $formData = $this->getRequest()->getParam('form_data');
            $sourceType = $this->getRequest()->getParam('source_type');
            $importData = [];
            foreach ($formData as $data) {
                $exData = explode('+', $data);
                $index = str_replace($sourceType . '[', '', $exData[0]);
                $index = str_replace(']', '', $index);
                $importData[$index] = $exData[1];
            }
            $importData['records'] = $this->jsonDecoder->decode($importData['records']);
            $importData['platforms'] = $type;
            $importData['locale'] = $locale;
            if (isset($importData['type_file'])) {
                $this->helper->setTypeSource($importData['type_file']);
            }
            $directory = $this->fileSystem->create()->getDirectoryWrite(DirectoryList::ROOT);

            $importData[$sourceType . '_file_path'] = $importData['file_path'];
            try {
                $result = $this->helper->correctData($importData);
                if ($result) {
                    $messages = $this->helper->processValidate($importData);
                }
            } catch (\Exception $e) {
                return $resultJson->setData(['error' => [$e->getMessage()]]);
            }

            $options = [];
            if ($importData['entity']) {
                $collect = $this->options->toOptionArray(1);
                $options = $collect[$importData['entity']];
            }
            $this->helper->revertLocale();

            return $resultJson->setData(
                [
                    'error' => $messages
                ]
            );
        }
    }
}
