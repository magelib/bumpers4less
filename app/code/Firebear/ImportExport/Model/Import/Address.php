<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Magento\CustomerImportExport\Model\Import\Address as MagentoAddress;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\Serializer\Json;
use \Magento\ImportExport\Model\Import\AbstractEntity;

class Address extends MagentoAddress
{
    use \Firebear\ImportExport\Traits\General;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    protected $_debugMode;

    protected $duplicateFields = [];

    private $serializer;

    /**
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\ImportExport\Model\ImportFactory $importFactory
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\ImportExport\Model\Export\Factory $collectionFactory
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\CustomerImportExport\Model\ResourceModel\Import\Customer\StorageFactory $storageFactory
     * @param \Magento\Customer\Model\AddressFactory $addressFactory
     * @param \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionColFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressColFactory
     * @param \Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory $attributesFactory
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Magento\Customer\Model\Address\Validator\Postcode $postcodeValidator
     * @param \Symfony\Component\Console\Output\ConsoleOutput $output
     * @param \Firebear\ImportExport\Helper\Data $helper
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
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\ImportExport\Model\Export\Factory $collectionFactory,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\CustomerImportExport\Model\ResourceModel\Import\Customer\StorageFactory $storageFactory,
        \Magento\Customer\Model\AddressFactory $addressFactory,
        \Magento\Directory\Model\ResourceModel\Region\CollectionFactory $regionColFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Address\CollectionFactory $addressColFactory,
        \Magento\Customer\Model\ResourceModel\Address\Attribute\CollectionFactory $attributesFactory,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Magento\Customer\Model\Address\Validator\Postcode $postcodeValidator,
        \Symfony\Component\Console\Output\ConsoleOutput $output,
        \Firebear\ImportExport\Helper\Data $helper,
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
            $storeManager,
            $collectionFactory,
            $eavConfig,
            $storageFactory,
            $addressFactory,
            $regionColFactory,
            $customerFactory,
            $addressColFactory,
            $attributesFactory,
            $dateTime,
            $postcodeValidator,
            $data
        );
        $this->_logger = $logger;
        $this->output = $output;
        $this->_debugMode = $helper->getDebugMode();
        $this->_dataSourceModel = $importFireData;
    }

    /**
     * @return array
     */
    public function getAllFields()
    {
        $options = array_merge($this->getValidColumnNames(), $this->_specialAttributes);
        $options = array_merge($options, $this->_permanentAttributes);

        return array_unique($options);
    }

    /**
     * @return bool
     */
    protected function _importData()
    {
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $newRows = [];
            $updateRows = [];
            $attributes = [];
            $defaults = [];

            $deleteRowIds = [];

            foreach ($bunch as $rowNumber => $rowData) {
                $time = explode(" ", microtime());
                $startTime = $time[0] + $time[1];
                $email = $rowData['_email'];
                $rowData = $this->joinIdenticalyData($rowData);
                if ($this->_isOptionalAddressEmpty($rowData) || !$this->validateRow($rowData, $rowNumber)) {
                    $this->addLogWriteln(__('email: %1 is not valided', $email), $this->output, 'info');
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNumber);
                    continue;
                }

                if ($this->getBehavior($rowData) == \Magento\ImportExport\Model\Import::BEHAVIOR_ADD_UPDATE) {
                    $addUpdateResult = $this->_prepareDataForUpdate($rowData);
                    if ($addUpdateResult['entity_row_new']) {
                        $newRows[] = $addUpdateResult['entity_row_new'];
                    }
                    if ($addUpdateResult['entity_row_update']) {
                        $updateRows[] = $addUpdateResult['entity_row_update'];
                    }
                    $attributes = $this->_mergeEntityAttributes($addUpdateResult['attributes'], $attributes);
                    $defaults = $this->_mergeEntityAttributes($addUpdateResult['defaults'], $defaults);
                } elseif ($this->getBehavior($rowData) == \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE) {
                    $deleteRowIds[] = $rowData[self::COLUMN_ADDRESS_ID];
                }
                $endTime = $time[0] + $time[1];
                $totalTime = $endTime - $startTime;
                $totalTime = round($totalTime, 5);
                $this->addLogWriteln(__('email: %1 .... %2s', $email, $totalTime), $this->output, 'info');
            }
            $this->updateItemsCounterStats($newRows, $updateRows, $deleteRowIds);

            $this->_saveAddressEntities(
                $newRows,
                $updateRows
            )->_saveAddressAttributes(
                $attributes
            )->_saveCustomerDefaults(
                $defaults
            );

            $this->_deleteAddressEntities($deleteRowIds);
        }
        return true;
    }

    protected function _saveValidatedBunches()
    {
        error_log("da222");
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
                $this->_dataSourceModel->saveBunches(
                    $this->getEntityTypeCode(),
                    $this->getBehavior(),
                    $jobId,
                    $file,
                    $bunchRows
                );

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

    protected function _validateRowForDelete(array $rowData, $rowNumber)
    {
        if ($this->_checkUniqueKey($rowData, $rowNumber)) {
            $email = strtolower($rowData[self::COLUMN_EMAIL]);
            $website = $rowData[self::COLUMN_WEBSITE];
            $addressId = $rowData[self::COLUMN_ADDRESS_ID];
            $customerId = $this->_getCustomerId($email, $website);
            if ($customerId === false) {
                $this->addRowError(self::ERROR_CUSTOMER_NOT_FOUND, $rowNumber);
            } else {
                if (!strlen($addressId)) {
                    $this->addRowError(self::ERROR_ADDRESS_ID_IS_EMPTY, $rowNumber);
                } elseif (!isset($this->_addresses[$customerId]) || !in_array($addressId, $this->_addresses[$customerId])) {
                    $this->addRowError(self::ERROR_ADDRESS_NOT_FOUND, $rowNumber);
                }
            }
        }
    }
}
