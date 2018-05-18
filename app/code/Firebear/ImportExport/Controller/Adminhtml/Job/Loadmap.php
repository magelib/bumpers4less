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
use Firebear\ImportExport\Helper\Assistant;
use Firebear\ImportExport\Model\Source\Config\CartPrice;

class Loadmap extends JobController
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
     * @var \Firebear\ImportExport\Model\Source\Config\CartPrice
     */
    protected $cartPrice;


    /**
     * Loadmap constructor.
     *
     * @param Context                $context
     * @param Registry               $coreRegistry
     * @param JobFactory             $jobFactory
     * @param JobRepositoryInterface $repository
     * @param JsonFactory            $jsonFactory
     * @param DirectoryList          $directoryList
     * @param Processor              $processor
     * @param Assistant              $ieAssistant
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
        Assistant $ieAssistant,
        CartPrice $cartPrice
    ) {
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
        $this->jsonFactory = $jsonFactory;
        $this->directoryList = $directoryList;
        $this->platforms = $platforms;
        $this->processor = $processor;
        $this->options = $options;
        $this->jsonDecoder = $jsonDecoder;
        $this->ieAssistant = $ieAssistant;
        $this->cartPrice = $cartPrice;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        if ($this->getRequest()->isAjax()) {
            //read required fields from xml file
            $type = $this->getRequest()->getParam('type');
            $locale = $this->getRequest()->getParam('language');
            $formData = $this->getRequest()->getParam('form_data');
            $sourceType = $this->getRequest()->getParam('source_type');
            $importData = [];
            foreach ($formData as $data) {
                $exData = explode('+', $data);
                $index = str_replace($sourceType.'[', '', $exData[0]);
                $index = str_replace(']', '', $index);
                $importData[$index] = $exData[1];
            }
            $importData['platforms'] = $type;
            $importData['locale'] = $locale;
            $maps = [];
            if ($type) {
                $mapArr = $this->platforms->getAllData($type);
                if (!empty($mapArr)) {
                    $maps = $mapArr;
                }
            }
            //get CSV Columns from CSV Import file
            $formData = $this->getRequest()->getParam('form_data');
            $sourceType = $this->getRequest()->getParam('source_type');
            $importData = [];
            foreach ($formData as $data) {
                $exData = explode('+', $data);
                $index = str_replace($sourceType.'[', '', $exData[0]);
                $index = str_replace(']', '', $index);
                $importData[$index] = $exData[1];
            }

            if (isset($importData['type_file'])) {
                $this->processor->setTypeSource($importData['type_file']);
            }
            $importData[$sourceType.'_file_path'] = $importData['file_path'];

            try {
                $result   = $this->processor->getCsvColumns($importData);
                //load categories map
                foreach ($result as $key => $el) {
                    if (preg_match('/^(attribute\|).+/', $el)) {
                        unset($result[$key]);
                    }
                }
                if (is_array($result)) {
                    $messages = [];
                }
            } catch (\Exception $e) {
                return $resultJson->setData(['error' => $e->getMessage()]);
            }
            /*render Import Attribute dropdown*/
            if (!is_array($result)) {
                return $resultJson->setData(['error' => $result]);
            }
            $options = [];
            if ($importData['entity']) {

                /* Validating the csv file header for CART PRICE RULE */ 
                if ($importData['entity']=='cart_price_rule') {
                    $custom_attribute = 'cart_price_rule';
                    $cartPriceData = $this->cartPrice->toArray();
                    foreach ($cartPriceData as $key=>$value) {
                        if (in_array($value, $result)) {
                            $ruleMessages = 'Success';
                        } else if ($value == 'coupon_code') {
                            if (in_array('code', $result)) {
                                $ruleMessages = 'Success';
                            }
                        } else {
                            $ruleMessages = 'Error';
                            break;
                        }

                    }
                    return $resultJson->setData(
                        [
                            'map' => $maps,
                            'columns' => $result,
                            'messages' => $messages,
                            'options' => $options,
                            'show' => $custom_attribute,
                            'ruleMessages' => $ruleMessages
                        ]
                    );
                } else {
                    $collect = $this->options->toOptionArray(1);
                    $options = $collect[$importData['entity']];
                    return $resultJson->setData(
                        [
                        'map' => $maps,
                        'columns' => $result,
                        'messages' => $messages,
                        'options' => $options
                        ]
                    );
                }
            }

            
        }
    }
}
