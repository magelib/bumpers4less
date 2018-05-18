<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Magento\CatalogImportExport\Model\Import\Product\TaxClassProcessor;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;
use Magento\CatalogImportExport\Model\Import\Product as MagentoProduct;
use Firebear\ImportExport\Model\Import;
use Magento\Framework\Model\ResourceModel\Db\TransactionManagerInterface;
use Magento\Framework\Model\ResourceModel\Db\ObjectRelationProcessor;
use Magento\CatalogImportExport\Model\Import\Product\RowValidatorInterface as ValidatorInterface;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Magento\Framework\App\Filesystem\DirectoryList;
use \Firebear\ImportExport\Ui\Component\Listing\Column\Import\Source\Configurable\Type\Options as TypeOptions;
use Magento\Bundle\Model\Product\Price as BundlePrice;
use Magento\BundleImportExport\Model\Import\Product\Type\Bundle;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\Tax\Model\ClassModel;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogImportExport\Model\Import\Product\Option;

class Product extends MagentoProduct
{
    use \Firebear\ImportExport\Traits\General;

    protected $addFields = [
        'manage_stock',
        'use_config_manage_stock',
        'qty',
        'min_qty',
        'use_config_min_qty',
        'min_sale_qty',
        'use_config_min_sale_qty',
        'max_sale_qty',
        'use_config_max_sale_qty',
        'is_qty_decimal',
        'backorders',
        'use_config_backorders',
        'notify_stock_qty',
        'use_config_notify_stock_qty',
        'enable_qty_increments',
        'use_config_enable_qty_inc',
        'qty_increments',
        'use_config_qty_increments',
        'is_in_stock',
        'low_stock_date',
        'stock_status_changed_auto',
        'is_decimal_divided',
        'has_options'
    ];
    /**
     * Default website id
     */
    const DEFAULT_WEBSITE_ID = 1;

    /**
     * Used when create new attributes in column name
     */
    const ATTRIBUTE_SET_GROUP = 'attribute_set_group';

    /**
     * Attribute sets column name
     */
    const ATTRIBUTE_SET_COLUMN = 'attribute_set';

    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $request;

    /**
     * @var \Firebear\ImportExport\Helper\Data
     */
    protected $helper;

    /**
     * @var \Firebear\ImportExport\Helper\Additional
     */
    protected $additional;

    /**
     * @var \Firebear\ImportExport\Model\Source\Type\AbstractType
     */
    protected $sourceType;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Eav\Model\EntityFactory
     */
    protected $eavEntityFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory
     */
    protected $groupCollectionFactory;

    /**
     * @var array
     */
    protected $_attributeSetGroupCache;

    /**
     * @var \Magento\Catalog\Helper\Product
     */
    protected $productHelper;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    protected $_debugMode;

    /**
     * @var \Firebear\ImportExport\Model\Source\Import\Config
     */
    protected $fireImportConfig;

    protected $duplicateFields = ['sku', 'scope', 'url_key'];

    /**
     * @var \Magento\Framework\App\ProductMetadata
     */
    protected $productMetadata;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory
     */
    protected $eavCollectionFactory;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection
     */
    protected $eavSetCollection;

    /**
     * @var \Magento\ConfigurableProduct\Helper\Product\Options\Factory
     */
    protected $optionConfFactory;


    public $onlyUpdate = 0;

    /**
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $groupFactory;

    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $websiteFactory;

    protected $repeatUrls = [];

    /**
     * @var \Magento\Tax\Model\ResourceModel\TaxClass\CollectionFactory
     */
    protected $collectionTaxFactory;

    protected $storeManager;

    protected $notValidedSku = [];

    private $importCollection;

    protected $priceRuleConditionFactory;


    protected $platform;

    /**
     * Product constructor.
     *
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Firebear\ImportExport\Helper\Data $helper
     * @param \Firebear\ImportExport\Helper\Additional $additional
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration
     * @param \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface $stockStateProvider
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\ImportExport\Model\Import\Config $importConfig
     * @param \Firebear\ImportExport\Model\Source\Import\Config $fireImportConfig
     * @param \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory
     * @param MagentoProduct\OptionFactory $optionFactory
     * @param Product\OptionFactory $fireOptionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setColFactory
     * @param MagentoProduct\Type\Factory $productTypeFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\LinkFactory $linkFactory
     * @param \Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory $proxyProdFactory
     * @param \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFac
     * @param DateTime\TimezoneInterface $localeDate
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     * @param \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry
     * @param MagentoProduct\StoreResolver $storeResolver
     * @param MagentoProduct\SkuProcessor $skuProcessor
     * @param MagentoProduct\CategoryProcessor $categoryProcessor
     * @param MagentoProduct\Validator $validator
     * @param ObjectRelationProcessor $objectRelationProcessor
     * @param TransactionManagerInterface $transactionManager
     * @param TaxClassProcessor $taxClassProcessor
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Catalog\Model\Product\Url $productUrl
     * @param \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory
     * @param \Magento\Eav\Model\EntityFactory $eavEntityFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param ConsoleOutput $output
     * @param \Magento\Framework\App\ProductMetadata $productMetadata
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $eavCollectionFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $eavSetCollection
     * @param \Magento\ConfigurableProduct\Helper\Product\Options\Factory $optionConfFactory
     * @param \Magento\Customer\Model\GroupFactory $groupFactory
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData
     * @param Product\CategoryProcessor $fireCategoryProcessor
     * @param UploaderFactory $fireUploader
     * @param \Magento\Tax\Model\ResourceModel\TaxClass\CollectionFactory $collectionTaxFactory
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \Firebear\ImportExport\Model\ResourceModel\Job\CollectionFactory $importCollectionFactory
     * @param Product\Price\Rule\ConditionFactory $priceRuleConditionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Firebear\ImportExport\Helper\Data $helper,
        \Firebear\ImportExport\Helper\Additional $additional,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface $errorAggregator,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
        \Magento\CatalogInventory\Api\StockConfigurationInterface $stockConfiguration,
        \Magento\CatalogInventory\Model\Spi\StockStateProviderInterface $stockStateProvider,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\ImportExport\Model\Import\Config $importConfig,
        \Firebear\ImportExport\Model\Source\Import\Config $fireImportConfig,
        \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory,
        \Magento\CatalogImportExport\Model\Import\Product\OptionFactory $optionFactory,
        \Firebear\ImportExport\Model\Import\Product\OptionFactory $fireOptionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setColFactory,
        \Magento\CatalogImportExport\Model\Import\Product\Type\Factory $productTypeFactory,
        \Magento\Catalog\Model\ResourceModel\Product\LinkFactory $linkFactory,
        \Magento\CatalogImportExport\Model\Import\Proxy\ProductFactory $proxyProdFactory,
        \Magento\CatalogImportExport\Model\Import\UploaderFactory $uploaderFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $stockResItemFac,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        DateTime $dateTime,
        LoggerInterface $logger,
        \Magento\Framework\Indexer\IndexerRegistry $indexerRegistry,
        \Magento\CatalogImportExport\Model\Import\Product\StoreResolver $storeResolver,
        \Magento\CatalogImportExport\Model\Import\Product\SkuProcessor $skuProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor $categoryProcessor,
        \Magento\CatalogImportExport\Model\Import\Product\Validator $validator,
        ObjectRelationProcessor $objectRelationProcessor,
        TransactionManagerInterface $transactionManager,
        TaxClassProcessor $taxClassProcessor,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Product\Url $productUrl,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory,
        \Magento\Eav\Model\EntityFactory $eavEntityFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory $groupCollectionFactory,
        \Magento\Catalog\Helper\Product $productHelper,
        \Symfony\Component\Console\Output\ConsoleOutput $output,
        \Magento\Framework\App\ProductMetadata $productMetadata,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $eavCollectionFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\Collection $eavSetCollection,
        \Magento\ConfigurableProduct\Helper\Product\Options\Factory $optionConfFactory,
        \Magento\Customer\Model\GroupFactory $groupFactory,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData,
        \Firebear\ImportExport\Model\Import\Product\CategoryProcessor $fireCategoryProcessor,
        \Firebear\ImportExport\Model\Import\UploaderFactory $fireUploader,
        \Magento\Tax\Model\ResourceModel\TaxClass\CollectionFactory $collectionTaxFactory,
        \Magento\Store\Model\StoreManager $storeManager,
        \Firebear\ImportExport\Model\ResourceModel\Job\CollectionFactory $importCollectionFactory,
        \Firebear\ImportExport\Model\Import\Product\Price\Rule\ConditionFactory $priceRuleConditionFactory,
        array $data = []
    ) {
        $this->output = $output;
        $this->request = $request;
        $this->helper = $helper;
        $this->attributeFactory = $attributeFactory;
        $this->eavEntityFactory = $eavEntityFactory;
        $this->groupCollectionFactory = $groupCollectionFactory;
        $this->productHelper = $productHelper;
        $this->additional = $additional;
        $this->_logger = $logger;
        $this->fireImportConfig = $fireImportConfig;
        $this->groupFactory = $groupFactory;
        $this->storeManager = $storeManager;
        $this->priceRuleConditionFactory = $priceRuleConditionFactory;
        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator,
            $eventManager,
            $stockRegistry,
            $stockConfiguration,
            $stockStateProvider,
            $catalogData,
            $importConfig,
            $resourceFactory,
            $optionFactory,
            $setColFactory,
            $productTypeFactory,
            $linkFactory,
            $proxyProdFactory,
            $uploaderFactory,
            $filesystem,
            $stockResItemFac,
            $localeDate,
            $dateTime,
            $logger,
            $indexerRegistry,
            $storeResolver,
            $skuProcessor,
            $categoryProcessor,
            $validator,
            $objectRelationProcessor,
            $transactionManager,
            $taxClassProcessor,
            $scopeConfig,
            $productUrl,
            $data
        );

        $this->_optionEntity = isset(
            $data['option_entity']
        )
            ? $data['option_entity']
            : $fireOptionFactory->create(
                ['data' => ['product_entity' => $this]]
            );
        $this->_debugMode = $helper->getDebugMode();
        $this->productMetadata = $productMetadata;
        $this->productRepository = $productRepository;
        $this->collectionFactory = $collectionFactory;
        $this->eavCollectionFactory = $eavCollectionFactory;
        $this->eavSetCollection = $eavSetCollection;
        $this->optionConfFactory = $optionConfFactory;
        $this->websiteFactory = $websiteFactory;
        $this->_dataSourceModel = $importFireData;
        $this->categoryProcessor = $fireCategoryProcessor;
        $this->_uploaderFactory = $fireUploader;
        $this->collectionTaxFactory = $collectionTaxFactory;
        $this->importCollection = $importCollectionFactory->create();
    }

    /**
     * Initialize source type model
     *
     * @param $type
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _initSourceType($type)
    {
        if (!$this->sourceType) {
            $this->sourceType = $this->additional->getSourceModelByType($type);
            $this->sourceType->setData($this->_parameters);
        }
    }

    /**
     * import product data
     */
    public function importData()
    {
        $this->notValidedSku = [];
        if ($this->_parameters['behavior'] == Import::FIREBEAR_ONLY_UPDATE) {
            $this->onlyUpdate = 1;
            $this->_parameters['behavior'] = Import::BEHAVIOR_APPEND;
        }
        $this->_validatedRows = null;

        if (Import::BEHAVIOR_REPLACE == $this->getBehavior()) {
            $this->_replaceFlag = true;
            $this->replaceProducts();
        } elseif (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            $this->_deleteProducts();
        } else {
            $this->saveProductsData();
        }
        $this->_eventManager->dispatch('catalog_product_import_finish_before', ['adapter' => $this]);

        return true;
    }

    /**
     * Replace imported products.
     *
     * @return $this
     */
    protected function replaceProducts()
    {
        $this->deleteProductsForReplacement();
        $this->_oldSku = $this->skuProcessor->reloadOldSkus()->getOldSkus();
        $this->_validatedRows = null;
        $this->setParameters(
            array_merge(
                $this->getParameters(),
                ['behavior' => Import::BEHAVIOR_APPEND]
            )
        );
        $this->saveProductsData();

        return $this;
    }

    /**
     * Save products data.
     *
     * @return $this
     */
    protected function saveProductsData()
    {
        $this->initMessageTemplates();

        $this->saveProducts();
        foreach ($this->_productTypeModels as $productTypeModel) {
            $productTypeModel->saveData();
        }
        $this->_saveLinks();
        $this->_saveStockItem();
        if ($this->_replaceFlag) {
            $this->getOptionEntity()->clearProductsSkuToId();
        }
        $this->getOptionEntity()->importData();

        return $this;
    }

    protected function initMessageTemplates()
    {
        // @codingStandardsIgnoreStart
        $this->addMessageTemplate(
            Option::ERROR_INVALID_STORE,
            __('Value for \'price\' sub attribute in \'store\' attribute contains incorrect value')
        );
        $this->addMessageTemplate(
            Option::ERROR_INVALID_TYPE,
            __('Value for \'type\' sub attribute in \'custom_options\' attribute contains incorrect value, acceptable values are: \'dropdown\', \'checkbox\'')
        );
        $this->addMessageTemplate(Option::ERROR_EMPTY_TITLE, __('Please enter a value for title.'));
        $this->addMessageTemplate(
            Option::ERROR_INVALID_PRICE,
            __('Value for \'price\' sub attribute in \'custom_options\' attribute contains incorrect value')
        );
        $this->addMessageTemplate(
            Option::ERROR_INVALID_MAX_CHARACTERS,
            __('Value for \'maximum characters\' sub attribute in \'custom_options\' attribute contains incorrect value')
        );
        $this->addMessageTemplate(
            Option::ERROR_INVALID_SORT_ORDER,
            __('Value for \'sort order\' sub attribute in \'custom_options\' attribute contains incorrect value')
        );
        $this->addMessageTemplate(
            Option::ERROR_INVALID_ROW_PRICE,
            __('Value for \'value price\' sub attribute in \'custom_options\' attribute contains incorrect value')
        );
        $this->addMessageTemplate(
            Option::ERROR_INVALID_ROW_SORT,
            __('Value for \'sort order\' sub attribute in \'custom_options\' attribute contains incorrect value')
        );
        $this->addMessageTemplate(
            Option::ERROR_AMBIGUOUS_NEW_NAMES,
            __('This name is already being used for custom option. Please enter a different name.')
        );
        $this->addMessageTemplate(
            Option::ERROR_AMBIGUOUS_OLD_NAMES,
            __('This name is already being used for custom option. Please enter a different name.')
        );
        $this->addMessageTemplate(
            Option::ERROR_AMBIGUOUS_TYPES,
            __('Custom options have different types.')
        );

        return $this;
    }

    /**
     * Gather and save information about product entities.
     *
     * @return $this
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function saveProducts()
    {
        if (isset($this->_parameters['import_source']) && $this->_parameters['import_source'] != 'file') {
            $this->_initSourceType($this->_parameters['import_source']);
        }
        $configurableData = [];
        $confSwitch = $this->_parameters['configurable_switch'];

        $isPriceGlobal = $this->_catalogData->isPriceGlobal();
        $productLimit = null;
        $productsQty = null;
        while ($nextBunch = $this->_dataSourceModel->getNextBunch()) {
            $entityRowsIn = $entityRowsUp = [];
            $attributes = [];
            $this->websitesCache = $this->categoriesCache = [];
            $mediaGallery = $uploadedImages = [];
            $tierPrices = [];
            $previousType = $prevAttributeSet = null;
            $existingImages = $this->getExistingImages($nextBunch);
            if ($this->sourceType) {
                $nextBunch = $this->prepareImagesFromSource($nextBunch);
            }
            $prevData = [];
            foreach ($nextBunch as $rowNum => $rowData) {
                if (isset($rowData[self::COL_CATEGORY])) {
                    $rowData[self::COL_CATEGORY] = $this->categoriesMapping($rowData[self::COL_CATEGORY]);
                    $rowData[self::COL_CATEGORY] = utf8_decode($rowData[self::COL_CATEGORY]);
                }
                $rowData = $this->joinIdenticalyData($rowData);

                if (isset($rowData['_attribute_set']) && isset($rowData['attribute_set_code'])) {
                    unset($rowData['attribute_set_code']);
                }
                if (!isset($rowData['_attribute_set'])) {
                    $collectSets = $this->_attrSetIdToName;
                    reset($collectSets);
                    $rowData['_attribute_set'] = current($collectSets);
                }
                $createValuesAllowed = (bool)$this->scopeConfig->getValue(
                    \Firebear\ImportExport\Model\Import::CREATE_ATTRIBUTES_CONF_PATH,
                    \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                );
                $rowData[self::COL_TYPE] = strtolower($rowData[self::COL_TYPE]);
                $productType = isset($rowData[self::COL_TYPE]) ? $rowData[self::COL_TYPE] : null;

                if ($productType) {
                    $productTypeModel = $this->_productTypeModels[$productType];
                    if ($createValuesAllowed) {
                        $rowData = $this->createAttributeValues(
                            $productTypeModel,
                            $rowData
                        );
                    }
                }
                $rowData = $this->checkUrl($rowData);
                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addLogWriteln(__('sku: %1 is not valided', $rowData[self::COL_SKU]), $this->output, 'info');
                    $this->notValidedSku[] = strtolower($rowData[self::COL_SKU]);
                    unset($nextBunch[$rowNum]);
                    continue;
                } else {
                    $rowData = $this->prepareRowForDb($rowData);
                }
                if (!isset($rowData[self::COL_ATTR_SET]) || !isset($this->_attrSetNameToId[$rowData[self::COL_ATTR_SET]])) {
                    $this->addRowError(ValidatorInterface::ERROR_INVALID_ATTR_SET, $rowNum);
                    $this->addLogWriteln(__('sku: %1 is not valided. Invalid value for Attribute Set column (set doesn\'t exist?)',
                        $rowData[self::COL_SKU]), $this->output, 'info');
                    $this->notValidedSku[] = strtolower($rowData[self::COL_SKU]);
                    unset($nextBunch[$rowNum]);
                    continue;
                }
                $validUrl = $this->checkUrlKeyDuplicates();
                $this->getOptionEntity()->validateAmbiguousData();

                if ($validUrl && $this->_parameters['generate_url']) {
                    $rowData[self::URL_KEY] = $this->generateUrl($rowData, 0);
                    $nextBunch[$rowNum][self::URL_KEY] = $rowData[self::URL_KEY];
                }

                if (!isset($rowData[self::URL_KEY])) {
                    $rowData[self::URL_KEY] = $this->getUrlKey($rowData);
                }

                $this->urlKeys = [];
                if (isset($rowData[self::COL_CATEGORY]) && $rowData[self::COL_CATEGORY]) {
                    $rowData[self::COL_CATEGORY] = str_replace(
                        $this->_parameters['category_levels_separator'],
                        "/",
                        str_replace(
                            $this->_parameters['categories_separator'],
                            ",",
                            $rowData[self::COL_CATEGORY]
                        )
                    );
                }

                $rowData = $this->_parseAdditionalAttributes($rowData);

                if (empty($rowData[self::COL_SKU])) {
                    $rowData = array_merge($prevData, $this->deleteEmpty($rowData));
                } else {
                    $prevData = $rowData;
                }
                $time = explode(" ", microtime());
                $startTime = $time[0] + $time[1];

                $sku = $rowData['sku'];
                if ($this->onlyUpdate) {
                    $collectionUpdate = $this->collectionFactory->create()->addFieldToFilter(
                        self::COL_SKU,
                        $rowData[self::COL_SKU]
                    );
                    if (!$collectionUpdate->getSize()) {
                        $this->addLogWriteln(__('sku: %1 does not exist', $sku), $this->output, 'info');
                        unset($nextBunch[$rowNum]);
                        continue;
                    }
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    unset($nextBunch[$rowNum]);
                    $this->notValidedSku[] = strtolower($rowData[self::COL_SKU]);

                    continue;
                }

                if (isset($rowData['_attribute_set']) && isset($this->_attrSetNameToId[$rowData['_attribute_set']])) {
                    $this->skuProcessor->setNewSkuData(
                        $rowData[self::COL_SKU],
                        'attr_set_id',
                        $this->_attrSetNameToId[$rowData['_attribute_set']]
                    );
                }

                if ($confSwitch && $rowData['product_type'] == 'simple') {
                    $field = $this->_parameters['configurable_field'];
                    $skuConf = null;
                    if (isset($rowData[$field])) {
                        switch ($this->_parameters['configurable_type']) {
                            case TypeOptions::FIELD:
                                if ($rowData[$field] && $rowData[self::COL_SKU] != $rowData[$field]) {
                                    $skuConf = $rowData[$field];
                                }
                                break;
                            case TypeOptions::PART_UP:
                                $array = explode($this->_parameters['configurable_part'], $rowData[$field]);
                                if (count($array) > 1) {
                                    $skuConf = $array[0];
                                }
                                break;
                            case TypeOptions::PART_DOWN:
                                $array = explode($this->_parameters['configurable_part'], $rowData[$field]);
                                if (count($array) > 1) {
                                    $skuConf = $array[count($array) - 1];
                                }
                                break;
                            case TypeOptions::SUB_UP:
                                $skuConf = substr($rowData[$field], 0, $this->_parameters['configurable_symbols']);
                                break;
                            case TypeOptions::SUB_DOWN:
                                $skuConf = substr($rowData[$field], -$this->_parameters['configurable_symbols']);
                                break;
                        }
                    }
                    if ($skuConf) {
                        $newData = $rowData;
                        $arrayConf = [];
                        $arrayConf['sku'] = $newData['sku'];
                        if (!empty($this->_parameters['configurable_variations'])) {
                            foreach ($this->_parameters['configurable_variations'] as $attrField) {
                                if (isset($newData[$attrField])) {
                                    $arrayConf[$attrField] = $newData[$attrField];
                                }
                            }
                        }
                        $configurableData[$skuConf][] = $arrayConf;
                    }
                }


                $rowScope = $this->getRowScope($rowData);
                $rowSku = $rowData[self::COL_SKU];
                $checkSku = $rowSku;

                if (strpos($this->productMetadata->getVersion(), '2.2') !== false) {
                    $checkSku = strtolower($rowSku);
                }
                if (!$rowSku) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                } elseif (self::SCOPE_STORE == $rowScope) {
                    // set necessary data from SCOPE_DEFAULT row
                    $rowData[self::COL_TYPE] = $this->skuProcessor->getNewSku($checkSku)['type_id'];
                    $rowData['attribute_set_id'] = $this->skuProcessor->getNewSku($checkSku)['attr_set_id'];
                    $rowData[self::COL_ATTR_SET] = $this->skuProcessor->getNewSku($checkSku)['attr_set_code'];
                }
                // Entity phase

                if (!isset($this->_oldSku[$checkSku])) {
                    // new row
                    if (!$productLimit || $productsQty < $productLimit) {
                        if (isset($rowData['has_options'])) {
                            $hasOptions = $rowData['has_options'];
                        } else {
                            $hasOptions = 0;
                        }
                        $entityRowsIn[$rowSku] = [
                            'attribute_set_id' => $this->skuProcessor->getNewSku($checkSku)['attr_set_id'],
                            'type_id' => $this->skuProcessor->getNewSku($checkSku)['type_id'],
                            'sku' => $rowSku,
                            'has_options' => $hasOptions,
                            'created_at' => $this->_localeDate->date()->format(DateTime::DATETIME_PHP_FORMAT),
                            'updated_at' => $this->_localeDate->date()->format(DateTime::DATETIME_PHP_FORMAT),
                        ];
                        $productsQty++;
                    } else {
                        $rowSku = null;
                        // sign for child rows to be skipped
                        $this->getErrorAggregator()->addRowToSkip($rowNum);
                        continue;
                    }
                } else {
                    $array = [
                        'updated_at' => $this->_localeDate->date()->format(DateTime::DATETIME_PHP_FORMAT),
                        'entity_id' => $this->_oldSku[$checkSku]['entity_id'],
                    ];
                    $array['attribute_set_id'] = $this->skuProcessor->getNewSku($checkSku)['attr_set_id'];
                    // existing row
                    $entityRowsUp[] = $array;
                }


                // Categories phase
                if (!array_key_exists($rowSku, $this->categoriesCache)) {
                    $this->categoriesCache[$rowSku] = [];
                }

                $rowData['rowNum'] = $rowNum;
                $categoryIds = $this->getCategories($rowData);
                if (!empty($this->getCategoryProcessor()->getFailedCategories())) {
                    foreach ($this->getCategoryProcessor()->getFailedCategories() as $field) {
                        $this->addRowError('Category: ' . __($field['category']->getName() .
                                ' Url: ' . $field['category']->getUrlKey() .
                                ' ' . $field['exception']->getMessage()), $rowNum);
                    }
                    $this->addLogWriteln(__('sku: %1 is not valided', $rowData[self::COL_SKU]), $this->output, 'info');
                    $this->notValidedSku[] = strtolower($rowData[self::COL_SKU]);
                    unset($nextBunch[$rowNum]);
                    continue;
                }

                foreach ($categoryIds as $id) {
                    $this->categoriesCache[$rowSku][$id] = true;
                }

                unset($rowData['rowNum']);
                if (!array_key_exists($rowSku, $this->websitesCache)) {
                    $this->websitesCache[$rowSku] = [];
                }

                // Product-to-Website phase
                if (!empty($rowData[self::COL_PRODUCT_WEBSITES])) {
                    $websiteCodes = explode($this->getMultipleValueSeparator(), $rowData[self::COL_PRODUCT_WEBSITES]);
                    foreach ($websiteCodes as $websiteCode) {
                        $websiteId = $this->storeResolver->getWebsiteCodeToId($websiteCode);
                        $this->websitesCache[$rowSku][$websiteId] = true;
                    }
                }

                // Price rules
                $rowData = $this->applyPriceRules($rowData);

                // Tier prices phase
                if (!empty($rowData['_tier_price_website'])) {
                    $tierPrices[$rowSku][] = [
                        'all_groups' => $rowData['_tier_price_customer_group'] == self::VALUE_ALL,
                        'customer_group_id' => $rowData['_tier_price_customer_group'] ==
                        self::VALUE_ALL ? 0 : $rowData['_tier_price_customer_group'],
                        'qty' => $rowData['_tier_price_qty'],
                        'value' => $rowData['_tier_price_price'],
                        'website_id' => self::VALUE_ALL == $rowData['_tier_price_website'] || $isPriceGlobal
                            ? 0
                            : $this->storeResolver->getWebsiteCodeToId(
                                $rowData['_tier_price_website']
                            ),
                    ];
                    $tierPrices = array_merge($tierPrices, $this->getTierPrices($rowData, $rowSku));
                } else {
                    $tierPrices += $this->getTierPrices($rowData, $rowSku);
                }
                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addLogWriteln(__('sku: %1 is not valided', $sku), $this->output, 'info');
                    unset($nextBunch[$rowNum]);
                    continue;
                }

                // Media gallery phase
                $disabledImages = [];

                if (isset($rowData['image'])) {
                    if (!isset($rowData['thumbnail'])) {
                        $rowData['thumbnail'] = $rowData['image'];
                    }
                    if (!isset($rowData['small_image'])) {
                        $rowData['small_image'] = $rowData['image'];
                    }
                }

                list($rowImages, $rowLabels) = $this->getImagesFromRow($rowData);
                if (isset($rowData['_media_is_disabled'])) {
                    $disabledImages = array_flip(
                        explode($this->getMultipleValueSeparator(), $rowData['_media_is_disabled'])
                    );
                }
                $rowData[self::COL_MEDIA_IMAGE] = [];
                foreach ($rowImages as $column => $columnImages) {
                    foreach ($columnImages as $position => $columnImage) {
                        if (isset($uploadedImages[$columnImage])) {
                            $uploadedFile = $uploadedImages[$columnImage];
                        } else {
                            $uploadedFile = $this->uploadMediaFiles(trim($columnImage), true);

                            if ($uploadedFile) {
                                $uploadedImages[$columnImage] = $uploadedFile;
                            } else {
                                $this->addRowError(
                                    ValidatorInterface::ERROR_MEDIA_URL_NOT_ACCESSIBLE,
                                    $rowNum,
                                    null,
                                    null,
                                    ProcessingError::ERROR_LEVEL_WARNING
                                );
                            }
                        }

                        if ($uploadedFile && $column !== self::COL_MEDIA_IMAGE) {
                            $rowData[$column] = $uploadedFile;
                        }

                        $imageNotAssigned = !isset($existingImages[$rowSku][$uploadedFile]);

                        if ($uploadedFile && $imageNotAssigned) {
                            if ($column == self::COL_MEDIA_IMAGE) {
                                $rowData[$column][] = $uploadedFile;
                            }
                            $mediaGallery[$rowSku][] = [
                                'attribute_id' => $this->getMediaGalleryAttributeId(),
                                'label' => isset($rowLabels[$column][$position]) ? $rowLabels[$column][$position] : '',
                                'position' => $position + 1,
                                'disabled' => isset($disabledImages[$columnImage]) ? '1' : '0',
                                'value' => $uploadedFile,
                            ];
                            $existingImages[$rowSku][$uploadedFile] = true;
                        }
                    }
                }
                $rowStore = (self::SCOPE_STORE == $rowScope)
                    ? $this->storeResolver->getStoreCodeToId($rowData[self::COL_STORE])
                    : 0;
                //  $productType = isset($rowData[self::COL_TYPE]) ? $rowData[self::COL_TYPE] : null;
                if (!$productType === null) {
                    $previousType = $productType;
                }
                $prevAttributeSet = null;
                if (isset($rowData[self::COL_ATTR_SET])) {
                    $prevAttributeSet = $rowData[self::COL_ATTR_SET];
                }
                if (self::SCOPE_NULL == $rowScope) {
                    // for multiselect attributes only
                    if (!$prevAttributeSet === null) {
                        $rowData[self::COL_ATTR_SET] = $prevAttributeSet;
                    }
                    if ($productType === null && !$previousType === null) {
                        $productType = $previousType;
                    }
                    if ($productType === null) {
                        continue;
                    }
                }
                if (!$productType) {
                    $tempProduct = $this->skuProcessor->getNewSku($checkSku);
                    if (isset($tempProduct['type_id'])) {
                        $productType = $tempProduct['type_id'];
                    }
                }
                if ($productType) {
                    $productTypeModel = $this->_productTypeModels[$productType];

                    if (!empty($rowData['tax_class_name'])) {
                        $collectionTax = $this->collectionTaxFactory->create();
                        $collectionTax->addFieldToFilter('class_type', ClassModel::TAX_CLASS_TYPE_PRODUCT);
                        foreach ($collectionTax as $taxClass) {
                            if (strtolower($rowData['tax_class_name']) == strtolower($taxClass->getClassName())) {
                                $rowData['tax_class_name'] = $taxClass->getClassName();
                            }
                        }
                        $rowData['tax_class_id'] =
                            $this->taxClassProcessor->upsertTaxClass($rowData['tax_class_name'], $productTypeModel);
                    }

                    if ($this->getBehavior() == Import::BEHAVIOR_APPEND || empty($rowData[self::COL_SKU])) {
                        $rowData = $productTypeModel->clearEmptyData($rowData);
                    }

                    $rowData = $productTypeModel->prepareAttributesWithDefaultValueForSave(
                        $rowData,
                        !isset($this->_oldSku[$checkSku])
                    );

                    foreach ($rowData as $attrCode => $attrValue) {
                        $attribute = $this->retrieveAttributeByCode($attrCode);
                        $tempStore = $rowStore;
                        if ('multiselect' != $attribute->getFrontendInput() && self::SCOPE_NULL == $rowScope) {
                            // skip attribute processing for SCOPE_NULL rows
                            continue;
                        }
                        $attrId = $attribute->getId();

                        $backModel = $attribute->getBackendModel();
                        $attrTable = $attribute->getBackend()->getTable();
                        $storeIds = [0];

                        if ('datetime' == $attribute->getBackendType() && strtotime($attrValue)) {
                            $attrValue = $this->dateTime->gmDate(
                                'Y-m-d H:i:s',
                                $this->_localeDate->date($attrValue)->getTimestamp()
                            );
                        } /*elseif ($backModel) {
                            $attribute->getBackend()->beforeSave($product);
                            $attrValue = $product->getData($attribute->getAttributeCode());
                        }*/
                        if (self::SCOPE_STORE == $rowScope) {
                            if (self::SCOPE_WEBSITE == $attribute->getIsGlobal()) {
                                // check website defaults already set
                                if (!isset($attributes[$attrTable][$rowSku][$attrId][$tempStore])) {
                                    $storeIds = $this->storeResolver->getStoreIdToWebsiteStoreIds($tempStore);
                                }
                            } elseif (self::SCOPE_STORE == $attribute->getIsGlobal()) {
                                $storeIds = [$tempStore];
                            }
                            error_log(json_encode($this->_oldSku));
                            if (!isset($this->_oldSku[$checkSku])) {
                                $storeIds[] = 0;
                            }
                        }
                        foreach ($storeIds as $storeId) {
                            if (!isset($attributes[$attrTable][$rowSku][$attrId][$storeId])) {
                                $attributes[$attrTable][$rowSku][$attrId][$storeId] = $attrValue;
                            }
                        }
                        // restore 'backend_model' to avoid 'default' setting
                        $attribute->setBackendModel($backModel);
                    }

                    $time = explode(" ", microtime());
                    $endTime = $time[0] + $time[1];
                    $totalTime = $endTime - $startTime;
                    $totalTime = round($totalTime, 5);
                    $this->addLogWriteln(__('sku: %1 .... %2s', $sku, $totalTime), $this->output, 'info');
                }
            }

            if (method_exists($this, '_saveProductEntity')) {
                $this->_saveProductEntity(
                    $entityRowsIn,
                    $entityRowsUp
                );
            } else {
                $this->saveProductEntity(
                    $entityRowsIn,
                    $entityRowsUp
                );
            }
            $this->addLogWriteln(__('Imported: %1 rows', count($entityRowsIn)), $this->output, 'info');
            $this->addLogWriteln(__('Updated: %1 rows', count($entityRowsUp)), $this->output, 'info');

            $this->_saveProductWebsites(
                $this->websitesCache
            )->_saveProductCategories(
                $this->categoriesCache
            )->_saveProductTierPrices(
                $tierPrices
            )->_saveMediaGallery(
                $mediaGallery
            )->_saveProductAttributes(
                $attributes
            );

            $this->_eventManager->dispatch(
                'catalog_product_import_bunch_save_after',
                ['adapter' => $this, 'bunch' => $nextBunch]
            );
        }
        if (!empty($configurableData)) {
            $this->saveConfigurationVariations($configurableData);
        }

        return $this;
    }

    /**
     * @param array $data
     * @param string $rowSku
     * @return void
     */
    protected function getTierPrices($data, $rowSku)
    {
        $tierPrices = [];

        if (!empty($data['tier_prices'])) {
            $tiers = explode("|", $data['tier_prices']);
            $groups = $this->groupFactory->create()->getCollection()->toOptionArray();
            $newGroups = [];
            foreach ($groups as $group) {
                $newGroups[$group['label']] = $group['value'];
            }
            $websites = $this->websiteFactory->create()->getCollection()->toOptionArray();
            $newWebsites = [0 => self::VALUE_ALL];
            foreach ($websites as $website) {
                $newWebsites[$website['label']] = $website['value'];
            }
            foreach ($tiers as $field) {
                $elements = explode($this->getMultipleValueSeparator(), $field);
                $isAllGroup = 0;
                if ($elements[0] == __('ALL GROUPS')) {
                    $isAllGroup = 1;
                }
                if (strpos($this->productMetadata->getVersion(), '2.2') !== false) {
                    $tierPrices[$rowSku][] = [
                        'all_groups' => $isAllGroup,
                        'customer_group_id' => (isset($elements[0]) && isset($newGroups[$elements[0]])) ? $newGroups[$elements[0]] : 0,
                        'qty' => (isset($elements[1])) ? $elements[1] : 0,
                        'value' => (isset($elements[2])) ? $elements[2] : 0,
                        'percentage_value' => (isset($elements[3])) ? $elements[3] : null,
                        'website_id' => (isset($elements[4]) && isset($newWebsites[$elements[4]])) ? $newWebsites[$elements[4]] : 0,
                    ];
                } else {
                    $tierPrices[$rowSku][] = [
                        'all_groups' => $isAllGroup,
                        'customer_group_id' => (isset($elements[0]) && isset($newGroups[$elements[0]])) ? $newGroups[$elements[0]] : 0,
                        'qty' => (isset($elements[1])) ? $elements[1] : 0,
                        'value' => (isset($elements[2])) ? $elements[2] : 0,
                        'website_id' => (isset($elements[3]) && isset($newWebsites[$elements[3]])) ? $newWebsites[$elements[3]] : 0,
                    ];
                }
            }
        }

        return $tierPrices;
    }


    protected function categoriesMapping($importedCategories)
    {
        $explodeImportedCategoriesItems = explode(',', $importedCategories);
        $importCollectionItems = $this->importCollection->addFieldToFilter(
            'entity_id',
            $this->_parameters['job_id']
        )
            ->getItems();
        foreach ($importCollectionItems as $importCollectionItem) {
            $newCategoriesMapItems = \Zend\Serializer\Serializer::unserialize($importCollectionItem->getMapping());
            foreach ($newCategoriesMapItems as $newCategoriesMapItem) {
                foreach ($explodeImportedCategoriesItems as &$explodeImportedCategoriesItem) {
                    if ($explodeImportedCategoriesItem == $newCategoriesMapItem['source_category_data_import']) {
                        $explodeImportedCategoriesItem = $newCategoriesMapItem['source_category_data_new'];
                    }
                }
            }
        }
        return implode(',', $explodeImportedCategoriesItems);
    }

    /**
     * @param $data
     * @return $this
     */
    protected function saveConfigurationVariations($data)
    {
        if (!empty($data)) {
            foreach ($data as $skuConf => $elements) {
                if (strpos($this->productMetadata->getVersion(), '2.2') !== false) {
                    $checkSku = strtolower($skuConf);
                }
                $created = 0;
                $websites = [];
                $additionalRows = [];
                $changeAttributes = [];
                $storeIds = [0];
                $mediaGallery = [];
                foreach ($this->storeManager->getStores() as $key => $model) {
                    $storeIds[] = $key;
                }
                try {
                    $collection = $this->collectionFactory->create()
                        ->addFieldToFilter('sku', $skuConf)
                        ->addFieldToFilter('type_id', 'configurable')
                        ->addAttributeToSelect('*');
                    $this->addLogWriteln(__('Configure variations for SKU:%1', $skuConf), $this->output, 'info');
                    if ($this->_parameters['configurable_create'] && !$collection->getSize()) {
                        try {
                            $created = 1;
                            $collectionChild = $this->collectionFactory->create();
                            $collectionChild->addFieldToFilter('sku', $elements[0][self::COL_SKU])
                                ->addAttributeToSelect('*');
                            $child = $collectionChild->getFirstItem();
                            $data = [];
                            $data[self::COL_SKU] = $skuConf;
                            $data[self::COL_NAME] = $skuConf;
                            foreach ($this->_imagesArrayKeys as $fieldImage) {
                                if ($fieldImage != '_media_image') {
                                    $data[$fieldImage] = $child->getData($fieldImage);
                                    $attributeChange = $this->retrieveAttributeByCode($fieldImage);
                                    $attrId = $attributeChange->getId();
                                    $attrTable = $attributeChange->getBackend()->getTable();
                                    $attrValue = $child->getData($fieldImage);
                                    if (!isset($changeAttributes[$attrTable][$checkSku][$attrId][0]) && !empty($attrValue)) {
                                        $changeAttributes[$attrTable][$skuConf][$attrId][0] = $attrValue;
                                        $mediaGallery[$skuConf][] = [
                                            'attribute_id' => $this->getMediaGalleryAttributeId(),
                                            'label' => '',
                                            'position' => 1,
                                            'disabled' => '0',
                                            'value' => $attrValue,
                                        ];
                                    }
                                }
                            }
                            $data['attribute_set_id'] = $child->getAttributeSetId();
                            $data['type_id'] = 'configurable';
                            $data['website_ids'] = $child->getWebsiteIds();
                            $websites[$skuConf] = $child->getWebsiteIds();
                            $data['category_ids'] = $child->getCategoryIds();
                            $data['visibility'] = 4;
                            $data['has_options'] = $child->getData('has_options');
                            $changedData = $data;
                            $product = $this->_proxyProdFactory->create();
                            $product->setData($data);

                            $product = $this->productRepository->save($product);
                            $data['entity_id'] = $product->getId();
                            $this->skuProcessor->addNewSku($skuConf, $data);
                            $this->_oldSku[strtolower($skuConf)] = [
                                'type_id' => "configurable",
                                'attr_set_id' => $child->getAttributeSetId(),
                                'entity_id' => $product->getId(),
                                'supported_type' => true
                            ];
                        } catch (LocalizedException $e) {
                            $this->addLogWriteln($e->getMessage(), $this->output, 'error');
                        }
                    } else {
                        if ($collection->getSize()) {
                            $product = $collection->getFirstItem();
                            if ($product->getName() == $skuConf) {
                                $collectionChild = $this->collectionFactory->create();
                                $collectionChild->addFieldToFilter('sku', $elements[0][self::COL_SKU])
                                    ->addAttributeToSelect('*');
                                $product->setName($skuConf);
                            }
                            if ($product->getTypeId() != 'configurable') {
                                $product->setTypeId('configurable');
                                $product = $this->productRepository->save($product);
                            }

                        }
                    }

                    $vars = [];
                    $attributes = [];
                    $attributeChange = $this->retrieveAttributeByCode('visibility');
                    $attrTable = $attributeChange->getBackend()->getTable();

                    $attrValue = 1;

                    $attrId = $attributeChange->getId();
                    foreach ($elements as $element) {
                        $position = 0;
                        foreach ($element as $ki => $field) {
                            if ($ki != 'sku' && !empty($field)) {
                                if (!in_array($ki, $attributes)) {
                                    $attributes[] = $ki;
                                }
                                $vars['fields'][] = [
                                    'code' => $ki,
                                    'value' => $field
                                ];
                            } else {
                                $vars[$ki] = $field;
                                if ($ki == 'sku') {
                                    foreach ($storeIds as $storeId) {
                                        if (!isset($changeAttributes[$attrTable][$field][$attrId][$storeId])) {
                                            $changeAttributes[$attrTable][$field][$attrId][$storeId] = $attrValue;
                                        }
                                    }
                                }
                            }
                        }
                        $vars['position'] = $position;
                        $position++;
                        $additionalRows[] = $vars;
                    }
                    $attributeValues = [];
                    $ids = [];
                    $configurableAttributesData = [];
                    $position = 0;
                    foreach ($attributes as $attribute) {
                        foreach ($additionalRows as $list) {
                            $attributeCollection = $this->attributeFactory->create()->getCollection();
                            $attributeCollection->addFieldToFilter('attribute_code', $attribute);
                            $value = [];
                            foreach ($list['fields'] as $item) {
                                if ($item['code'] == $attribute) {
                                    $value = $item['value'];
                                    $collection = $this->collectionFactory->create();
                                    $collection->addFieldToFilter('sku', $list['sku']);
                                    if (!in_array($collection->getFirstItem()->getId(), $ids)) {
                                        $ids[] = $collection->getFirstItem()->getId();
                                    }
                                }
                            }
                            if ($attributeCollection->getSize()) {
                                $attributeValues[$attribute][] = [
                                    'label' => $attribute,
                                    'attribute_id' => $attributeCollection->getFirstItem()->getId(),
                                    'value_index' => $value,
                                ];
                            }
                        }
                        if ($attributeCollection->getSize()) {
                            $attr = $attributeCollection->getFirstItem();
                            $configurableAttributesData[] =
                                [
                                    'attribute_id' => $attr->getId(),
                                    'code' => $attr->getAttributeCode(),
                                    'label' => $attr->getStoreLabel(),
                                    'position' => $position++,
                                    'values' => $attributeValues[$attribute],
                                ];
                        }
                    }
                    if ($created) {
                        if (!empty($websites)) {
                            $this->saveProductWebsitesConf($product->getId(), $websites);
                        }

                        if (!empty($mediaGallery)) {
                            $this->_saveMediaGallery($mediaGallery);
                        }
                        if (!empty($changeAttributes)) {
                            $this->_saveProductAttributes($changeAttributes);
                        }
                    }
                    $this->saveCollectData($product->getId(), $configurableAttributesData, $ids);

                } catch (\Exception $e) {
                    $this->addLogWriteln($e->getMessage(), $this->output, 'error');
                }
            }
        }

        return $this;
    }

    public function saveCollectData($productId, $configurableAttributesData, $ids)
    {

        $connection = $this->_connection;
        $resource = $this->_resourceFactory->create();
        $table = $resource->getTable('catalog_product_super_attribute');
        $labelTable = $resource->getTable('catalog_product_super_attribute_label');
        $linkTable = $resource->getTable('catalog_product_super_link');
        $relationTable = $resource->getTable('catalog_product_relation');
        // $connection->delete($table, ['product_id=' . $productId]);
        // $connection->delete($linkTable, ['parent_id=' . $productId]);
        // $connection->delete($relationTable, ['parent_id=' . $productId]);
        $select = $connection->select()->from(
            ['m' => $table],
            ['product_id', 'attribute_id', 'product_super_attribute_id']
        )->where(
            'm.product_id IN ( ? )',
            [$productId]
        );
        $counts = count($connection->fetchAll($select));
        if (!$counts) {
            foreach ($configurableAttributesData as $elem) {
                $data = [
                    'product_id' => $productId,
                    'attribute_id' => $elem['attribute_id'],
                    'position' => $elem['position']
                ];
                $connection->insert($table, $data);
            }


            foreach ($connection->fetchAll($select) as $row) {
                $attrId = $row['attribute_id'];
                $superId = $row['product_super_attribute_id'];
                foreach ($configurableAttributesData as $elem) {
                    if ($elem['attribute_id'] == $attrId) {
                        $data = ['product_super_attribute_id' => $superId, 'value' => $elem['label']];
                        $connection->insert($labelTable, $data);
                    }
                }
            }
        }

        foreach ($ids as $id) {
            $data = ['product_id' => $id, 'parent_id' => $productId];
            $connection->insert($linkTable, $data);
            $relData = ['child_id' => $id, 'parent_id' => $productId];
            $connection->insert($relationTable, $relData);
        }
    }

    protected function generateUrl($rowData, $number)
    {
        $newUrl = '';
        $this->urlKeys = [];
        if ($number === 0) {
            $number = '';
        }
        $newUrl = $this->productUrl->formatUrlKey(
            $rowData[self::COL_NAME] . '-' . $rowData[self::COL_SKU] . "-" . $number
        );
        $urlKey = strtolower($newUrl);
        $sku = $rowData[self::COL_SKU];
        $storeCodes = empty($rowData[self::COL_STORE_VIEW_CODE])
            ? array_flip($this->storeResolver->getStoreCodeToId())
            : explode($this->getMultipleValueSeparator(), $rowData[self::COL_STORE_VIEW_CODE]);
        foreach ($storeCodes as $storeCode) {
            $storeId = $this->storeResolver->getStoreCodeToId($storeCode);
            $productUrlSuffix = $this->getProductUrlSuffix($storeId);
            $urlPath = $urlKey;
            if (empty($this->urlKeys[$storeId][$urlPath])
                || ($this->urlKeys[$storeId][$urlPath] == $sku)
            ) {
                $this->urlKeys[$storeId][$urlPath] = $sku;
            }
        }

        $validUrl = $this->checkUrlKeyDuplicates();
        if ($validUrl && $this->_parameters['generate_url']) {
            return $this->generateUrl($rowData, $number + 1);
        } else {
            return $newUrl;
        }
    }

    protected function saveProductWebsitesConf($productId, array $websiteData)
    {
        static $tableName = null;

        if (!$tableName) {
            $tableName = $this->_resourceFactory->create()->getProductWebsiteTable();
        }
        if ($websiteData) {
            $websitesData = [];
            $delProductId = [];

            foreach ($websiteData as $delSku => $websites) {
                $delProductId[] = $productId;
                foreach ($websites as $websiteId) {
                    $websitesData[] = ['product_id' => $productId, 'website_id' => $websiteId];
                }
            }
            $this->_connection->delete(
                $tableName,
                $this->_connection->quoteInto('product_id IN (?)', $delProductId)
            );
            if ($websitesData) {
                $this->_connection->insertOnDuplicate($tableName, $websitesData);
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    protected function _saveValidatedBunches()
    {
        $source = $this->_getSource();
        $currentDataSize = 0;
        $bunchRows = [];
        $prevData = [];
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
                $this->_dataSourceModel->saveBunches(
                    $this->getEntityTypeCode(),
                    $this->getBehavior(),
                    $jobId,
                    $file,
                    $bunchRows
                );
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

                $rowData = $this->customFieldsMapping($rowData);
                if (empty($rowData[self::COL_SKU])) {
                    $rowData = array_merge($prevData, $this->deleteEmpty($rowData));

                } else {
                    $prevData = $rowData;
                }
                $this->_processedRowsCount++;

                if ($this->onlyUpdate) {
                    $collectionUpdate = $this->collectionFactory->create()->addFieldToFilter(
                        self::COL_SKU,
                        $rowData[self::COL_SKU]
                    );
                    if (!$collectionUpdate->getSize()) {
                        $source->next();
                        continue;
                    }
                }

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

    public function isExist($sku)
    {
        if ($this->onlyUpdate) {
            $collectionUpdate = $this->collectionFactory->create()->addFieldToFilter(
                self::COL_SKU,
                $sku
            );
            if (!$collectionUpdate->getSize()) {
                return true;
            }
        }

        return false;
    }

    /*
    protected function mergeData($rowData, $prevData)
    {

        $data = $this->deleteEmpty($rowData);

        foreach ($data as $key => $value) {
            $prevData[$key] .= $this->getMultipleValueSeparator() . $value;
        }

        return $prevData;
    }
    */

    /**
     * Import images via initialized source type
     *
     * @param $bunch
     *
     * @return mixed
     */
    protected function prepareImagesFromSource($bunch)
    {
        foreach ($bunch as $rowNum => &$rowData) {
            $rowData = $this->customFieldsMapping($rowData);
            foreach ($this->_imagesArrayKeys as $image) {
                /*   if (!$this->validateRow($rowData, $rowNum)) {
                          continue;
                   } else {
                       $rowData = $this->prepareRowForDb($rowData);
                   }*/
                if (empty($rowData[$image])) {
                    continue;
                }
                $dispersionPath =
                    \Magento\Framework\File\Uploader::getDispretionPath($rowData[$image]);
                $importImages = explode($this->getMultipleValueSeparator(), $rowData[$image]);
                $imageArr = [];
                foreach ($importImages as $importImage) {
                    $imageSting = mb_strtolower(
                        $dispersionPath . '/' . preg_replace('/[^a-z0-9\._-]+/i', '', $importImage)
                    );
                    if ($this->sourceType) {
                        $this->sourceType->importImage($importImage, $imageSting);
                    }
                    $imageArr[] = $this->sourceType->getCode() . $imageSting;
                }
                $rowData[$image] = implode($this->getMultipleValueSeparator(), $imageArr);
            }
        }

        return $bunch;
    }

    /**
     * Retrieving images from all columns and rows
     *
     * @param $bunch
     *
     * @return array
     */
    protected function getBunchImages(
        $bunch
    ) {
        $allImagesFromBunch = [];
        foreach ($bunch as $rowData) {
            $rowData = $this->customFieldsMapping($rowData);
            foreach ($this->_imagesArrayKeys as $image) {
                if (empty($rowData[$image])) {
                    continue;
                }
                $dispersionPath =
                    \Magento\Framework\File\Uploader::getDispretionPath($rowData[$image]);
                $importImages = explode($this->getMultipleValueSeparator(), $rowData[$image]);
                foreach ($importImages as $importImage) {
                    $imageSting = mb_strtolower(
                        $dispersionPath . '/' . preg_replace('/[^a-z0-9\._-]+/i', '', $importImage)
                    );
                    /**
                     * TODO: check source type 'file'.
                     * Compare code with default Magento\CatalogImportExport\Model\Import\Product
                     */
                    if (isset($this->_parameters['import_source']) && $this->_parameters['import_source'] != 'file') {
                        $allImagesFromBunch[$this->sourceType->getCode() . $imageSting] = $imageSting;
                    } else {
                        $allImagesFromBunch[$importImage] = $imageSting;
                    }
                }
            }
        }

        return $allImagesFromBunch;
    }

    /**
     * Convert attribute string syntax to array.
     *
     * @param $columnData
     *
     * @return array
     * @throws \Exception
     */
    protected function prepareAttributeData($columnData)
    {
        $result = [];
        foreach ($columnData as $field) {
            $field = explode(':', $field);
            if (isset($field[1])) {
                if (preg_match('/^(frontend_label_)[0-9]+/', $field[0])) {
                    $result['frontend_label'][(int)substr($field[0], -1)] = $field[1];
                } else {
                    $result[$field[0]] = $field[1];
                }
            }
        }

        if (!empty($result)) {
            $attributeCode = isset($result['attribute_code']) ? $result['attribute_code'] : null;
            $frontendLabel = $result['frontend_label'][0];
            $attributeCode = $attributeCode ?: $this->generateAttributeCode($frontendLabel);
            $result['attribute_code'] = $attributeCode;

            $entityTypeId = $this->eavEntityFactory->create()->setType(
                \Magento\Catalog\Model\Product::ENTITY
            )->getTypeId();
            $result['entity_type_id'] = $entityTypeId;
            $result['is_user_defined'] = 1;
        }

        return $result;
    }

    /**
     * Generate code from label
     *
     * @param string $label
     *
     * @return string
     */
    protected function generateAttributeCode($label)
    {
        $code = substr(
            preg_replace(
                '/[^a-z_0-9]/',
                '_',
                $this->productUrl->formatUrlKey($label)
            ),
            0,
            30
        );
        $validatorAttrCode = new \Zend_Validate_Regex(['pattern' => '/^[a-z][a-z_0-9]{0,29}[a-z0-9]$/']);
        if (!$validatorAttrCode->isValid($code)) {
            $code = 'attr_' . ($code ?: substr(hash("md5", time()), 0, 8));
        }

        return $code;
    }

    /**
     * Custom fields mapping for changed purposes of fields and field names.
     *
     * @param array $rowData
     *
     * @return array
     */
    public function customFieldsMapping($rowData)
    {
        foreach ($this->_fieldsMap as $systemFieldName => $fileFieldName) {
            if (array_key_exists($fileFieldName, $rowData)) {
                $rowData[$systemFieldName] = $rowData[$fileFieldName];
            }
        }

        $rowData = $this->_parseAdditionalAttributes($rowData);
        $rowData = $this->setStockUseConfigFieldsValues($rowData);
        if (array_key_exists('status', $rowData)
            && $rowData['status'] != \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED
        ) {
            if ($rowData['status'] == 'yes') {
                $rowData['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED;
            } elseif (!empty($rowData['status']) || $this->getRowScope($rowData) == self::SCOPE_DEFAULT) {
                $rowData['status'] = \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_DISABLED;
            }
        }

        return $rowData;
    }

    /**
     * Parse attributes names and values string to array.
     *
     * @param array $rowData
     *
     * @return array
     */
    private function _parseAdditionalAttributes($rowData)
    {
        if (empty($rowData['additional_attributes'])) {
            return $rowData;
        }

        $valuePairs = explode(
            $this->getMultipleValueSeparator(),
            $rowData['additional_attributes']
        );
        foreach ($valuePairs as $valuePair) {
            $separatorPosition = strpos($valuePair, self::PAIR_NAME_VALUE_SEPARATOR);
            if ($separatorPosition !== false) {
                $key = substr($valuePair, 0, $separatorPosition);
                $value = substr(
                    $valuePair,
                    $separatorPosition + strlen(self::PAIR_NAME_VALUE_SEPARATOR)
                );
                $rowData[$key] = $value === false ? '' : $value;
            }
        }
        if ($rowData['product_type'] == 'bundle') {
            $fields = ['price_type', 'weight_type', 'sku_type'];
            foreach ($fields as $field) {
                if (isset($rowData[$field])) {
                    if ($rowData[$field] == BundlePrice::PRICE_TYPE_DYNAMIC) {
                        $rowData[$field] = Bundle::VALUE_DYNAMIC;
                    } else {
                        $rowData[$field] = Bundle::VALUE_FIXED;
                    }
                }
            }
        }

        return $rowData;
    }

    /**
     * Set values in use_config_ fields.
     *
     * @param array $rowData
     *
     * @return array
     */
    private function setStockUseConfigFieldsValues($rowData)
    {
        $useConfigFields = [];
        foreach ($rowData as $key => $value) {
            if (isset($this->defaultStockData[$key])
                && isset($this->defaultStockData[self::INVENTORY_USE_CONFIG_PREFIX . $key])
                && !empty($value)
            ) {
                $useConfigFields[self::INVENTORY_USE_CONFIG_PREFIX . $key] =
                    ($value == self::INVENTORY_USE_CONFIG) ? 1 : 0;
            }
        }
        $rowData = array_merge($rowData, $useConfigFields);

        return $rowData;
    }

    /**
     * Load categories map
     *
     * @return mixed
     */
    public function getCategoriesMap($fieldName)
    {
        $bunchRows = [];
        $categories = [];
        $source = $this->_getSource();
        $source->rewind();
        while ($source->valid() || $bunchRows) {
            if ($source->valid()) {
                $rowData = $source->current();
                if (isset($rowData[$fieldName])) {
                    $categories[] = $rowData[$fieldName];
                }

                $source->next();
            }
        }

        return $categories;
    }

    /**
     * Validate data
     *
     * @return ProcessingErrorAggregatorInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateData($saveBunches = 1)
    {
        if ($this->_parameters['behavior'] == Import::FIREBEAR_ONLY_UPDATE) {
            $this->onlyUpdate = 1;
            $this->_parameters['behavior'] = Import::BEHAVIOR_APPEND;
        }

        if (isset($this->_parameters['output'])) {
            $this->output = $this->_parameters['output'];
        }
        $this->_initTypeModels();
        if (!$this->_dataValidated) {
            $this->getErrorAggregator()->clear();
            // do all permanent columns exist?
            $platformModel = null;
            $absentColumns =
                array_diff($this->_permanentAttributes, $this->getSource()->getColNames());
            $this->addErrors(self::ERROR_CODE_COLUMN_NOT_FOUND, $absentColumns);

            // check attribute columns names validity
            $columnNumber = 0;
            $emptyHeaderColumns = [];
            $invalidColumns = [];
            $invalidAttributes = [];
            foreach ($this->getSource()->getColNames() as $columnName) {
                $this->addLogWriteln(__('Checked column %1', $columnNumber), $this->output);
                $columnNumber++;
                if (!$this->isAttributeParticular($columnName)) {
                    /**
                     * Check syntax when attribute should be created on the fly
                     */
                    $createValuesAllowed = (bool)$this->scopeConfig->getValue(
                        \Firebear\ImportExport\Model\Import::CREATE_ATTRIBUTES_CONF_PATH,
                        \Magento\Store\Model\ScopeInterface::SCOPE_STORE
                    );
                    $isNewAttribute = false;

                    if ($createValuesAllowed && preg_match('/^(attribute\|).+/', $columnName)) {
                        $isNewAttribute = true;
                        $columnData = explode('|', $columnName);
                        $columnData = $this->prepareAttributeData($columnData);
                        $attribute = $this->attributeFactory->create();
                        $attribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $columnData['attribute_code']);
                        if (!$attribute->getId()) {
                            $attribute->setBackendType(
                                $attribute->getBackendTypeByInput($columnData['frontend_input'])
                            );
                            $defaultValueField = $attribute->getDefaultValueByInput($columnData['frontend_input']);
                            if (!$defaultValueField && isset($columnData['default_value'])) {
                                unset($columnData['default_value']);
                            }
                            $columnData['source_model'] = $this->productHelper->getAttributeSourceModelByInputType(
                                $columnData['frontend_input']
                            );
                            $columnData['backend_model'] = $this->productHelper->getAttributeBackendModelByInputType(
                                $columnData['frontend_input']
                            );

                            $attribute->addData($columnData);
                            try {
                                $attribute->save();
                            } catch (\Exception $e) {
                                $invalidColumns[] = $columnName;
                            }

                            $attributeSetCodes = explode(',', $columnData[self::ATTRIBUTE_SET_COLUMN]);
                            foreach ($attributeSetCodes as $attributeSetCode) {
                                if (isset($this->_attrSetNameToId[$attributeSetCode])) {
                                    $attributeSetId = $this->_attrSetNameToId[$attributeSetCode];
                                    $attributeGroupCode = isset($columnData[self::ATTRIBUTE_SET_GROUP])
                                        ? $columnData[self::ATTRIBUTE_SET_GROUP] : 'product-details';
                                    if (!isset($this->_attributeSetGroupCache[$attributeSetId])) {
                                        $groupCollection =
                                            $this->groupCollectionFactory->create()->setAttributeSetFilter(
                                                $attributeSetId
                                            )->load();
                                        foreach ($groupCollection as $group) {
                                            $this->_attributeSetGroupCache[$attributeSetId][$group->getAttributeGroupCode()] = $group->getAttributeGroupId();
                                        }
                                    }
                                    foreach ($this->_attributeSetGroupCache[$attributeSetId] as $groupCode => $groupId) {
                                        if ($groupCode == $attributeGroupCode) {
                                            $attribute->setAttributeSetId($attributeSetId);
                                            $attribute->setAttributeGroupId($groupId);
                                            try {
                                                $attribute->save();
                                            } catch (\Exception $e) {
                                                $this->addLogWriteln($e->getMessage(), $this->output, 'error');
                                            }
                                            break;
                                        }
                                    }
                                }
                            }
                        }
                        $this->_initTypeModels();
                    }

                    if (trim($columnName) == '') {
                        $emptyHeaderColumns[] = $columnNumber;
                    } elseif (!preg_match('/^[a-zA-Z.][a-zA-Z0-9_\.]*$/', $columnName) && !$isNewAttribute) {
                        $invalidColumns[] = $columnName;
                    } elseif ($this->needColumnCheck && !in_array($columnName, $this->getValidColumnNames())) {
                        $invalidAttributes[] = $columnName;
                    }
                }
            }

            $this->addErrors(self::ERROR_CODE_INVALID_ATTRIBUTE, $invalidAttributes);
            $this->addErrors(self::ERROR_CODE_COLUMN_EMPTY_HEADER, $emptyHeaderColumns);
            $this->addErrors(self::ERROR_CODE_COLUMN_NAME_INVALID, $invalidColumns);
            $this->addLogWriteln(__('Finish checking columns'), $this->output);
            $this->addLogWriteln(__('Errors count: %1', $this->getErrorAggregator()->getErrorsCount()), $this->output);
            if (!$this->getErrorAggregator()->getErrorsCount()) {
                if ($saveBunches) {
                    $this->addLogWriteln(__('Start saving bunches'), $this->output);
                    $this->mergeFieldsMap();
                    if (!isset($this->_parameters['platforms']) &&  $this->_parameters['platforms'] != 'magento1' ) {
                        if (isset($this->_parameters['platforms'])) {
                            $platformModel = $this->helper->getPlatformModel($this->_parameters['platforms']);
                            $platformModel->saveValidatedBunches(
                                $this->_getSource(),
                                $this->_resourceHelper->getMaxDataSize(),
                                $this->_importExportData->getBunchSize(),
                                $this->_dataSourceModel,
                                $this->_parameters,
                                $this->getEntityTypeCode(),
                                $this->getBehavior(),
                                $this->_processedRowsCount,
                                $this->getMultipleValueSeparator(),
                                $this
                            );
                        }

                    } else {
                        $this->_saveValidatedBunches();
                    }
                    $this->addLogWriteln(__('Finish saving bunches'), $this->output);
                    $this->_dataValidated = true;
                }
            }
        }

        return $this->getErrorAggregator();
    }

    protected function mergeFieldsMap()
    {
        if (isset($this->_parameters['map'])) {
            $newAttributes = [];
            foreach ($this->_parameters['map'] as $field) {
                if (!$field['import']) {
                    $field['import'] = $field['system'];
                }
                $attribute = $this->getResource()->getAttribute($field['system']);
                $attributeCode = '';
                if ($attribute) {
                    $attributeCode = $attribute->getAttributeCode();
                    $newAttributes[$attribute->getAttributeCode()] = $field['import'];
                } else {
                    $attributeCode = $field['system'];
                    $newAttributes[$field['system']] = $field['import'];
                }
            }

            $this->_fieldsMap = array_merge($this->_fieldsMap, $newAttributes);
        }
    }


    /**
     * @return string[]
     */
    public function getSpecialAttributes()
    {
        return $this->_specialAttributes;
    }

    /**
     * @param $productTypeModel
     * @param $rowData
     *
     * @return mixed
     */
    public function createAttributeValues($productTypeModel, $rowData)
    {
        $options = [];
        $attributeSet = $rowData[\Magento\CatalogImportExport\Model\Import\Product::COL_ATTR_SET];
        foreach ($rowData as $attrCode => $attrValue) {
            /**
             * Add attribute to set & set's group
             */
            if (preg_match('/^(attribute\|).+/', $attrCode)) {
                $columnData = explode('|', $attrCode);
                $columnData = $this->prepareAttributeData($columnData);
                $rowData[$columnData['attribute_code']] = $rowData[$attrCode];
                unset($rowData[$attrCode]);
                $attrCode = $columnData['attribute_code'];
            }

            /**
             * Prepare new values
             */
            $attrParams = $productTypeModel->retrieveAttribute($attrCode, $attributeSet);
            if (!empty($attrParams)) {
                if (!$attrParams['is_static'] && isset($rowData[$attrCode]) && !empty($rowData[$attrCode])) {
                    switch ($attrParams['type']) {
                        case 'select':
                            if (!isset($attrParams['options'][strtolower($rowData[$attrCode])])) {
                                $options[$attrParams['id']][] = [
                                    'sort_order' => count($attrParams['options']) + 1,
                                    'value' => $rowData[$attrCode],
                                    'code' => $attrCode
                                ];
                            }
                            break;
                        case 'multiselect':
                            foreach (explode(Product::PSEUDO_MULTI_LINE_SEPARATOR, $rowData[$attrCode]) as $value) {
                                if (!isset($attrParams['options'][strtolower($value)])) {
                                    $options[$attrParams['id']][] = [
                                        'sort_order' => count($attrParams['options']) + 1,
                                        'value' => $value,
                                        'code' => $attrCode
                                    ];
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }

        /**
         * Create new values
         */

        if (!empty($options)) {
            foreach ($options as $attributeId => $optionsArray) {
                foreach ($optionsArray as $option) {
                    /**
                     * @see \Magento\Eav\Model\ResourceModel\Entity\Attribute::_updateAttributeOption()
                     */
                    $connection = $this->_connection;
                    $resource = $this->_resourceFactory->create();
                    $table = $resource->getTable('eav_attribute_option');
                    $data = ['attribute_id' => $attributeId, 'sort_order' => $option['sort_order']];
                    $connection->insert($table, $data);
                    $intOptionId = $connection->lastInsertId($table);
                    /**
                     * @see \Magento\Eav\Model\ResourceModel\Entity\Attribute::_updateAttributeOptionValues()
                     */
                    $table = $resource->getTable('eav_attribute_option_value');
                    $data = ['option_id' => $intOptionId, 'store_id' => 0, 'value' => $option['value']];
                    $connection->insert($table, $data);
                    foreach ($this->_productTypeModels as $productTypeModel) {
                        $productTypeModel->addAttributeOption($option['code'], strtolower($option['value']),
                            $intOptionId);
                    }
                }
            }
        }

        return $rowData;
    }

    /**
     * @return array
     */
    public function getAddFields()
    {
        return $this->addFields;
    }

    /**
     * @return $this
     */
    protected function _initTypeModels()
    {
        $this->_importConfig = $this->fireImportConfig;
        $productTypes = $this->_importConfig->getEntityTypes($this->getEntityTypeCode());
        foreach ($productTypes as $productTypeName => $productTypeConfig) {
            $class = $productTypeConfig['model'];
            $class::$commonAttributesCache = [];
            $class::$attributeCodeToId = [];
        }
        parent::_initTypeModels();

        return $this;
    }

    /**
     * @param $array
     * @return array
     */
    protected function deleteEmpty($array)
    {
        if (isset($array[self::COL_SKU])) {
            unset($array[self::COL_SKU]);
        }
        $newElement = [];
        foreach ($array as $key => $element) {
            if (strlen($element)) {
                $newElement[$key] = $element;
            }
        }

        return $newElement;
    }

    /**
     * @param string $productSku
     * @return array
     */
    public function getProductWebsites($productSku)
    {
        return array_keys($this->websitesCache[$productSku]);
    }

    /**
     * @param string $productSku
     * @return array
     */
    public function getProductCategories($productSku)
    {
        return array_keys($this->categoriesCache[$productSku]);
    }

    /**
     * @return \Magento\CatalogImportExport\Model\Import\Uploader
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getUploader()
    {
        $DS = DIRECTORY_SEPARATOR;
        if (is_null($this->_fileUploader)) {
            $this->_fileUploader = $this->_uploaderFactory->create();
            $this->_fileUploader->init();
            $dirConfig = DirectoryList::getDefaultConfig();
            $dirAddon = $dirConfig[DirectoryList::MEDIA][DirectoryList::PATH];
            if (!empty($this->_parameters[Import::FIELD_NAME_IMG_FILE_DIR])) {
                $tmpPath = $this->_parameters[Import::FIELD_NAME_IMG_FILE_DIR];
            } else {
                $tmpPath = $dirAddon . $DS . $this->_mediaDirectory->getRelativePath('import');
            }
            if (!$this->_fileUploader->setTmpDir($tmpPath)) {
                $this->addLogWriteln(__('File directory \'%1\' is not readable.', $tmpPath), $this->output, 'info');
                $this->addRowError(
                    __('File directory \'%1\' is not readable.', $tmpPath),
                    null,
                    null,
                    null,
                    ProcessingError::ERROR_LEVEL_NOT_CRITICAL
                );
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('File directory \'%1\' is not readable.', $tmpPath)
                );
            }
            $destinationDir = "catalog/product";
            $destinationPath = $dirAddon . $DS . $this->_mediaDirectory->getRelativePath($destinationDir);

            $this->_mediaDirectory->create($destinationPath);
            if (!$this->_fileUploader->setDestDir($destinationPath)) {
                $this->addRowError(
                    __('File directory \'%1\' is not writable.', $destinationPath),
                    null,
                    null,
                    null,
                    ProcessingError::ERROR_LEVEL_NOT_CRITICAL
                );
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('File directory \'%1\' is not writable.', $destinationPath)
                );
            }
        }

        return $this->_fileUploader;
    }

    protected function getCategories($rowData)
    {
        if (isset($rowData[self::COL_STORE])) {
            $this->categoryProcessor->setStoreId($this->storeResolver->getStoreCodeToId($rowData[self::COL_STORE]));
        }
        $this->categoryProcessor->setGeneratUrl($this->_parameters['generate_url']);
        $this->categoryProcessor->setResource($this->getResource());
        $ids = $this->categoryProcessor->getRowCategories($rowData, $this->getMultipleValueSeparator());
        foreach ($this->categoryProcessor->getFailedCategories() as $error) {
            $this->errorAggregator->addError(
                AbstractEntity::ERROR_CODE_CATEGORY_NOT_VALID,
                ProcessingError::ERROR_LEVEL_NOT_CRITICAL,
                $rowData['rowNum'],
                self::COL_CATEGORY,
                __('Category "%1" has not been created.', $error['category'])
                . ' ' . $error['exception']->getMessage()
            );
        }

        return $ids;
    }

    protected function checkUrlKeyDuplicates()
    {
        $status = 0;
        $resource = $this->getResource();
        foreach ($this->urlKeys as $storeId => $urlKeys) {
            $select = $this->_connection->select()->from(
                ['url_rewrite' => $resource->getTable('url_rewrite')],
                ['request_path', 'store_id']
            )->joinLeft(
                ['cpe' => $resource->getTable('catalog_product_entity')],
                "cpe.entity_id = url_rewrite.entity_id"
            )->where('request_path IN (?)', array_keys($urlKeys))
                ->where('store_id IN (?)', $storeId)
                ->where('cpe.sku not in (?)', array_values($urlKeys));

            $urlKeyDuplicates = $this->_connection->fetchAssoc(
                $select
            );
            foreach ($urlKeyDuplicates as $entityData) {
                if (!$this->_parameters['generate_url']) {
                    $rowNum = $this->rowNumbers[$entityData['store_id']][$entityData['request_path']];
                    $this->addRowError(ValidatorInterface::ERROR_DUPLICATE_URL_KEY, $rowNum);
                }
                $status = 1;
            }
        }

        return $status;
    }

    /**
     * @return array
     */
    public function getNotValidSkus()
    {
        return $this->notValidedSku;
    }

    public function setErrorMessages()
    {
        $this->_initErrorTemplates();
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function prepareRowForDb(array $rowData)
    {
        $rowData = $this->customFieldsMapping($rowData);

        foreach ($rowData as $key => $val) {
            if ($val === '') {
                $rowData[$key] = null;
            }
        }

        static $lastSku = null;

        if (Import::BEHAVIOR_DELETE == $this->getBehavior()) {
            return $rowData;
        }

        $lastSku = $rowData[self::COL_SKU];
        if (strpos($this->productMetadata->getVersion(), '2.2') !== false) {
            $checkSku = strtolower($lastSku);
        } else {
            $checkSku = $lastSku;
        }
        if (isset($this->_oldSku[$checkSku]) && $this->_oldSku[$checkSku]) {
            $newSku = $this->skuProcessor->getNewSku($lastSku);
            if (isset($rowData[self::COL_ATTR_SET]) && !$rowData[self::COL_ATTR_SET]) {
                $rowData[self::COL_ATTR_SET] = $newSku['attr_set_code'];
            }
            if (isset($rowData[self::COL_TYPE]) && !$rowData[self::COL_TYPE]) {
                $rowData[self::COL_TYPE] = $newSku['type_id'];
            }
        }

        return $rowData;
    }

    /**
     * @return \Firebear\ImportExport\Model\Source\Type\AbstractType
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * @return \Magento\Framework\App\ProductMetadata
     */

    public function getProductMetadata()
    {
        return $this->productMetadata;
    }

    /**
     * @param $rowData
     *
     * @return mixed
     */
    protected function applyPriceRules($rowData)
    {
        if (!empty($this->_parameters['price_rules'])) {
            $priceRules = $this->_parameters['price_rules'];

            foreach ($priceRules as $priceRule) {
                $applyRule = true;

                if (isset($priceRule['price_rules_conditions_hidden']['rule']['conditions'])) {
                    $conditions = $priceRule['price_rules_conditions_hidden']['rule']['conditions'];
                    $aggr = $conditions[1]['aggregator'];
                    $aggrValue = $conditions[1]['value'];

                    $data = [
                        'conditions' => $priceRule['price_rules_conditions_hidden']['rule']['conditions'],
                        'row' => $rowData,
                        'aggregator' => $aggr,
                        'value' => $aggrValue,
                        'categories' => isset($this->categoriesCache[$rowData[self::COL_SKU]]) ?
                            array_keys($this->categoriesCache[$rowData[self::COL_SKU]]) : []
                    ];
                    $applyRule = $this->priceRuleConditionFactory->create()->validatePriceRuleConditions($data);
                }

                if ($applyRule) {
                    if ($priceRule['apply'] == 'fixed') {
                        $rowData['price'] += $priceRule['value'];
                    } else {
                        $rowData['price'] *= 1 + $priceRule['value'] / 100;
                    }
                }
            }
        }

        return $rowData;
    }

    protected function checkUrl($rowData)
    {
        if (isset($rowData[self::URL_KEY])) {
            $urlKey = strtolower($this->getUrlKey($rowData));

            if (!isset($this->repeatUrls[$urlKey])) {
                $this->repeatUrls[$urlKey] = 0;
            } else {
                $rowData[self::URL_KEY] = $this->generateUrl($rowData, $this->repeatUrls[$urlKey] + 1);
                $this->repeatUrls[$urlKey] += 1;
            }
        }

        return $rowData;
    }

    /**
     * Parse values of multiselect attributes depends on "Fields Enclosure" parameter
     *
     * @param string $values
     * @param string $delimiter
     * @return array
     * @since 100.1.2
     */
    public function parseMultiselectValues($values, $delimiter = self::PSEUDO_MULTI_LINE_SEPARATOR)
    {
        $delimiter = $this->getMultipleValueSeparator();

        return parent::parseMultiselectValues($values, $delimiter);
    }

    public function getJsonHelper()
    {
        return $this->jsonHelper;
    }
}
