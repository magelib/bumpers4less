<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

// @codingStandardsIgnoreFile

namespace Firebear\ImportExport\Model\Import\Product;

use Firebear\ImportExport\Model\Source\Platform\Magento;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\CatalogImportExport\Model\Import\Product\Option as BaseOption;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;

/**
 * Entity class which provide possibility to import product custom options
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Option extends BaseOption
{
    /**
     * @var string
     */
    private $columnMaxCharacters = '_custom_option_max_characters';

    /**
     * Product entity link field
     *
     * @var string
     */
    private $productEntityLinkField;

    /**
     * Product entity identifier field
     *
     * @var string
     */
    private $productEntityIdentifierField;

    /**
     * @var Magento
     */
    private $magentoPlatform;

    public function __construct(
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory,
        \Magento\ImportExport\Model\ResourceModel\CollectionByPagesIteratorFactory $colIteratorFactory,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        ProcessingErrorAggregatorInterface $errorAggregator,
        \Firebear\ImportExport\Model\Source\Factory $factory,
        \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData,
        array $data = []
    ) {
        parent::__construct(
            $importData,
            $resource,
            $resourceHelper,
            $storeManager,
            $productFactory,
            $optionColFactory,
            $colIteratorFactory,
            $catalogData,
            $scopeConfig,
            $dateTime,
            $errorAggregator,
            $data
        );
        $this->factory = $factory;
        $this->_dataSourceModel = $importFireData;
    }

    /**
     * Import data rows
     *
     * @return boolean
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    /*
    protected function _importData()
    {
        $platform =  $this->factory->create(Magento::class);

        $this->_initProductsSku();

        $nextOptionId = $this->_resourceHelper->getNextAutoincrement($this->_tables['catalog_product_option']);
        $nextValueId = $this->_resourceHelper->getNextAutoincrement(
            $this->_tables['catalog_product_option_type_value']
        );
        $prevOptionId = 0;
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $products = [];
            $options = [];
            $titles = [];
            $prices = [];
            $typeValues = [];
            $typePrices = [];
            $typeTitles = [];
            $parentCount = [];
            $childCount = [];
                foreach ($bunch as $rowNumber => $rowData) {
                if (isset($rowData['_custom_option_type'])){
                    if(!$rowData['sku'])
                        continue;
                    $customOptions = [];
                    $count = $rowNumber;

                    do{
                        //prepare data for custom options
                        if($bunch[$count]['_custom_option_type'])
                            $customOptions[] = $bunch[$count];
                        $count++;
                    }
                    while(isset($bunch[$count]) && !$bunch[$count]['sku']);
                    //transform data for custom options

                    $rowData['custom_options'] = $platform->formatCustomOptions($rowData);

                    if (isset($rowData['_store'])) {
                        $rowData['store_view_code'] = $rowData['_store'];
                    } else {
                        $rowData['store_view_code'] = '';
                    }
                }

                $multiRowData = $this->_getMultiRowFormat($rowData);

                foreach ($multiRowData as $optionData) {

                    $combinedData = array_merge($rowData, $optionData);

                    if (!$this->isRowAllowedToImport($combinedData, $rowNumber)) {
                        continue;
                    }
                    if (!$this->_parseRequiredData($combinedData)) {
                        continue;
                    }
                    $optionData = $this->_collectOptionMainData(
                        $combinedData,
                        $prevOptionId,
                        $nextOptionId,
                        $products,
                        $prices
                    );
                    if ($optionData != null) {
                        $options[] = $optionData;
                    }
                    $this->_collectOptionTypeData(
                        $combinedData,
                        $prevOptionId,
                        $nextValueId,
                        $typeValues,
                        $typePrices,
                        $typeTitles,
                        $parentCount,
                        $childCount
                    );
                    $this->_collectOptionTitle($combinedData, $prevOptionId, $titles);
                }
            }

            // Save prepared custom options data !!!
            if ($this->getBehavior() != Import::BEHAVIOR_APPEND) {
                $this->_deleteEntities(array_keys($products));
            }

            if ($this->_isReadyForSaving($options, $titles, $typeValues)) {
                if ($this->getBehavior() == Import::BEHAVIOR_APPEND) {
                    $this->_compareOptionsWithExisting($options, $titles, $prices, $typeValues);
                }
                $this->_saveOptions(
                    $options
                )->_saveTitles(
                    $titles
                )->_savePrices(
                    $prices
                )->_saveSpecificTypeValues(
                    $typeValues
                )->_saveSpecificTypePrices(
                    $typePrices
                )->_saveSpecificTypeTitles(
                    $typeTitles
                )->_updateProducts(
                    $products
                );
            }
        }

        return true;
    }
*/

    /**
     * @param string $name
     * @param array $optionRow
     * @return array
     */
    private function processOptionRow($name, $optionRow)
    {
        $result = [
            self::COLUMN_TYPE => $name ? $optionRow['type'] : '',
            self::COLUMN_IS_REQUIRED => $optionRow['required'],
            self::COLUMN_ROW_SKU => $optionRow['sku'],
            self::COLUMN_PREFIX . 'sku' => $optionRow['sku'],
            self::COLUMN_ROW_TITLE => '',
            self::COLUMN_ROW_PRICE => ''
        ];

        if (isset($optionRow['option_title'])) {
            $result[self::COLUMN_ROW_TITLE] = $optionRow['option_title'];
        }

        if (isset($optionRow['price'])) {
            $percent_suffix = '';
            if (isset($optionRow['price_type']) && $optionRow['price_type'] == 'percent') {
                $percent_suffix =  '%';
            }
            $result[self::COLUMN_ROW_PRICE] = $optionRow['price'] . $percent_suffix;
        }

        $result[self::COLUMN_PREFIX . 'price'] = $result[self::COLUMN_ROW_PRICE];

        if (isset($optionRow['max_characters'])) {
            $result[$this->columnMaxCharacters] = $optionRow['max_characters'];
        }

        return $result;
    }

    /**
     * Get product entity link field
     *
     * @return string
     */
    private function getProductEntityLinkField()
    {
        if (!$this->productEntityLinkField) {
            $this->productEntityLinkField = $this->getMetadataPool()
                ->getMetadata(ProductInterface::class)
                ->getLinkField();
        }
        return $this->productEntityLinkField;
    }

    /**
     * Get product entity identifier field
     *
     * @return string
     */
    private function getProductIdentifierField()
    {
        if (!$this->productEntityIdentifierField) {
            $this->productEntityIdentifierField = $this->getMetadataPool()
                ->getMetadata(ProductInterface::class)
                ->getIdentifierField();
        }
        return $this->productEntityIdentifierField;
    }

    public function validateRow(array $rowData, $rowNumber)
    {
        
        if (isset($this->_validatedRows[$rowNumber])) {
            return !isset($this->_invalidRows[$rowNumber]);
        }
        $this->_validatedRows[$rowNumber] = true;

        $multiRowData = $this->_getMultiRowFormat($rowData);

        foreach ($multiRowData as $optionData) {

            $combinedData = array_merge($rowData, $optionData);

            if ($this->_isRowWithCustomOption($combinedData)) {
                if ($this->_isMainOptionRow($combinedData)) {
                    if (!$this->_validateMainRow($combinedData, $rowNumber)) {
                        return false;
                    }
                }
                if ($this->_isSecondaryOptionRow($combinedData)) {
                    if (!$this->_validateSecondaryRow($combinedData, $rowNumber)) {
                        return false;
                    }
                }
                return true;
            }
        }

        return false;
    }
}
