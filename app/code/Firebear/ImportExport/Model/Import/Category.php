<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Json\Helper\Data;
use Magento\Framework\Stdlib\StringUtils;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\Framework\Registry;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Symfony\Component\Console\Output\ConsoleOutput;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as ValidatorInterface;

class Category extends AbstractEntity
{

    use \Firebear\ImportExport\Traits\General;

    /**
     * Delimiter in category path.
     */
    const DELIMITER_CATEGORY = '/';

    const PSEUDO_MULTI_LINE_SEPARATOR = '|';

    const PAIR_NAME_VALUE_SEPARATOR = '=';

    /**
     * Column category url key.
     */
    const COL_URL = 'url_key';

    /**
     * Column category name.
     */
    const COL_NAME = 'name';

    /**
     * Column category parent id.
     */
    const COL_PARENT = 'parent_id';

    /**
     * Column category path.
     */
    const COL_PATH = 'path';

    /**
     * Core event manager proxy
     *
     * @var ManagerInterface
     */
    protected $eventManager = null;

    /**
     * Flag for replace operation.
     *
     * @var null
     */
    protected $replaceFlag = null;

    /**
     * @var CategoryProcessor
     */
    protected $categoryProcessor;

    /**
     * @var CollectionFactory
     */
    protected $categoryColFactory;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    protected $storeManager;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    protected $resource;

    protected $resourceFactory;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Categories text-path to ID hash.
     *
     * @var array
     */
    protected $categories = [];

    /**
     * @var array
     */
    protected $categoriesCache = [];

    protected $categoriesUrl;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    private $multiLineSeparatorForRegexp;

    protected $attributeCache = [];

    protected $attrData = [];

    protected $attributeCol;

    protected $sourceType;

    /**
     * @var \Firebear\ImportExport\Helper\Additional
     */
    protected $additional;

    /**
     * @var array
     */
    protected $customAttr = [
        'custom_apply_to_products',
        'custom_design',
        'custom_design_from',
        'custom_design_to',
        'custom_layout_update',
        'custom_use_parent_settings',
        'description'
    ];

    /**
     * @param Data $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param Config $config
     * @param ResourceConnection $resource
     * @param Helper $resourceHelper
     * @param StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param CollectionFactory $categoryColFactory
     * @param CategoryProcessor $categoryProcessor
     * @param CategoryFactory $categoryFactory
     * @param ManagerInterface $eventManager
     * @param CategoryRepositoryInterface $categoryRepository
     * @param \Firebear\ImportExport\Model\ResourceModel\Import\Data
     */
    public function __construct(
        Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        Config $config,
        ResourceConnection $resource,
        Helper $resourceHelper,
        StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        CollectionFactory $categoryColFactory,
        CategoryProcessor $categoryProcessor,
        CategoryFactory $categoryFactory,
        ManagerInterface $eventManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        \Symfony\Component\Console\Output\ConsoleOutput $output,
        Registry $registry,
        \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData,
        \Magento\Catalog\Model\ResourceModel\Category\Attribute\CollectionFactory $attributeColFactory,
        \Magento\Catalog\Model\ResourceModel\CategoryFactory $categoryResourceFactory,
        \Firebear\ImportExport\Helper\Additional $additional
    ) {
        $this->categoryColFactory = $categoryColFactory;
        $this->categoryProcessor = $categoryProcessor;
        $this->categoryFactory = $categoryFactory;
        $this->categoryRepository = $categoryRepository;
        $this->eventManager = $eventManager;
        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->output = $output;
        $this->attributeCol = $attributeColFactory;
        $this->resourceFactory = $categoryResourceFactory;
        $this->additional = $additional;
        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator
        );
        $this->_dataSourceModel = $importFireData;
        $this->initCategories()->initAttributes();
    }

    /**
     * Prepare all existing categories in array
     * @return $this
     */
    protected function initCategories()
    {
        if (empty($this->categories)) {
            $stores = $this->storeManager->getStores();
            $searchStores = [\Magento\Store\Model\Store::DEFAULT_STORE_ID];
            foreach ($stores as $store) {
                $searchStores[] = $store->getId();
            }
            foreach ($searchStores as $store) {
                $collection = $this->categoryColFactory->create();
                $collection->setStoreId($store)
                    ->addAttributeToSelect(self::COL_NAME)
                    ->addAttributeToSelect(self::COL_URL);
                /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
                foreach ($collection as $category) {
                
                    $structure = explode(self::DELIMITER_CATEGORY, $category->getPath());
                    $pathSize = count($structure);
                    $this->categoriesCache[$category->getId()] = $category;
                
                    if ($pathSize > 1) {
                        $path = [];
                        for ($i = 1; $i < $pathSize; $i++) {
                            $path[] = $collection->getItemById((int)$structure[$i])->getName();
                        }
                        $index = implode(self::DELIMITER_CATEGORY, $path);
                        $this->categories[$index] = $category->getId();
                    } else {
                        $this->categories[$category->getName()] = $category->getId();
                    }
                }
            }
        }

        $this->setupKeyUrls();

        return $this;
    }

    protected function initAttributes()
    {
        foreach ($this->attributeCol->create() as $item) {
            $this->attrData[$item->getAttributeCOde()] = $item->getData();
        }
    }

    protected function searchInCategories($id, $data)
    {
        $array = [];
        foreach ($data as $el) {
            if ($el['entity_id'] == $id) {
                $array[$el['value']];
            }
        }

        return $array;
    }

    /**
     * Create Category entity from raw data.
     *
     * @throws \Exception
     * @return bool Result of operation.
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _importData()
    {

      //  $this->registry->register("isSecureArea", true);

        $this->_validatedRows = null;
        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            $this->deleteCategories();
        } else {
            /**
             * If user select replace behavior all categories will be deleted first,
             * then new categories will be saved
             */
            $this->saveCategoriesData();
        }
        $this->eventManager->dispatch('catalog_category_import_finish_before', ['adapter' => $this]);

        return true;
    }

    /**
     * Delete categories is delete behavior is selected
     * @return $this
     */
    protected function deleteCategories()
    {
        $categoryId = null;
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $this->categoriesCache = [];
            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                if (isset($rowData['name']) && isset($this->categories[$rowData['name']])) {
                    $categoryId = (int)$this->categories[$rowData['name']];
                } elseif (isset($rowData['entity_id'])) {
                    $categoryId = (int)$rowData['entity_id'];
                }

                if ($categoryId) {
                    if ($this->categoryFactory->create()->
                        getCollection()->addFieldToFilter('entity_id', $categoryId)
                        ->getSize()) {
                            try {
                            $category = $this->categoryRepository->get($categoryId);
                            if (!$this->getResource()->isForbiddenToDelete($categoryId)) {
                                $this->addRowError(
                                    'Cannot delete category ',
                                    $rowNum
                                );
                            } else {
                            $this->categoryRepository->delete($category);
                            }
                            } catch (\Magento\Framework\Exception\StateException $e) {
                                $this->addRowError(
                                    $e->getMessage(),
                                    $rowNum
                                );
                            }
                    }
                } else {
                    $this->addRowError(
                        'Cannot delete category ',
                        $rowNum
                    );
                }
            }
        }

        return $this;
    }

    /**
     * Delete all categories when replace behavior is selected
     * @return $this
     */
    protected function deleteAllCategories()
    {
        $this->deleteCategories();

        /**
         * Clear categories cache.
         */
        $this->categories = [];
        $this->categoriesCache = [];

        /**
         * Re-init default categories.
         */
        $this->initCategories();

        return $this;
    }

    /**
     * Gather and save information about product entities.
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
    protected function saveCategoriesData()
    {
        /**
         * Delete all categories if replace behavior is selected
         */
        if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
            $this->deleteAllCategories();
        }
        $this->_initSourceType('url');

        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $this->categoriesCache = [];
            $bunch = $this->prepareImagesFromSource($bunch);
            foreach ($bunch as $rowNum => $rowData) {
                $rowData = $this->joinIdenticalyData($rowData);
                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addLogWriteln(__('name: %1 is not valided', $rowData['name']), $this->output, 'info');
                    continue;
                }
                $time = explode(" ", microtime());
                $startTime = $time[0] + $time[1];
                $name = $rowData['name'];
                if (!$this->validateRow($rowData, $rowNum)) {
                    continue;
                }

                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }

                if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
                    if (isset($rowData['entity_id'])) {
                        unset($rowData['entity_id']);
                    }
                }

                $rowData = $this->checkUrl($rowData);
                $rowData = $this->changeData($rowData);
                $rowPath = null;
                $rowPath = $rowData[self::COL_NAME];
           /*     if (strpos($rowData[self::COL_NAME], self::DELIMITER_CATEGORY) !== false) {
                    $rowPath = $rowData[self::COL_NAME];
                } elseif (isset($rowData[self::COL_PARENT])) {
                    $rowPath = (int)$rowData[self::COL_PARENT];
                } elseif (isset($rowData[self::COL_PATH])) {
                    $rowPath = $rowData[self::COL_PATH] . self::DELIMITER_CATEGORY . $rowData[self::COL_NAME];
                }*/
                
                if (!empty($rowPath)) {
                    if (is_int($rowPath)) {
                        try {
                            $category = $this->categoryFactory->create();
                            if (!($parentCategory = isset($this->categoriesCache[$rowPath])
                                ? $this->categoriesCache[$rowPath] : null)
                            ) {
                                $parentCategory = $this->categoryFactory->create()->load($rowPath);
                            }
                            $category->setParentId($rowPath);
                            $category->setIsActive(true);
                            $category->setIncludeInMenu(true);
                            $category->setAttributeSetId($category->getDefaultAttributeSetId());
                            $category->addData($rowData);
                            $category->setPath($parentCategory->getPath());
                            $this->categoryRepository->save($category);
                            $this->categoriesCache[$category->getId()] = $category;
                        } catch (\Exception $e) {
                            $this->getErrorAggregator()->addError($e->getCode(),
                                ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                                $this->_processedRowsCount,
                                null,
                                $e->getMessage()
                            );
                            $this->_processedRowsCount++;
                        }
                    } else {
                        if (!isset($this->categories[$rowPath])) {
                            $result = $this->prepareCategoriesByPath($rowPath, $rowData);
                        } else {
                            $result = $this->updateCategoriesByPath($rowPath, $rowData);
                        }
                        if ($result === false) {
                            continue;
                        }
                    }
                }
                $time = explode(" ", microtime());
                $endTime = $time[0] + $time[1];
                $totalTime = $endTime - $startTime;
                $totalTime = round($totalTime, 5);
                $this->addLogWriteln(__('name: %1 .... %2s', $name, $totalTime), $this->output, 'info');
            }

            $this->eventManager->dispatch(
                'catalog_category_import_bunch_save_after',
                ['adapter' => $this, 'bunch' => $bunch]
            );
        }
        return $this;
    }

    /**
     * Prepare new category by path.
     *
     * @param $rowPath
     * @param $rowData
     *
     * @return $this
     */
    protected function prepareCategoriesByPath($rowPath, $rowData)
    {
        $result = true;
        $parentId = \Magento\Catalog\Model\Category::TREE_ROOT_ID;
        $pathParts = explode(self::DELIMITER_CATEGORY, $rowPath);
        $path = '';
        foreach ($pathParts as $pathPart) {
            $path .= $pathPart;
            if (!isset($this->categories[$path])) {
                try {
                    $category = $this->categoryFactory->create();
                    if (!($parentCategory = isset($this->categoriesCache[$parentId])
                        ? $this->categoriesCache[$parentId] : null)
                    ) {
                        $parentCategory = $this->categoryFactory->create()->load($parentId);
                    }
                    $category->addData($rowData);
                    $category->setStoreId(0);
                    $category->setParentId($parentId);
                    $category->setIsActive(true);
                    $category->setIncludeInMenu(true);
                    $category->setAttributeSetId($category->getDefaultAttributeSetId());
                    $category->setName($pathPart);
                    $category->setPath($parentCategory->getPath());
                    $category = $this->categoryRepository->save($category);
                    if ($category->getId()) {

                        $category->setPath($parentCategory->getPath() . self::DELIMITER_CATEGORY . $category->getId());
                        $this->categoryRepository->save($category);
                    }
                    $this->categoriesCache[$category->getId()] = $category;
                    $this->categories[$path] = $category->getId();
                } catch (\Exception $e) {
                    $this->getErrorAggregator()->addError(
                        $e->getCode(),
                        ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                        $this->_processedRowsCount,
                        null,
                        $e->getMessage()
                    );
                    $result = false;
                    $this->_processedRowsCount++;
                }
            }
            if (isset($this->categories[$path])) {
                $parentId = $this->categories[$path];

                $path .= self::DELIMITER_CATEGORY;
            }
        }

        return $result;
    }

    /**
     * Update existing category by path.
     *
     * @param $rowPath
     * @param $rowData
     *
     * @return $this
     */
    protected function updateCategoriesByPath($rowPath, $rowData)
    {
        $result = true;
        $categoryId = $this->categories[$rowPath];
        $category = $this->categoryFactory->create()->load($categoryId);
        /**
         * Avoid changing category name and path
         */
        if (isset($rowData[self::COL_NAME])) {
            unset($rowData[self::COL_NAME]);
        }

        if (isset($rowData[self::COL_PATH])) {
            unset($rowData[self::COL_PATH]);
        }
        try {
            $category->addData($rowData);
            $category->setStoreId(0);
            $category->save();
        } catch (\Exception $e) {
            $this->getErrorAggregator()->addError(
                $e->getCode(),
                ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                $this->_processedRowsCount,
                null,
                $e->getMessage()
            );
            $result = false;
            $this->_processedRowsCount++;
        }

        return $result;
    }

    /**
     * Validate data row.
     *
     * @param array $rowData
     * @param int $rowNum
     * @return boolean
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function validateRow(array $rowData, $rowNum)
    {
        if (isset($this->_validatedRows[$rowNum])) {
            // check that row is already validated
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }
        $this->_validatedRows[$rowNum] = true;
        $this->_processedEntitiesCount++;

        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * EAV entity type code getter.
     *
     * @abstract
     * @return string
     */
    public function getEntityTypeCode()
    {
        return 'catalog_category';
    }

    protected function setupKeyUrls()
    {
        $this->categoriesUrl = [];
        $collection = $this->categoryColFactory->create();
        $collection->addAttributeToSelect(self::COL_URL);
        foreach ($collection as $category) {
            $this->categoriesUrl[] = $category[self::COL_URL];
        }
    }

    /**
     * @param $rowData
     * @return mixed
     */
    protected function checkUrl($rowData)
    {
        if (isset($rowData[self::COL_URL])) {
            $url = $this->searchUrl($rowData[self::COL_URL]);
            $rowData[self::COL_URL] = $url;
            $this->categoriesUrl[] = $url;
        }

        return $rowData;
    }

    /**
     * @param $url
     * @return mixed
     */
    protected function searchUrl($url)
    {
        if (in_array($url, $this->categoriesUrl)) {
            preg_match_all("/\d+$/i", $url, $out);
            if (isset($out[0][0])) {
                $counter = (int)$out[0][0];
                $url = $this->searchUrl(str_replace($counter, ++$counter, $url));
            } else {
                $url = $url;
            }
        }
        
        if ($this->checkUrlKeyDuplicates($url)) {
            preg_match_all("/\d+$/i", $url, $out);
            if (isset($out[0][0])) {
                $counter = (int)$out[0][0];
                $url = $this->searchUrl(str_replace($counter, ++$counter, $url));
            }
        } 
        return $url;
    }

    /**
     * @return array
     */
    protected function getDataAttributes()
    {
        $category = $this->categoryFactory->create()->getResource();
        $attr = $category->getAttribute(self::COL_NAME);
        $attrName = $attr->getId();
        $table = $attr->getBackendTable();
        $entityTable = $category->getEntityTable();
        $collection = $this->categoryColFactory->create();
        $connection = $collection->getConnection();
        $indexList = $connection->getIndexList($entityTable);
        $entityIdField = $indexList[$connection->getPrimaryKeyName($entityTable)]['COLUMNS_LIST'][0];
        $stores = $this->storeManager->getStores();
        $searchStores = [\Magento\Store\Model\Store::DEFAULT_STORE_ID];
        foreach ($stores as $store) {
            $searchStores[] = $store->getId();
        }
        $select = $connection->select()->from(
            ['t_d' => $table],
            [$entityIdField, 'value']
        )
            ->where(
                't_d.attribute_id=?',
                $attrName
            )
            ->where(
                't_d.store_id IN(?)',
                $searchStores
            )
            ->where(
                't_d.store_id = ?',
                $connection->getIfNullSql('t_d.store_id', \Magento\Store\Model\Store::DEFAULT_STORE_ID)
            );

        return $this->_connection->fetchAll($select);
    }

    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $currentDataSize = 0;
        $bunchRows = [];
        $startNewBunch = false;
        $nextRowBackup = [];
        $maxDataSize = $this->_resourceHelper->getMaxDataSize();
        $bunchSize = $this->_importExportData->getBunchSize();

        $source->rewind();
        $this->_dataSourceModel->cleanBunches();
        $file = null;
        $jobId = null;
        if (isset($this->_parameters['file'])) {
            $file = $this->_parameters['file'];
        }
        if (isset($this->_parameters['job_id'])) {
            $jobId = $this->_parameters['job_id'];
        }

        while ($source->valid() || $bunchRows) {
            if ($startNewBunch || !$source->valid()) {
                $this->_dataSourceModel->saveBunches($this->getEntityTypeCode(), $this->getBehavior(), $jobId, $file,
                    $bunchRows);
                $bunchRows = $nextRowBackup;
                $currentDataSize = strlen(serialize($bunchRows));
                $startNewBunch = false;
                $nextRowBackup = [];
            }
            if ($source->valid()) {
                try {
                    $rowData = $source->current();
                } catch (\InvalidArgumentException $e) {
                    $this->addRowError($e->getMessage(), $this->_processedRowsCount);
                    $this->_processedRowsCount++;
                    $source->next();
                    continue;
                }

                $this->_processedRowsCount++;

                $rowSize = strlen($this->jsonHelper->jsonEncode($rowData));

                $isBunchSizeExceeded = $bunchSize > 0 && count($bunchRows) >= $bunchSize;

                if ($currentDataSize + $rowSize >= $maxDataSize || $isBunchSizeExceeded) {
                    $startNewBunch = true;
                    $nextRowBackup = [$source->key() => $rowData];
                } else {
                    $bunchRows[$source->key()] = $rowData;
                    $currentDataSize += $rowSize;
                }

                $source->next();
            }
        }

        return $this;
    }

    private function parseAdditionalAttributes($additionalAttributes)
    {
        return empty($this->_parameters[Import::FIELDS_ENCLOSURE])
            ? $this->parseAttributesWithoutWrappedValues($additionalAttributes)
            : $this->parseAttributesWithWrappedValues($additionalAttributes);
    }

    private function parseAttributesWithoutWrappedValues($attributesData)
    {
        $attributeNameValuePairs = explode($this->getMultipleValueSeparator(), $attributesData);
        $preparedAttributes = [];
        $code = '';
        foreach ($attributeNameValuePairs as $attributeData) {
            //process case when attribute has ImportModel::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR inside its value
            if (strpos($attributeData, self::PAIR_NAME_VALUE_SEPARATOR) === false) {
                if (!$code) {
                    continue;
                }
                $preparedAttributes[$code] .= $this->getMultipleValueSeparator() . $attributeData;
                continue;
            }
            list($code, $value) = explode(self::PAIR_NAME_VALUE_SEPARATOR, $attributeData, 2);
            $code = mb_strtolower($code);
            $preparedAttributes[$code] = $value;
        }
        return $preparedAttributes;
    }

    private function parseAttributesWithWrappedValues($attributesData)
    {
        $attributes = [];
        preg_match_all('~((?:[a-zA-Z0-9_])+)="((?:[^"]|""|"' . $this->getMultiLineSeparatorForRegexp() . '")+)"+~',
            $attributesData,
            $matches
        );
        foreach ($matches[1] as $i => $attributeCode) {
            $attribute = $this->retrieveAttributeByCode($attributeCode);
            $value = 'multiselect' != $attribute->getFrontendInput()
                ? str_replace('""', '"', $matches[2][$i])
                : '"' . $matches[2][$i] . '"';
            $attributes[mb_strtolower($attributeCode)] = $value;
        }
        return $attributes;
    }

    public function getMultipleValueSeparator()
    {
        if (!empty($this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR])) {
            return $this->_parameters[Import::FIELD_FIELD_MULTIPLE_VALUE_SEPARATOR];
        }
        return Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR;
    }

    private function getMultiLineSeparatorForRegexp()
    {
        if (!$this->multiLineSeparatorForRegexp) {
            $this->multiLineSeparatorForRegexp = in_array(self::PSEUDO_MULTI_LINE_SEPARATOR, str_split('[\^$.|?*+(){}'))
                ? '\\' . self::PSEUDO_MULTI_LINE_SEPARATOR
                : self::PSEUDO_MULTI_LINE_SEPARATOR;
        }
        return $this->multiLineSeparatorForRegexp;
    }

    public function retrieveAttributeByCode($attrCode)
    {
        /** @var string $attrCode */
        $attrCode = mb_strtolower($attrCode);

        if (!isset($this->attributeCache[$attrCode])) {
            $this->attributeCache[$attrCode] = $this->getResource()->getAttribute($attrCode);
        }

        return $this->attributeCache[$attrCode];
    }

    public function changeData($rowData)
    {
        foreach ($this->customAttr as $value) {
            $rowData[$value] = stripslashes($rowData[$value]);
        }
        foreach ($rowData as $key => $value) {
            $h = $this->attrData[$key];
            if ($h['frontend_input'] == 'select') {
                $data = $this->retrieveAttributeByCode($key);
                if (!empty($data->getOptions())) {
                    foreach ($data->getOptions() as $valueOptions) {
                        $valueData = $valueOptions->getData();
                        if ($valueData['label'] == $value) {
                            $rowData[$key] = $valueData['value'];
                        }
                    }
                }
            }

        }

        return $rowData;
    }

    protected function getResource()
    {
        if (!$this->resource) {
            $this->resource = $this->resourceFactory->create();
        }
        return $this->resource;
    }

    protected function checkUrlKeyDuplicates($urlKeys)
    {
        $resource = $this->getResource();
        $select = $this->_connection->select()->from(
            ['url_rewrite' => $resource->getTable('url_rewrite')],
            ['request_path', 'store_id']
        )->joinLeft(
            ['cpe' => $resource->getTable('catalog_product_entity')],
            "cpe.entity_id = url_rewrite.entity_id"
        )->where('request_path LIKE "%' . $urlKeys . '%"');
        $urlKeyDuplicates = $this->_connection->fetchAssoc(
            $select
        );

        return count($urlKeyDuplicates);
    }

    protected function prepareImagesFromSource($bunch)
    {
        $image = 'image';

        foreach ($bunch as $rowNum => &$rowData) {
            if (empty($rowData[$image])) {
                continue;
            }
            $importImage = $rowData[$image];
            $imageSting = mb_strtolower(preg_replace('/[^a-z0-9\._-]+/i', '', $importImage));
            if ($this->sourceType) {
                $this->sourceType->importImageCategory($importImage, $imageSting);
            }
            $imageArr = $imageSting;

            $rowData[$image] = $imageArr;
        }

        return $bunch;
    }

    protected function _initSourceType($type)
    {
        if (!$this->sourceType) {
            $this->sourceType = $this->additional->getSourceModelByType($type);
            $this->sourceType->setData($this->_parameters);
        }
    }
}
