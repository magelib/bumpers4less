<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\ExportJob;

use Magento\Backend\App\Area\FrontNameResolver;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Json\DecoderInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Api\StoreResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\RequestInterface;
use Firebear\ImportExport\Model\ExportFactory;
use Firebear\ImportExport\Model\ExportJobFactory;
use Firebear\ImportExport\Api\ExportJobRepositoryInterface;
use Firebear\ImportExport\Helper\Additional;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Zend\Console\Console;

class Processor extends \Firebear\ImportExport\Model\AbstractProcessor
{
    const SOURCE = 'export_source';

    const SOURCE_DATA_SYSTEM = 'source_data_system';

    const SOURCE_DATA_EXPORT = 'source_data_export';

    const SOURCE_DATA_REPLACES = 'source_data_replace';

    const SOURCE_DATA_COUNT = 'source_data_count';

    const SOURCE_FILTER_FIELD = 'source_filter_field';

    const SOURCE_FILTER_FILTER = 'source_filter_filter';

    const BEHAVIOR_FIELD = 'behavior_field';

    const ENTITY = 'entity';

    const FILE_FORMAT = 'file_format';

    const LIST_DATA = 'list';

    const EXPORT_FILTER = 'export_filter';

    const EXPORT_FILTER_TABLE = 'export_filter_table';

    const REPLACE_CODE = 'replace_code';

    const REPLACE_VALUE = 'replace_value';

    const BEHAVIOR_DATA = 'behavior_data';

    const EXPORT_SOURCE = 'export_source';

    const FILE_PATH = 'file_path';

    const SOURCE_ENTITY = 'source_entity';

    const ALL_FIELDS = 'all_fields';

    const VALUE = 'value';

    const DEPENDENCIES = 'dependencies';

    const LANGUAGE = 'language';

    const DIVIDED_ATTRIBUTES = 'divided_additional';

    /**
     * @var ExportFactory
     */
    protected $exportModel;

    /**
     * @var ExportJobFactory
     */
    protected $exportJob;

    /**
     * @var ExportJobRepositoryInterface
     */
    protected $exportJobRepository;

    /**
     * @var Data
     */
    protected $helper;

    protected $source;

    protected $output;

    public $debugMode;

    protected $exportFile;

    /**
     * Application
     *
     * @var \Magento\Framework\App\AreaList
     */
    protected $areaList;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $locale;

    protected $localeLocal;

    /**
     * @var \Magento\Backend\App\ConfigInterface
     */
    protected $backendConfig;

    /**
     * @var \Magento\Backend\Model\Locale\Manager
     */
    protected $manager;

    /**
     * @var \Magento\Framework\TranslateInterface
     */
    protected $translator;

    public $inConsole;

    /**
     * Processor constructor.
     * @param DecoderInterface $jsonDecoder
     * @param StoreManagerInterface $storeManager
     * @param StoreResolverInterface $storeResolver
     * @param RequestInterface $request
     * @param LoggerInterface $logger
     * @param TimezoneInterface $timezone
     * @param UrlInterface $backendUrl
     * @param ExportFactory $exportModel
     * @param ExportJobFactory $exportJob
     * @param ExportJobRepositoryInterface $exportJobRepository
     * @param Additional $helper
     * @param ConsoleOutput $output
     * @param \Magento\Framework\App\AreaList $areaList
     * @param \Magento\Framework\Locale\Resolver $locale
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Backend\App\ConfigInterface $backendConfig
     * @param \Magento\Framework\TranslateInterface $translator
     * @param \Magento\Backend\Model\Locale\ManagerFactory $manager
     */
    public function __construct(
        DecoderInterface $jsonDecoder,
        StoreManagerInterface $storeManager,
        StoreResolverInterface $storeResolver,
        RequestInterface $request,
        LoggerInterface $logger,
        TimezoneInterface $timezone,
        UrlInterface $backendUrl,
        ExportFactory $exportModel,
        ExportJobFactory $exportJob,
        ExportJobRepositoryInterface $exportJobRepository,
        Additional $helper,
        ConsoleOutput $output,
        \Magento\Framework\App\AreaList $areaList,
        \Magento\Framework\Locale\Resolver $locale,
        \Magento\Framework\App\State $state,
        \Magento\Backend\App\ConfigInterface $backendConfig,
        \Magento\Framework\TranslateInterface $translator,
        \Magento\Backend\Model\Locale\ManagerFactory $manager
    ) {
        $this->exportModel = $exportModel;
        $this->exportJob = $exportJob;
        $this->exportJobRepository = $exportJobRepository;
        $this->helper = $helper;
        $this->output = $output;
        $this->locale = $locale;
        $this->areaList = $areaList;
        $this->state = $state;
        $this->localeLocal = null;
        $this->backendConfig = $backendConfig;
        $this->manager = $manager;
        $this->inConsole = 0;
        $this->translator = $translator;
        parent::__construct($jsonDecoder, $storeManager, $storeResolver, $request, $logger, $timezone, $backendUrl);
    }

    /**
     * @param $jobId
     *
     * @return ExportJobRepositoryInterface
     */
    protected function getJobModel($jobId)
    {
        return $this->exportJobRepository->getById($jobId);
    }

    public function prepareJob($jobId)
    {
        $this->setLogger($this->logger);
        $data = parent::prepareJob($jobId);
        if (isset($data['language']) && $data['language']) {
            $this->changeLocal($data['language']);
        }

        return $data;
    }

    public function process($jobId)
    {
        $result = parent::process($jobId);

        $this->revertLocale();

        return $result;
    }

    /**
     * @return mixed
     */
    protected function getProcessModel()
    {
        return $this->exportModel->create();
    }

    /**
     * @param $sourceData
     *
     * @return array
     */
    public function getSourceData($sourceData, $exportSource)
    {
        $source = [];
        $sourceKey = self::SOURCE . "_" . $exportSource . "_";

        foreach ($sourceData as $key => $param) {
            if (strpos($key, $sourceKey) !== false) {
                $source[substr($key, strlen($sourceKey))] = $param;
            }
        }

        return $source;
    }

    /**
     * @param $data
     *
     * @return array
     */
    public function getBehaviorData($data)
    {
        $array = [];
        $sourceKey = self::BEHAVIOR_FIELD . "_";

        foreach ($data as $key => $param) {
            if (strpos($key, $sourceKey) !== false) {
                $array[substr($key, strlen($sourceKey))] = $param;
            }
        }

        return $array;
    }

    /**
     * @param $mapData
     *
     * @return array
     */
    public function getMapData($mapData)
    {
        $replaces = [];
        $list = [];
        $exportFilter = [];
        $replacesValues = [];
        $allFields = 0;
        $deps = [];
        $exportFilterTable = [];
        $exportFilter['tier_price'] = '';
        $exportFilter[\Magento\Catalog\Model\Category::KEY_UPDATED_AT] = [
            '01.01.1970 00:00:00',
            $this->timezone->date()
        ];

        foreach ($mapData as $key => $items) {
            if ($key == self::SOURCE_DATA_SYSTEM) {
                foreach ($items as $num => $values) {
                    if ($num == self::VALUE) {
                        foreach ($values as $k => $value) {
                            if (!($mapData[self::SOURCE_DATA_EXPORT]['delete'][$k])) {
                                $list[$k] = $value;
                                $exportFilter[$num][$value] = "";
                                $replace = $mapData[self::SOURCE_DATA_EXPORT][$num][$k];
                                $replaces[$k] = $replace;
                                $replacesValues[$k] = $mapData[self::SOURCE_DATA_REPLACES][$num][$k];
                            }
                        }
                    }
                    if ($num == self::ENTITY) {
                        foreach ($values as $k => $value) {
                            if (!($mapData[self::SOURCE_DATA_EXPORT]['delete'][$k])) {
                                $deps[$k] = $value;
                            }
                        }
                    }
                }
            }
            if ($key == self::SOURCE_DATA_COUNT) {
                $allFields = $items;
            }
            if ($key == self::SOURCE_FILTER_FIELD) {
                foreach ($items as $num => $values) {
                    if ($num == self::VALUE) {
                        foreach ($values as $k => $value) {
                            if (isset($mapData[self::SOURCE_FILTER_FILTER][$num][$k])) {
                                $filter = $mapData[self::SOURCE_FILTER_FILTER][$num][$k];
                            } else {
                                $filter = '';
                            }
                            $exportFilter[$value] = $this->parseValue($filter);
                            $exportFilterTable[] = [
                                'entity' => $mapData[self::SOURCE_FILTER_FIELD]['entity'][$k],
                                'field' => $value,
                                'value' => $this->parseValue($filter)
                            ];
                        }
                    }
                }
            }
        }

        return [$list, $exportFilter, $exportFilterTable, $replaces, $replacesValues, $allFields, $deps];
    }

    /**
     * @param $value
     * @return array
     */
    protected function parseValue($value)
    {
        if (strpos($value, ":") !== false) {
            $value = explode(":", $value);
        }

        return $value;
    }

    /**
     * @return array
     */
    protected function getDataMerge()
    {
        $behaviorData = $this->getBehaviorData($this->jsonDecoder->decode($this->getJob()->getBehaviorData()));
        $sourceData = $this->jsonDecoder->decode($this->getJob()->getExportSource());
        $mapsData = $this->jsonDecoder->decode($this->getJob()->getSourceData());
        if (isset($sourceData['language'])) {
            $language = $sourceData['language'];
        } else {
            $language = 'en_EN';
        }
        $data = [];
        $allFields = 0;
        list($listCodes, $exportFilter, $exportFilterTable, $replaceCode, $replaceValues, $allFields, $deps) =
            $this->getMapData($mapsData);
        if (isset($sourceData[self::SOURCE . '_entity'])) {
            $exportSource = $sourceData[self::SOURCE . '_entity'];
            $source = $this->getSourceData($sourceData, $exportSource);
            $data = [
                self::ENTITY => $this->getJob()->getEntity(),
                self::FILE_FORMAT => isset($behaviorData['file_format']) ? $behaviorData['file_format'] : 'csv',
                self::LIST_DATA => $listCodes,
                self::EXPORT_FILTER => $exportFilter,
                self::EXPORT_FILTER_TABLE => $exportFilterTable,
                self::REPLACE_CODE => $replaceCode,
                self::REPLACE_VALUE => $replaceValues,
                self::BEHAVIOR_DATA => $behaviorData,
                self::EXPORT_SOURCE => $source,
                self::SOURCE_ENTITY => $exportSource,
                self::ALL_FIELDS => $allFields,
                self::DEPENDENCIES => $deps,
                self::LANGUAGE => $language,
                self::DIVIDED_ATTRIBUTES => $sourceData['divided_additional']
            ];
        }

        return $data;
    }

    /**
     * @param $data
     */
    public function run($data)
    {
        $model = $this->getProcessModel();
        $model->setLogger($this->logger);
        $model->setData($data);
        $entity = $data[self::SOURCE_ENTITY];
        $this->addLogComment(__('Entity %1', $data['entity']), $this->output, 'info');
        $source = $this->getSource($entity);
        $source->setData($data[self::EXPORT_SOURCE]);
        list($result, $file, $errors) = $source->run($model);
        
        if ($result) {
            $this->setExportFile($file);
        } else {
            foreach ($errors as $error) {
                $this->logger->debug($error);
            }
        }

        return $result;
    }

    /**
     * @return \Firebear\ImportExport\Model\Source\Type\AbstractType
     */
    public function getSource($entity)
    {
        if (!$this->source) {
            try {
                $this->source = $this->helper->getSourceModelByType($entity);
            } catch (\Exception $e) {
                $this->addLogComment($e->getMessage(), $this->output, 'error');
                $this->logger->critical($e);
            }
        }

        return $this->source;
    }

    /**
     * @param $debugData
     * @param OutputInterface|null $output
     * @param null $type
     * @return $this
     */
    public function addLogComment($debugData, OutputInterface $output = null, $type = null)
    {

       // if ($this->debugMode) {
            $this->logger->debug($debugData);
      //  }
 
        if ($output) {
            switch ($type) {
                case 'error':
                    $debugData = '<error>' . $debugData . '</error>';
                    break;
                case 'info':
                    $debugData = '<info>' . $debugData . '</info>';
                    break;
                default:
                    $debugData = '<comment>' . $debugData . '</comment>';
                    break;
            }

            $output->writeln($debugData);
        }

        return $this;
    }

    /**
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return mixed
     */
    public function getExportFile()
    {
        return $this->exportFile;
    }

    /**
     * @param $file
     */
    public function setExportFile($file)
    {
        $this->exportFile = $file;
    }


    /**
     * @param $local
     * @return $this
     */
    public function setLocal($local)
    {
        $this->localeLocal = $local;

        return $this;
    }

    /**
     * @return null
     */
    public function getLocal()
    {
        return $this->localeLocal;
    }

    /**
     * @param $local
     */
    public function changeLocal($local)
    {
        $this->setLocal($this->locale->getLocale());

        if (!$this->inConsole) {
            $this->backendConfig->setValue('general/locale/code', $local);
            $this->locale->setLocale($local);
            $this->manager->create()->switchBackendInterfaceLocale($this->locale->getLocale());
        } else {
            $this->locale->setLocale($local);
            $this->translator->setLocale($local)->loadData(null, true);
        }

        $area = $this->areaList->getArea($this->state->getAreaCode());

        $area->load(\Magento\Framework\App\Area::PART_TRANSLATE);
    }


    public function revertLocale()
    {
        $this->locale->setLocale($this->getLocal());
        if (!$this->inConsole) {
            $this->backendConfig->setValue('general/locale/code', $this->getLocal());
            $this->locale->setLocale($this->getLocal());
            $this->manager->create()->switchBackendInterfaceLocale($this->getLocal());
        } else {
            $this->locale->setLocale($this->getLocal());
            $this->translator->setLocale($this->getLocal())->loadData(null, true);
        }
        $area = $this->areaList->getArea($this->state->getAreaCode());
        $area->load(\Magento\Framework\App\Area::PART_TRANSLATE);
    }
}
