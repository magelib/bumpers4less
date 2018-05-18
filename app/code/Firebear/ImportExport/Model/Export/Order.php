<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use Magento\ImportExport\Model\Import;
use \Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Symfony\Component\Console\Output\ConsoleOutput;

class Order extends \Magento\ImportExport\Model\Export\Entity\AbstractEntity
{
    use \Firebear\ImportExport\Traits\General;

    const ORDERS = 'orders';

    const ITEM = 'item';

    const SEPARATOR = '|';

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $_logger;

    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    private $factory;

    protected $entityCollectionFactory;

    protected $entityCollection;

    protected $itemsPerPage = null;

    protected $headerColumns = [];

    /**
     * @var \Firebear\ImportExport\Model\Source\Factory
     */
    protected $createFactory;

    protected $children;

    /**
     * @var \Firebear\ImportExport\Helper\Data
     */
    protected $helper;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    protected $_debugMode;

    protected $joined = true;

    protected $counts;

    /**
     * Order constructor.
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Firebear\ImportExport\Model\Source\Factory $createFactory
     * @param Dependencies\Config $configDi
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Firebear\ImportExport\Model\Source\Factory $createFactory,
        \Firebear\ImportExport\Model\Export\Dependencies\Config $configDi,
        \Firebear\ImportExport\Helper\Data $helper,
        ConsoleOutput $output
    ) {
        $this->_logger = $logger;
        $this->createFactory = $createFactory;
        $this->children = $configDi->get();
        $this->helper = $helper;
        $this->output = $output;
        $this->_debugMode = $helper->getDebugMode();
        parent::__construct($localeDate, $config, $resource, $storeManager);
    }

    /**
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'order';
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getName()
    {
        return __('Orders');
    }

    /**
     * @return mixed
     */
    public function _getHeaderColumns()
    {
        return $this->customHeadersMapping($this->headerColumns);
    }

    /**
     * @param bool $resetCollection
     * @return \Magento\Sales\Model\ResourceModel\Order\Collection
     */
    protected function _getEntityCollection($resetCollection = false)
    {
        if ($resetCollection || empty($this->entityCollection)) {
            $this->entityCollection = $this->entityCollectionFactory->create();
        }

        return $this->entityCollection;
    }

    /**
     * @param $model
     * @return mixed
     */
    protected function _getEntityCollectionSecond($model)
    {

        return $model->create();
    }

    public function export()
    {
        //Execution time may be very long
        set_time_limit(0);
        if (!isset($this->_parameters['behavior_data']['deps'])) {
            $this->addLogWriteln(__('You have not selected items'), $this->output);
            return false;
        }
        $this->counts = 0;
        $deps = $this->_parameters['behavior_data']['deps'];
        $collections = [];
        $this->addLogWriteln(__('Begin Export'), $this->output);
        $this->addLogWriteln(__('Scope Data'), $this->output);
        foreach ($this->children as $typeName => $type) {
            if ($typeName == 'catalog_category') {
                continue;
            }
            foreach ($type['fields'] as $name => $values) {
                if (in_array($name, $deps)) {
                    $model = $this->createFactory->create($values['collection']);
                    $object = [
                        'model' => $model,
                        'main_field' => $values['main_field'],
                        'parent' => $values['parent'],
                        'parent_field' => $values['parent_field'],
                        'children' => []
                    ];
                    if (!$values['parent'] || empty($deps)) {
                        $collections[$name] = $object;
                    } else {
                        $this->searchChildren($values['parent'], $name, $collections, $object);
                    }
                }
            }
        }

        if ($this->_parameters['behavior_data']['file_format'] == 'xml') {
            $this->joined = false;
        }
        $writer = $this->getWriter();

        foreach ($collections as $key => $collection) {
            $this->runCollection($key, $collection, $writer);
        }

        return [$writer->getContents(), $this->counts];
    }

    /**
     * @param $parent
     * @param $name
     * @param $collections
     * @param $object
     * @return bool
     */
    protected function searchChildren($parent, $name, &$collections, $object)
    {
        if (isset($collections[$parent])) {
            $collections[$parent]['children'][$name] = $object;
            return true;
        }
        foreach ($collections as &$child) {
            $this->searchChildren($parent, $name, $child['children'], $object);
        }
    }

    /**
     * @param $key
     * @param $collection
     * @param $writer
     */
    public function runCollection($key, $collection, $writer)
    {
        $page = 0;
        $this->entityCollectionFactory = $collection['model'];

        while (true) {
            ++$page;
            $entityCollection = $this->_getEntityCollection(true);
            $entityCollection = $this->prepareEntityCollection($entityCollection, $key);
            $this->paginateCollection($page, $this->getItemsPerPage());
            if ($entityCollection->getSize() == 0) {
                break;
            }

            $exportData = $this->getExportData(
                $key,
                isset($collection['children']) ? $collection['children'] : [],
                $entityCollection,
                1
            );
            if ($page == 1) {
                $writer->setHeaderCols($this->_getHeaderColumns());
            }
            $this->addLogWriteln(__('Write to Source'), $this->output);
            foreach ($exportData as $dataRow) {
                $writer->writeRow($this->customFieldsMapping($dataRow));
                $this->counts++;
            }
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }
    }

    /**
     * @param $key
     * @param $collection
     * @return string
     */
    public function runCollectionSecond($key, $collection, $parents, $level)
    {
        $text = "";
        $page = 0;
        $array[self::ITEM] = [];
        while (true) {
            ++$page;
            $entityCollection = $this->_getEntityCollectionSecond($collection['model']);
            $entityCollection = $this->prepareEntityCollection($entityCollection, $key);
            if (!empty($parents)) {
                foreach ($parents as $field => $value) {
                    $entityCollection->addFieldToFilter($field, $value);
                }
            }
            $this->paginateCollection($page, $this->getItemsPerPage());
            if ($entityCollection->getSize() == 0) {
                break;
            }

            $exportData = $this->getExportData(
                $key,
                isset($collection['children']) ? $collection['children'] : [],
                $entityCollection,
                ++$level
            );
            $columns = $exportData[0];

            if ($page == 1) {
                if ($this->joined) {
                    $text .= $this->getColumns($columns) . self::SEPARATOR . $level . self::SEPARATOR;
                }
            }
            $temps = [];
            foreach ($exportData as $kk => $dataRow) {
                $getData = $this->getRow($this->customFieldsMapping($dataRow));
                if ($this->joined) {
                    $getData = $this->getRow($this->customFieldsMapping($dataRow));
                    $text .= $getData
                        . self::SEPARATOR . $level . self::SEPARATOR;
                } else {
                    $array[] = [self::ITEM => $getData];
                }
            }
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }
        if (!$this->joined) {
            $text = $array;
        }

        return $text;
    }

    /**
     * @return array
     */
    protected function getExportData($key, $children, $collection, $level)
    {
        $exportData = [];
        try {
            $rawData = $this->collectRawData($key, $collection);
            foreach ($rawData as $dataRow) {
                if ($dataRow) {
                    if (!empty($children)) {
                        foreach ($children as $keySecond => $collectSecond) {
                            $parents = [];
                            if (isset($collectSecond['parent'])) {
                                $code = $this->getChangeCode($key);
                                if ($collectSecond['parent'] == $key) {
                                    $parents[$collectSecond['parent_field']] = $dataRow[$code];
                                }
                            }
                            $dataRow[$keySecond] = $this->runCollectionSecond(
                                $keySecond,
                                $collectSecond,
                                $parents, $level
                            );
                        }
                    }
                    $exportData[] = $this->checkColumns($dataRow, $key);
                }
            }
        } catch (\Exception $e) {
            $this->addLogWriteln($e->getMessage(), $this->output, 'error');
            $this->_logger->critical($e);
        }

        return $exportData;
    }

    /**
     * @return array
     */
    public function getAttributeCollection()
    {
        return [];
    }

    /**
     * @return array
     */
    public function getFieldsForExport()
    {
        $options = [];
        foreach ($this->children as $typeName => $type) {
           if ($typeName == 'order') {
               foreach ($type['fields'] as $name => $values) {
                   $model = $this->createFactory->create($values['model']);
                   $options[$name] = [
                       'label' => __($values['label']),
                       'optgroup-name' => $name,
                       'value' => []
                   ];
                   $fields = $this->getChildHeaders($model);
                   foreach ($fields as $field) {
                       $options[$name]['value'][] = [
                           'label' => $field,
                           'value' => $field
                       ];
                   }
               }
           }
        }

        return $options;
    }

    /**
     * @return array
     */
    public function getFieldsForFilter()
    {
        $options = [];
        foreach ($this->children as $typeName => $type) {
            if ($typeName == 'order') {
                foreach ($type['fields'] as $name => $values) {
                    $model = $this->createFactory->create($values['model']);
                    $fields = $this->getChildHeaders($model);
                    $mergeFields = [];
                    if (isset($values['fields'])) {
                        $mergeFields = $values['fields'];
                    }
                    foreach ($fields as $field) {
                        if (isset($mergeFields[$field]) && $mergeFields[$field]['delete']) {
                            continue;
                        }
                        $options[$name][] = [
                            'label' => $field,
                            'value' => $field
                        ];
                    }
                }
            }
        }

        return $options;
    }

    /**
     * @return array
     */
    public function getFieldColumns()
    {
        $options = [];
        foreach ($this->children as $typeName => $type) {
            if ($typeName == 'order') {
                foreach ($type['fields'] as $name => $values) {
                    $mergeFields = [];
                    if (isset($values['fields'])) {
                        $mergeFields = $values['fields'];
                    }
                    $model = $this->createFactory->create($values['model']);
                    $fields = $this->describeTable($model);
                    foreach ($fields as $key => $field) {
                        $type = $this->helper->convertTypesTables($field['DATA_TYPE']);
                        $select = [];
                        if (isset($mergeFields[$key])) {
                            if (!$mergeFields[$key]['delete']) {
                                $type = $mergeFields[$key]['type'];
                                $select = $mergeFields[$key]['options'];
                            }
                        }
                        $options[$name][] = ['field' => $key, 'type' => $type, 'select' => $select];
                    }
                }
            }
        }

        return $options;
    }

    protected function getTableColumns()
    {
        $options = [];
        foreach ($this->children as $typeName => $type) {
            if ($typeName == 'catalog_category') {
                continue;
            }
            foreach ($type['fields'] as $name => $values) {
                $model = $this->createFactory->create($values['model']);
                $fields = $this->describeTable($model);
                foreach ($fields as $key => $field) {
                    $type = $this->helper->convertTypesTables($field['DATA_TYPE']);
                    $options[$name][$key] = ['type' => $type];
                }
            }
        }

        return $options;
    }

    /**
     * @return array
     */
    protected function getHeaders()
    {
        return array_keys($this->describeTable());
    }

    /**
     * @param null $model
     * @return array
     */
    protected function describeTable($model = null)
    {

        if ($model) {
            $resource = $model->getResource();
        } else {
            $resource = $this->factory->create()->getResource();
        }
        $table = $resource->getMainTable();
        $fields = $resource->getConnection()->describeTable($table);

        return $fields;
    }

    /**
     * @return array
     */
    protected function collectRawData($key, $collection)
    {
        $instr = $this->scopeFields($key);
        $allFields = $this->_parameters['all_fields'];
        $data = [];
        foreach ($collection as $itemId => $item) {
            if (!$allFields) {
                $data[] = $this->changedColumns($item->getData(), $instr);
            } else {
                $data[] = $this->addPartColumns($item, $instr, $key);
            }
        }

        $collection->clear();

        return $data;
    }

    /**
     * @param $key
     * @return mixed|string
     */
    protected function getChangeCode($key)
    {
        $newCode = '';
        foreach ($this->children as $typeName => $type) {
            if ($typeName == 'catalog_category') {
                continue;
            }
            foreach ($type['fields'] as $name => $values) {
                if ($name == $key) {
                    $newCode = $values['main_field'];
                }
            }
        }
        $instr = $this->scopeFields($key);

        $keyCode = $this->getKeyFromList($instr['list'], $newCode);
        if ($keyCode !== false && isset($instr['replaces'][$keyCode])) {
            $newCode = $instr['replaces'][$keyCode];
        }

        return $newCode;
    }

    /**
     * @param $key
     * @return array
     */
    protected function scopeFields($key)
    {
        $deps = $this->_parameters['dependencies'];
        $numbers = [];
        foreach ($deps as $ki => $dep) {
            if ($dep == $key) {
                $numbers[] = $ki;
            }
        }

        $listCodes = $this->filterCodes($numbers, $this->_parameters['list']);
        $replaces = $this->filterCodes($numbers, $this->_parameters['replace_code']);
        $replacesValues = $this->filterCodes($numbers, $this->_parameters['replace_value']);
        $instr = [
            'list' => $listCodes,
            'replaces' => $replaces,
            'replacesValues' => $replacesValues
        ];

        return $instr;
    }

    /**
     * @param $list
     * @param $search
     * @return false|int|string
     */
    protected function getKeyFromList($list, $search)
    {
        return array_search($search, $list);
    }

    /**
     * @param $numbers
     * @param $list
     * @return array
     */
    protected function filterCodes($numbers, $list)
    {
        $array = [];

        foreach ($list as $ki => $item) {
            if (in_array($ki, $numbers)) {
                $array[$ki] = $item;
            }
        }

        return $array;
    }

    /**
     * @param $data
     * @return array
     */
    protected function changedColumns($data, $instr)
    {
        $newData = [];
        foreach ($data as $code => $value) {
            if (in_array($code, $instr['list'])) {
                $ki = $this->getKeyFromList($instr['list'], $code);
                $newCode = $code;
                if ($ki !== false && isset($instr['replaces'][$ki])) {
                    $newCode = $instr['replaces'][$ki];
                }
                $newData[$newCode] = $value;
                if ($ki !== false && isset($instr['replacesValues'][$ki])
                    && !empty($instr['replacesValues'][$ki])) {
                    $newData[$newCode] = $instr['replacesValues'][$ki];
                }
            } else {
                $newData[$code] = $value;
            }
        }

        return $newData;
    }

    /**
     * @param $item
     * @return array
     */
    protected function addPartColumns($item, $instr, $key)
    {
        $newData = [];
        $reqCode = "";
        foreach ($this->children as $typeName => $type) {
            if ($typeName == 'catalog_category') {
                continue;
            }
            foreach ($type['fields'] as $name => $values) {
                if ($name == $key) {
                    $reqCode = $values['main_field'];
                }
            }
        }
        if (!in_array($reqCode, $instr['list'])) {
            $newData[$reqCode] = $item->getData($reqCode);
        }
        foreach ($instr['list'] as $k => $code) {
            $newCode = $code;
            if (isset($instr['replaces'][$k])) {
                $newCode = $instr['replaces'][$k];
            }
            $newData[$newCode] = $item->getData($code);

            if (isset($instr['replacesValues'][$k])
                && !empty($instr['replacesValues'][$k])) {
                $newData[$newCode] = $instr['replacesValues'][$k];
            }
        }

        return $newData;
    }

    /**
     * @param $data
     * @param $key
     * @return mixed
     */
    protected function checkColumns($data, $key)
    {
        $deps = $this->_parameters['dependencies'];
        $instr = $this->scopeFields($key);
        $allFields = $this->_parameters['all_fields'];
        if ($allFields) {
            foreach ($data as $code => $value) {
                if (!in_array($code, $instr['replaces']) && !in_array($code, $deps)) {
                    unset($data[$code]);
                }
            }
        }

        return $data;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    protected function customHeadersMapping($rowData)
    {
        foreach ($rowData as $key => $fieldName) {
            if (isset($this->_fieldsMap[$fieldName])) {
                $rowData[$key] = $this->_fieldsMap[$fieldName];
            }
        }

        return $rowData;
    }

    /**
     * @param $page
     * @param $pageSize
     */
    protected function paginateCollection($page, $pageSize)
    {
        $this->_getEntityCollection()->setPage($page, $pageSize);
    }

    /**
     * @return int|null
     */
    protected function getItemsPerPage()
    {
        if ($this->itemsPerPage === null) {
            $memoryLimit = trim(ini_get('memory_limit'));
            $lastMemoryLimitLetter = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
            switch ($lastMemoryLimitLetter) {
                case 'g':
                    $memoryLimit *= 1024;
                // fall-through intentional
                case 'm':
                    $memoryLimit *= 1024;
                // fall-through intentional
                case 'k':
                    $memoryLimit *= 1024;
                    break;
                default:
                    // minimum memory required by Magento
                    $memoryLimit = 250000000;
            }

            // Tested one product to have up to such size
            $memoryPerProduct = 100000;
            // Decrease memory limit to have supply
            $memoryUsagePercent = 0.8;
            // Minimum Products limit
            $minProductsLimit = 500;
            // Maximal Products limit
            $maxProductsLimit = 5000;

            $this->itemsPerPage = (int)
                ($memoryLimit * $memoryUsagePercent - memory_get_usage(true)) / $memoryPerProduct;
            if ($this->itemsPerPage < $minProductsLimit) {
                $this->itemsPerPage = $minProductsLimit;
            }
            if ($this->itemsPerPage > $maxProductsLimit) {
                $this->itemsPerPage = $maxProductsLimit;
            }
        }

        return $this->itemsPerPage;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    protected function customFieldsMapping($rowData)
    {
        if ($this->joined) {
            foreach ($rowData as $key => $value) {
                if (is_array($value)) {
                    $rowData[$key] = $this->optionRowToCellString($value);
                }
            }
        }

        return $rowData;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        foreach ($this->children as $key => $child) {
            $options[] = ['label' => $child['name'], 'value' => $key];
        }

        return $options;
    }

    /**
     * @param $model
     * @return array
     */
    public function getChildHeaders($model)
    {
        return array_keys($this->describeTable($model));
    }

    /**
     * @param array $data
     * @return string
     */
    protected function getColumns(array $data)
    {
        $behaviors = $this->_parameters['behavior_data'];
        $columns = [];
        if ($data) {
            foreach ($data as $key => $item) {
                $columns[$key] = false;
            }
        }

        return implode($behaviors['multiple_value_separator'], array_keys($columns));
    }

    /**
     * @param array $data
     * @return string
     */
    public function getRow(array $data)
    {
        $behaviors = $this->_parameters['behavior_data'];
        $rows = [];
        $newData = '';
        if ($this->joined) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $row[$key] = $this->optionRowToCellString($value);
                } elseif (is_object($value)) {
                    $row[$key] = $this->optionRowToCellString($value->getData());
                } else {
                    if (!$value) {
                        $value = "";
                    }
                    $rows[$key] = $value;
                }
            }

            $newData = implode($behaviors['multiple_value_separator'], $rows);
        } else {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $row[$key] = $value;
                } elseif (is_object($value)) {
                    $row[$key] = $value->getData();
                } else {
                    if (!$value) {
                        $value = "";
                    }
                    $rows[$key] = $value;
                }
            }

            $newData = $rows;
        }

        return $newData;
    }

    /**
     * @param $option
     * @return string
     */
    protected function optionRowToCellString($option)
    {
        $result = [];

        foreach ($option as $key => $value) {
            if (!is_array($value)) {
                $result[] = $key . ImportProduct::PAIR_NAME_VALUE_SEPARATOR . $value;
            } else {
                $result[] = $key . ImportProduct::PAIR_NAME_VALUE_SEPARATOR . $this->optionRowToCellString($value);
            }
        }

        return implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $result);
    }

    /**
     * @param $collection
     * @param $entity
     * @return mixed
     */
    protected function prepareEntityCollection($collection, $entity)
    {
        if (!isset($this->_parameters[\Firebear\ImportExport\Model\ExportJob\Processor::EXPORT_FILTER_TABLE])
            || !is_array($this->_parameters[\Firebear\ImportExport\Model\ExportJob\Processor::EXPORT_FILTER_TABLE])) {
            $exportFilter = [];
        } else {
            $exportFilter = $this->_parameters[\Firebear\ImportExport\Model\ExportJob\Processor::EXPORT_FILTER_TABLE];
        }
        $filters = [];
        foreach ($exportFilter as $data) {
            if ($data['entity'] == $entity) {
                $filters[$data['field']] = $data['value'];
            }
        }

        $fields = $this->getTableColumns();
        foreach ($filters as $key => $value) {
            if (isset($fields[$entity][$key])) {
                $type = $fields[$entity][$key]['type'];
                if ('text' == $type) {
                    $value = $value;
                    if (is_scalar($value)) {
                        trim($value);
                    }
                    $collection->addFieldToFilter($key, ['like' => "%{$value}%"]);
                } elseif ('int' == $type) {
                    if (is_array($value) && count($value) == 2) {
                        $from = array_shift($value);
                        $to = array_shift($value);

                        if (is_numeric($from)) {
                            $collection->addFieldToFilter($key, ['from' => $from]);
                        }
                        if (is_numeric($to)) {
                            $collection->addFieldToFilter($key, ['to' => $to]);
                        }
                    }
                } elseif ('date' == $type) {
                    if (is_array($value) && count($value) == 2) {
                        $from = array_shift($exportFilter[$value]);
                        $to = array_shift($exportFilter[$value]);

                        if (is_scalar($from) && !empty($from)) {
                            $date = (new \DateTime($from))->format('m/d/Y');
                            $collection->addFieldToFilter($key, ['from' => $date, 'date' => true]);
                        }
                        if (is_scalar($to) && !empty($to)) {
                            $date = (new \DateTime($to))->format('m/d/Y');
                            $collection->addFieldToFilter($key, ['to' => $date, 'date' => true]);
                        }
                    }
                }
            }
        }

        return $collection;
    }
}
