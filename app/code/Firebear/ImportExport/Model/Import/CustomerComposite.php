<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Magento\CustomerImportExport\Model\Import\CustomerComposite as MagentoCustomer;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use \Magento\ImportExport\Model\Import\AbstractEntity;

class CustomerComposite extends MagentoCustomer
{
    use \Firebear\ImportExport\Traits\General;

    protected $specialFields = [
        'reward_update_notification',
        'reward_warning_notification'
    ];
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    protected $_debugMode;

    protected $duplicateFields = [\Magento\CustomerImportExport\Model\Import\Customer::COLUMN_EMAIL];

    protected $_customerEntity;

    private $serializer;
    
    /**
     *
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\ImportExport\Model\ImportFactory $importFactory
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param \Magento\CustomerImportExport\Model\ResourceModel\Import\CustomerComposite\DataFactory $dataFactory
     * @param \Magento\CustomerImportExport\Model\Import\CustomerFactory $customerFactory
     * @param \Magento\CustomerImportExport\Model\Import\AddressFactory $addressFactory
     * @param \Symfony\Component\Console\Output\ConsoleOutput $output
     * @param \Firebear\ImportExport\Helper\Data $helper
     * @param \Firebear\ImportExport\Model\Import\CustomerFactory $fireImportCustomer
     * @param LoggerInterface $logger
     * @param \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\ImportExport\Model\ImportFactory $importFactory,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\App\ResourceConnection $resource,
        ProcessingErrorAggregatorInterface $errorAggregator,
        \Magento\CustomerImportExport\Model\ResourceModel\Import\CustomerComposite\DataFactory $dataFactory,
        \Magento\CustomerImportExport\Model\Import\CustomerFactory $customerFactory,
        \Magento\CustomerImportExport\Model\Import\AddressFactory $addressFactory,
        \Symfony\Component\Console\Output\ConsoleOutput $output,
        \Firebear\ImportExport\Helper\Data $helper,
        \Firebear\ImportExport\Model\Import\CustomerFactory $fireImportCustomer,
        LoggerInterface $logger,
        \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData,
        array $data = []
    ) {
        parent::__construct(
            $string,
            $scopeConfig,
            $importFactory,
            $resourceHelper,
            $resource,
            $errorAggregator,
            $dataFactory,
            $customerFactory,
            $addressFactory,
            $data
        );
        $this->output = $output;
        $this->_logger = $logger;
        $this->_debugMode = $helper->getDebugMode();
        $this->_customerEntity = $fireImportCustomer->create(['data' => $data]);
        $this->_dataSourceModel = $importFireData;
    }

    /**
     * @return array
     */
    public function getAllFields()
    {
        $options = array_merge($this->getValidColumnNames(), $this->_specialAttributes);
        $options = array_merge($options, $this->_permanentAttributes);
        $options = array_merge($options, $this->specialFields);

        return array_unique($options);
    }
    
    public function setLogger($logger)
    {
        $this->_logger = $logger;
        $this->_customerEntity->setLogger($logger);
    }

    protected function _saveValidatedBunches()
    {
        error_log("da22222");
        $source = $this->getSource();
        $bunchRows = [];
        $startNewBunch = false;

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        $masterAttributeCode = $this->getMasterAttributeCode();
        $file = null;
        $jobId = null;
        if (isset($this->_parameters['file'])) {
            $file = $this->_parameters['file'];
        }
        if (isset($this->_parameters['job_id'])) {
            $jobId = $this->_parameters['job_id'];
        }
        while ($source->valid() || count($bunchRows) || isset($entityGroup)) {
            if ($startNewBunch || !$source->valid()) {
                /* If the end approached add last validated entity group to the bunch */
                if (!$source->valid() && isset($entityGroup)) {
                    foreach ($entityGroup as $key => $value) {
                        $bunchRows[$key] = $value;
                    }
                    unset($entityGroup);
                }
                $this->_dataSourceModel->saveBunches($this->getEntityTypeCode(), $this->getBehavior(), $jobId, $file, $bunchRows);
                
                $bunchRows = [];
                $startNewBunch = false;
            }
            if ($source->valid()) {
                $valid = true;
                try {
                    $rowData = $source->current();
                    foreach ($rowData as $attrName => $element) {
                        if (!mb_check_encoding($element, 'UTF-8')) {
                            $valid = false;
                            $this->addRowError(
                                AbstractEntity::ERROR_CODE_ILLEGAL_CHARACTERS,
                                $this->_processedRowsCount,
                                $attrName
                            );
                        }
                    }
                } catch (\InvalidArgumentException $e) {
                    $valid = false;
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                }
                if (!$valid) {
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }

                if (isset($rowData[$masterAttributeCode]) && trim($rowData[$masterAttributeCode])) {
                    /* Add entity group that passed validation to bunch */
                    if (isset($entityGroup)) {
                        foreach ($entityGroup as $key => $value) {
                            $bunchRows[$key] = $value;
                        }
                        $productDataSize = strlen($this->getSerializer()->serialize($bunchRows));

                        /* Check if the new bunch should be started */
                        $isBunchSizeExceeded = ($this->_bunchSize > 0 && count($bunchRows) >= $this->_bunchSize);
                        $startNewBunch = $productDataSize >= $this->_maxDataSize || $isBunchSizeExceeded;
                    }

                    /* And start a new one */
                    $entityGroup = [];
                }

                if (isset($entityGroup) && $this->validateRow($rowData, $source->key())) {
                    /* Add row to entity group */
                    $entityGroup[$source->key()] = $this->_prepareRowForDb($rowData);
                } elseif (isset($entityGroup)) {
                    /* In case validation of one line of the group fails kill the entire group */
                    unset($entityGroup);
                }
             //   $platformModel = $this->helper->getPlatformModel($this->_parameters['platforms']);

               // $rowData = $platformModel->prepareRow($rowData);
                $this->_processedRowsCount++;
                $source->next();
            }
        }
        return $this;
    }

    private function getSerializer()
    {
        if (null === $this->serializer) {
            $this->serializer = ObjectManager::getInstance()->get(Json::class);
        }
        return $this->serializer;
    }
}
