<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model;

use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Symfony\Component\Console\Output\ConsoleOutput;
use Firebear\ImportExport\Model\Import\Adapter;
use Magento\Framework\App\Filesystem\DirectoryList;
use Firebear\ImportExport\Model\Source\Type\File\Config;

class Import extends \Magento\ImportExport\Model\Import
{
    use \Firebear\ImportExport\Traits\General;

    const FIREBEAR_ONLY_UPDATE = 'update';

    /**
     * Limit displayed errors on Import History page.
     */
    const LIMIT_VISIBLE_ERRORS = 5;

    const CREATE_ATTRIBUTES_CONF_PATH = 'firebear_importexport/general/create_attributes';

    /**
     * @var \Firebear\ImportExport\Model\Source\ConfigInterface
     */
    protected $config;

    /**
     * @var \Firebear\ImportExport\Helper\Data
     */
    protected $helper;

    /**
     * @var \Firebear\ImportExport\Helper\Additional
     */
    protected $additional;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\Timezone
     */
    protected $timezone;

    /**
     * @var \Firebear\ImportExport\Model\Source\Type\AbstractType
     */
    protected $source;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @var array
     */
    protected $errorMessages;

    /**
     * @var \Firebear\ImportExport\Model\Source\Factory
     */
    protected $factory;

    /**
     * @var \Magento\Framework\FilesystemFactory
     */
    protected $filesystemFactory;

    /**
     * @var Config
     */
    protected $typeConfig;

    /**
     * @var array|mixed|null
     */
    protected $platforms;

    protected $importConfig;

    protected $importFireData;

    /**
     * Import constructor.
     * @param Source\ConfigInterface $config
     * @param \Firebear\ImportExport\Helper\Data $helper
     * @param \Firebear\ImportExport\Helper\Additional $additional
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig
     * @param Source\Import\Config $importConfig
     * @param \Magento\ImportExport\Model\Import\Entity\Factory $entityFactory
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param \Magento\ImportExport\Model\Export\Adapter\CsvFactory $csvFactory
     * @param \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory
     * @param \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory
     * @param \Magento\ImportExport\Model\Source\Import\Behavior\Factory $behaviorFactory
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param \Magento\ImportExport\Model\History $importHistoryModel
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $localeDate
     * @param Source\Factory $factory
     * @param Source\Platform\Config $configPlatforms
     * @param ConsoleOutput $output
     * @param Source\Import\Config $importConfig
     * @param array $data
     */
    public function __construct(
        \Firebear\ImportExport\Model\Source\ConfigInterface $config,
        \Firebear\ImportExport\Helper\Data $helper,
        \Firebear\ImportExport\Helper\Additional $additional,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig,
        \Firebear\ImportExport\Model\Source\Import\Config $importConfig,
        \Magento\ImportExport\Model\Import\Entity\Factory $entityFactory,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\ImportExport\Model\Export\Adapter\CsvFactory $csvFactory,
        \Magento\Framework\HTTP\Adapter\FileTransferFactory $httpFactory,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\ImportExport\Model\Source\Import\Behavior\Factory $behaviorFactory,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\ImportExport\Model\History $importHistoryModel,
        \Magento\Framework\Stdlib\DateTime\DateTime $localeDate,
        \Firebear\ImportExport\Model\Source\Factory $factory,
        \Firebear\ImportExport\Model\Source\Platform\Config $configPlatforms,
        \Magento\Framework\FilesystemFactory $filesystemFactory,
        \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData,
        Config $typeConfig,
        ConsoleOutput $output,
        array $data = []
    ) {
        $this->config = $config;
        $this->helper = $helper;
        $this->additional = $additional;
        $this->timezone = $timezone;
        $this->output = $output;
        $this->factory = $factory;
        $this->platforms = $configPlatforms->get();
        $this->importConfig = $importConfig;
        $this->filesystemFactory = $filesystemFactory;
        $this->typeConfig = $typeConfig;
        $this->importFireData = $importFireData;
        parent::__construct(
            $logger,
            $filesystem,
            $importExportData,
            $coreConfig,
            $importConfig,
            $entityFactory,
            $importData,
            $csvFactory,
            $httpFactory,
            $uploaderFactory,
            $behaviorFactory,
            $indexerRegistry,
            $importHistoryModel,
            $localeDate,
            $data
        );

        $this->_debugMode = $helper->getDebugMode();
    }

    /**
     * Check if remote file was modified since the last import
     *
     * @param $timestamp
     *
     * @return bool
     */
    public function checkModified($timestamp)
    {
        if ($this->getSource()) {
            return $this->getSource()->checkModified($timestamp);
        }

        return true;
    }

    /**
     * Download remote source file to temporary directory
     *
     * @TODO change the code to show exceptions on frontend instead of 503 error.
     * @return null|string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadSource()
    {
        $result = null;

        if ($this->getImportSource() && $this->getImportSource() != 'file') {
            $source = $this->getSource();
            try {
                $result = $source->uploadSource();
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
        }
        if ($result) {
            $sourceFileRelative = $this->_varDirectory->getRelativePath($result);
            $entity = $this->getEntity();
            // $this->createHistoryReport($sourceFileRelative, $entity);

            return $result;
        }

        return parent::uploadSource();
    }

    /**
     * Validates source file and returns validation result.
     *
     * @param \Magento\ImportExport\Model\Import\AbstractSource $source
     *
     * @return bool
     */
    public function validateSource(\Magento\ImportExport\Model\Import\AbstractSource $source)
    {
        $platformModel = null;
        $this->addLogWriteln(__('Begin data validation'), $this->output, 'comment');
        if (isset($this->platforms[$this->getData('platforms')]['model'])) {
            $platformModel = $this->factory->create($this->platforms[$this->getData('platforms')]['model']);
        }
        $source->setPlatform($platformModel);
        try {
            if (!$source->getMap()) {
                $source->setMap($this->getData('map'));
            }
            $adapter = $this->_getEntityAdapter()->setSource($source);
            $adapter->setLogger($this->_logger);
            $adapter->setOutput($this->output);
            $errorAggregator = $adapter->validateData();

        } catch (\Exception $e) {
            $errorAggregator = $this->getErrorAggregator();
            $this->addLogWriteln($e->getMessage(), $this->output, 'error');
            $errorAggregator->addError(
                \Magento\ImportExport\Model\Import\Entity\AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION . '. '
                . $e->getMessage(),
                ProcessingError::ERROR_LEVEL_CRITICAL,
                null,
                null,
                null,
                $e->getMessage()
            );
        }

        $messages = $this->getOperationResultMessages($errorAggregator);
        $this->serErrorMessages($messages);
        foreach ($messages as $message) {
            $this->addLogWriteln($message, $this->output, 'info');
        }

        $result = !$errorAggregator->getErrorsCount([ProcessingError::ERROR_LEVEL_CRITICAL]);
        if ($result) {
            $this->addLogWriteln(__('Import data validation is complete.'), $this->output, 'info');
        } else {
            if ($this->isReportEntityType()) {
                $this->getImportHistoryModel()->load($this->getImportHistoryModel()->getLastItemId());
                $summary = '';
                if ($errorAggregator->getErrorsCount() > self::LIMIT_VISIBLE_ERRORS) {
                    $summary = __('Too many errors. Please check your debug log file.') . '<br />';
                    $this->addLogWriteln($summary, $this->output, 'error');
                } else {
                    if ($this->getJobId()) {
                        $summary = __('Import job #' . $this->getJobId() . ' failed.') . '<br />';
                        $this->addLogWriteln(
                            __('Import job #' . $this->getJobId() . ' failed.'),
                            $this->output,
                            'error'
                        );
                    }

                    foreach ($errorAggregator->getRowsGroupedByErrorCode() as $errorMessage => $rows) {
                        $error = $errorMessage . ' ' . __('in rows') . ': ' . implode(', ', $rows);
                        $this->addLogWriteln($error, $this->output, 'error');
                        $summary .= $error . '<br />';
                    }
                }
                $date = $this->timezone->formatDateTime(
                    $this->timezone->date(),
                    \IntlDateFormatter::MEDIUM,
                    \IntlDateFormatter::MEDIUM,
                    null,
                    null
                );
                $summary .= '<i>' . $date . '</i>';
                $this->addLogWriteln($date, $this->output, 'info');
                $this->getImportHistoryModel()->setSummary($summary);
                $this->getImportHistoryModel()->setExecutionTime(\Magento\ImportExport\Model\History::IMPORT_FAILED);
                $this->getImportHistoryModel()->save();
            }
        }

        return $result;
    }

    /**
     * @return Source\Type\AbstractType
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getSource()
    {
        if (!$this->source) {
            $sourceType = $this->getImportSource();
            try {
                $this->source = $this->additional->getSourceModelByType($sourceType);
                $this->source->setData($this->getData());
            } catch (\Exception $e) {
                throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
        }

        return $this->source;
    }

    /**
     * @return mixed
     */
    public function getImportHistoryModel()
    {
        return $this->importHistoryModel;
    }

    /**
     * @return mixed
     */
    public function getErrorMessages()
    {
        return $this->errorMessages;
    }

    /**
     * @param $messages
     */
    public function serErrorMessages($messages)
    {
        $this->errorMessages = $messages;
    }

    /**
     * @param mixed $debugData
     * @return $this
     */
    public function addLogComment($debugData)
    {

        if (is_array($debugData)) {
            $this->_logTrace = array_merge($this->_logTrace, $debugData);
        } else {
            $this->_logTrace[] = $debugData;
        }

        if (is_scalar($debugData)) {
            $this->_logger->debug($debugData);
            if ($this->output) {
                $this->output->writeln($debugData);
            }
        } else {
            foreach ($debugData as $message) {
                if ($message instanceof \Magento\Framework\Phrase) {
                    if ($this->output) {
                        $this->output->writeln($message->__toString());
                    }
                    $this->_logger->debug($message->__toString());
                } else {
                    if ($this->output) {
                        $this->output->writeln($message);
                    }
                    $this->_logger->debug($message);
                }
            }
        }

        return $this;
    }

    /**
     * @return \Magento\ImportExport\Model\Import\AbstractEntity|\Magento\ImportExport\Model\Import\Entity\AbstractEntity
     */
    protected function _getEntityAdapter()
    {
        $adapter = parent::_getEntityAdapter();
        $adapter->setLogger($this->_logger);
        $adapter->setOutput($this->output);

        return $adapter;
    }

    /**
     * @return mixed
     */
    public function getTypeClass($typeData)
    {
        $data = $this->typeConfig->get();
        $types = $data['import'][$typeData];
        $model = $types['model'];
        if (isset($types[$this->getTypeSource()])) {
            $model = $types[$this->getTypeSource()]['model'];
        }
        return $model;
    }

    /**
     * Load categories map
     *
     * @return mixed
     */
    public function getCategories($sourceData)
    {
        $adapter = $this->_getEntityAdapter();
        if ($sourceData['import_source'] != 'file') {
            $directory = $this->filesystemFactory->create()->getDirectoryWrite(DirectoryList::ROOT);
            $this->setImportSource($sourceData['import_source']);
            $this->setData($sourceData);
            $this->getSource()->setData($sourceData);
            $result = null;
            $source = $this->getSource();
            $source->setFormatFile($sourceData['type_file']);
            try {
                $result = $source->uploadSource();
            } catch (\Exception $e) {
                $errorMessage = __($e->getMessage());
                if (strpos($errorMessage, 'ftp_get()') !== false) {
                    $errorMessage = __('Unable to open your file. Please make sure File Path is correct.');
                }
            }
            if ($result) {
                $source = Adapter::findAdapterFor(
                    $this->getTypeClass($sourceData['type_file']),
                    $this->uploadSource(),
                    $directory,
                    $sourceData[Import::FIELD_FIELD_SEPARATOR]
                );
            } else {
                return $errorMessage;
            }
        } else {
            $source = Adapter::findAdapterFor(
                $this->getTypeClass($sourceData['type_file']),
                $sourceData['file_path'],
                $this->filesystemFactory->create()->getDirectoryWrite(DirectoryList::ROOT),
                $sourceData[Import::FIELD_FIELD_SEPARATOR]
            );
        }

        $adapter->setSource($source);
        $fieldName = 'categories';

        if (isset($sourceData['mappingData'])) {
            foreach ($sourceData['mappingData'] as $sourceDataMapItem) {
                if ($sourceDataMapItem['source_data_system'] == 'categories') {
                    $fieldName = $sourceDataMapItem['source_data_import'];
                }
            }
        }

        return $adapter->getCategoriesMap($fieldName);
    }

    /**
     * Get entity adapter class string
     *
     * @return string
     */
    public function getEntityClassName()
    {
        return get_class($this->_getEntityAdapter());
    }


    public function getEntityBehaviors()
    {
        $behaviourData = [];
        $entities = $this->importConfig->getEntities();
        foreach ($entities as $entityCode => $entityData) {
            $behaviorClassName = isset($entityData['behaviorModel']) ? $entityData['behaviorModel'] : null;
            if ($behaviorClassName && class_exists($behaviorClassName)) {
                /** @var $behavior \Magento\ImportExport\Model\Source\Import\AbstractBehavior */
                $behavior = $this->_behaviorFactory->create($behaviorClassName);

                $behaviourData[$entityCode] = [
                    'token' => $behaviorClassName,
                    'code' => $behavior->getCode() . '_behavior',
                    'notes' => $behavior->getNotes($entityCode),
                ];
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('The behavior token for %1 is invalid.', $entityCode)
                );
            }
        }
        return $behaviourData;
    }

    public function importSourcePart($file, $offset, $job, $show)
    {
        $this->setData('entity', $this->getEntity());
        $this->setData('behavior', $this->getBehavior());
        $this->addLogComment(__('Begin import of "%1" with "%2" behavior', $this->getEntity(), $this->getBehavior()));
        $result = $this->processImportPart($file, $offset, $job);

        if ($result) {
            $this->addLogComment(
                [
                    __(
                        'Checked rows: %1, checked entities: %2, invalid rows: %3, total errors: %4',
                        $this->getProcessedRowsCount(),
                        $this->getProcessedEntitiesCount(),
                        $this->getErrorAggregator()->getInvalidRowsCount(),
                        $this->getErrorAggregator()->getErrorsCount()
                    ),
                    __('The import was successful.'),
                ]
            );
            if ($show) {
                $this->showErrors();
            }
            $this->importHistoryModel->updateReport($this, true);
        } else {
            $this->importHistoryModel->invalidateReport($this);
        }

        return $result;
    }

    protected function processImportPart($file, $offset, $job)
    {
        return $this->_getEntityAdapter()->importDataPart($file, $offset, $job);
    }

    public function setErrorAggregator($errorAggregator)
    {
        $this->_getEntityAdapter()->setErrorAggregator($errorAggregator);
        $this->_getEntityAdapter()->setErrorMessages();
    }

    public function showErrors()
    {
        foreach ($this->getErrorAggregator()->getRowsGroupedByErrorCode() as $errorMessage => $rows) {
            $error = $errorMessage . ' ' . __('in rows') . ': ' . implode(', ', $rows);
            $this->addLogWriteln($error, $this->output, 'error');
        }
    }

    public function validateCheck(\Magento\ImportExport\Model\Import\AbstractSource $source)
    {
        $this->addLogComment(__('Begin data validation'));

        $errorAggregator = $this->getErrorAggregator();
        $errorAggregator->initValidationStrategy(
            $this->getData(self::FIELD_NAME_VALIDATION_STRATEGY),
            $this->getData(self::FIELD_NAME_ALLOWED_ERROR_COUNT)
        );

        try {
            $adapter = $this->_getEntityAdapter()->setSource($source);
            $adapter->validateData(0);
        } catch (\Exception $e) {
            $errorAggregator->addError(
                \Magento\ImportExport\Model\Import\Entity\AbstractEntity::ERROR_CODE_SYSTEM_EXCEPTION,
                ProcessingError::ERROR_LEVEL_CRITICAL,
                null,
                null,
                null,
                $e->getMessage()
            );
        }

        $messages = $this->getOperationResultMessages($errorAggregator);
        $this->addLogComment($messages);

        $result = !$errorAggregator->getErrorsCount();
        if ($result) {
            $this->addLogComment(__('Import data validation is complete.'));
        }
        return $result;
    }

    /**
     * @param $output
     */
    public function setOuput($output)
    {
        $this->output = $output;
    }

    public function getFireDataSourceModel()
    {
        return $this->importFireData;
    }
}
