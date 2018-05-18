<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

use \Magento\Store\Model\Store;
use \Magento\CatalogImportExport\Model\Import\Product as ImportProduct;
use Magento\ImportExport\Model\Import;

class Product extends \Magento\CatalogImportExport\Model\Export\Product
{
    use \Firebear\ImportExport\Traits\Export\Products;

    use \Firebear\ImportExport\Traits\General;

    protected $headColumns;

    protected $additional;

    private $userDefinedAttributes = [];

    protected $keysAdditional;

    /**
     * Product constructor.
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig
     * @param \Magento\Catalog\Model\ResourceModel\ProductFactory $productFactory
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory
     * @param \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $itemFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeColFactory
     * @param \Magento\CatalogImportExport\Model\Export\Product\Type\Factory $_typeFactory
     * @param \Magento\Catalog\Model\Product\LinkTypeProvider $linkTypeProvider
     * @param \Magento\CatalogImportExport\Model\Export\RowCustomizerInterface $rowCustomizer
     * @param Product\Additional $additional
     * @param array $dateAttrCodes
     */
    public function __construct(
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig,
        \Magento\Catalog\Model\ResourceModel\ProductFactory $productFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory,
        \Magento\CatalogInventory\Model\ResourceModel\Stock\ItemFactory $itemFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Option\CollectionFactory $optionColFactory,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $attributeColFactory,
        \Magento\CatalogImportExport\Model\Export\Product\Type\Factory $_typeFactory,
        \Magento\Catalog\Model\Product\LinkTypeProvider $linkTypeProvider,
        \Magento\CatalogImportExport\Model\Export\RowCustomizerInterface $rowCustomizer,
        \Firebear\ImportExport\Model\Export\Product\Additional $additional,
        array $dateAttrCodes = []
    ) {
        parent::__construct(
            $localeDate,
            $config,
            $resource,
            $storeManager,
            $logger,
            $collectionFactory,
            $exportConfig,
            $productFactory,
            $attrSetColFactory,
            $categoryColFactory,
            $itemFactory,
            $optionColFactory,
            $attributeColFactory,
            $_typeFactory,
            $linkTypeProvider,
            $rowCustomizer,
            $dateAttrCodes
        );
        $this->additional = $additional;
    }

    /**
     * @return array
     */
    protected function getExportData()
    {
        $exportData = [];
        try {
            $rawData = $this->collectRawData();
            $multirawData = $this->collectMultirawData();

            $productIds = array_keys($rawData);
            $stockItemRows = $this->prepareCatalogInventory($productIds);
            $this->rowCustomizer->prepareData(
                $this->_prepareEntityCollection($this->_entityCollectionFactory->create()),
                $productIds
            );

            $this->setAddHeaderColumns($stockItemRows);
            $prevData = [];
            foreach ($rawData as $productId => $productData) {
                foreach ($productData as $storeId => $dataRow) {
                    if ($storeId == Store::DEFAULT_STORE_ID && isset($stockItemRows[$productId])) {
                        $dataRow = array_merge($dataRow, $stockItemRows[$productId]);
                    }
                    $this->appendMultirowData($dataRow, $multirawData);

                    if ($dataRow) {
                        if (!empty($prevData)) {
                            if (isset($prevData['sku']) && isset($dataRow['sku'])) {
                                if ($prevData['sku'] == $dataRow['sku']) {
                                    $dataRow = array_merge($prevData, $dataRow);
                                }
                            }
                        }
                        $exportData[] = $dataRow;
                    }
                    $prevData = $dataRow;
                }
            }
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
        $newData = $this->changeData($exportData);

        $this->addHeaderColumns();
        $this->_headerColumns = $this->changeHeaders($this->_headerColumns);

        return $newData;
    }

    protected function _customHeadersMapping($rowData)
    {
        $rowData = parent::_customHeadersMapping($rowData);

        return ($this->_parameters['all_fields']) ? $this->_headerColumns : array_unique($rowData);
    }

    /**
     * @return string
     */
    public function export()
    {
        $this->keysAdditional = [];
        $this->scopeHeader();

        set_time_limit(0);

        $writer = $this->getWriter();
        $page = 0;
        $counts = 0;
        while (true) {
            ++$page;
            $entityCollection = $this->_getEntityCollection(true);
            $entityCollection->setOrder('entity_id', 'asc');
            $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
            $this->_prepareEntityCollection($entityCollection);
            $this->paginateCollection($page, $this->getItemsPerPage());
            if ($entityCollection->count() == 0) {
                break;
            }
            $exportData = $this->getExportData();
            if ($page == 1) {
                $writer->setHeaderCols($this->_getHeaderColumns());
            }
            foreach ($exportData as $dataRow) {
                $writer->writeRow($this->_customFieldsMapping($dataRow));
                $counts++;
            }
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }

        return [$writer->getContents(), $counts];
    }

    protected function scopeHeader()
    {
        $page = 0;
        while (true) {
            ++$page;
            $entityCollection = $this->_getEntityCollection(true);
            $entityCollection->setOrder('entity_id', 'asc');
            $entityCollection->setStoreId(Store::DEFAULT_STORE_ID);
            $this->_prepareEntityCollection($entityCollection);
            $this->paginateCollection($page, $this->getItemsPerPage());
            if ($entityCollection->count() == 0) {
                break;
            }

            $this->getHeaderData();
            if ($entityCollection->getCurPage() >= $entityCollection->getLastPageNumber()) {
                break;
            }
        }

        $this->headColumns = $this->_headerColumns;

        $this->_headerColumns = [];
    }

    protected function getHeaderData()
    {
        $exportData = [];
        try {
            $rawData = $this->collectRawData();
            // $multirawData = $this->collectMultirawData();
            $productIds = array_keys($rawData);
            $stockItemRows = $this->prepareCatalogInventory($productIds);
            $this->rowCustomizer->prepareData($this->_getEntityCollection(), $productIds);
            //   $this->setHeaderColumns($multirawData['customOptionsData'], $stockItemRows);
            $this->setAddHeaderColumns($stockItemRows);

            $this->_headerColumns = $this->rowCustomizer->addHeaderColumns($this->_headerColumns);
        } catch (\Exception $e) {
            $this->_logger->critical($e);
        }
    }

    /**
     * @param array $rowData
     * @return array
     */
    protected function _customFieldsMapping($rowData)
    {

        $headerColumns = $this->_getHeaderColumns();

        $rowData = parent::_customFieldsMapping($rowData);
        if (count($headerColumns != count(array_keys($rowData)))) {
            $newData = [];
            foreach ($headerColumns as $code) {
                //   $flipFieldsMap = array_flip($this->_fieldsMap);
                $fieldCode = isset($this->_fieldsMap[$code]) ? $this->_fieldsMap[$code] : null;
                if ($fieldCode && isset($rowData[$fieldCode])) {
                    $newData[$code] = $rowData[$fieldCode];
                } else {

                    if (!isset($rowData[$code])) {
                        $newData[$code] = '';
                    } else {
                        $newData[$code] = $rowData[$code];
                    }
                }
            }
            $rowData = $newData;
        }

        return $rowData;
    }

    /**
     * @param \Magento\Eav\Model\Entity\Collection\AbstractCollection $collection
     * @return \Magento\Eav\Model\Entity\Collection\AbstractCollection
     */
    protected function _prepareEntityCollection(\Magento\Eav\Model\Entity\Collection\AbstractCollection $collection)
    {
        if (!isset($this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP])
            || !is_array($this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP])) {
            $exportFilter = [];
        } else {
            $exportFilter = $this->_parameters[\Magento\ImportExport\Model\Export::FILTER_ELEMENT_GROUP];
        }

        $collection = \Magento\ImportExport\Model\Export\Entity\AbstractEntity::_prepareEntityCollection($collection);
        $joinTable = 0;

        foreach ($this->additional->fields as $field) {
            if (isset($exportFilter[$field]) && !empty($exportFilter[$field])) {
                if ($field == 'store') {
                    $collection->addStoreFilter($exportFilter['store']);
                } else {
                    $collection->getSelect()->where($this->additional->convertFields($field) . "=?",
                        $exportFilter[$field]);
                }
            }
        }

        if (isset($exportFilter['category_ids']) && !empty($exportFilter['category_ids'])) {
            $table = $collection->getResource()->getTable('catalog_category_product');
            $collection->joinTable(
                ['cp' => $table],
                'entity_id = entity_id',
                ['category_id'],
                '`cp`.`category_id` IN (' . implode(",", $exportFilter['category_ids']) . ')'
            );
        }

        return $collection;
    }

    protected function collectMultirawData()
    {
        $data = [];
        $productIds = [];
        $rowWebsites = [];
        $rowCategories = [];
        $productLinkIds = [];

        $collection = $this->_getEntityCollection();
        $collection->setStoreId(Store::DEFAULT_STORE_ID);
        $collection->addCategoryIds()->addWebsiteNamesToResult();
        /** @var \Magento\Catalog\Model\Product $item */
        foreach ($collection as $item) {
            $productLinkIds[] = $item->getData($this->getProductEntityLinkField());
            $productIds[] = $item->getId();
            $rowWebsites[$item->getId()] = array_intersect(
                array_keys($this->_websiteIdToCode),
                $item->getWebsites()
            );
            $rowCategories[$item->getId()] = array_combine($item->getCategoryIds(), $item->getCategoryIds());
        }
        $collection->clear();

        $allCategoriesIds = array_merge(array_keys($this->_categories), array_keys($this->_rootCategories));
        $allCategoriesIds = array_combine($allCategoriesIds, $allCategoriesIds);
        foreach ($rowCategories as &$categories) {
            $categories = array_intersect_key($categories, $allCategoriesIds);
        }

        $data['rowWebsites'] = $rowWebsites;
        $data['rowCategories'] = $rowCategories;

        $data['linksRows'] = $this->prepareLinks($productLinkIds);

        $data['customOptionsData'] = $this->getCustomOptionsData($productLinkIds);

        return $data;
    }

    /**
     * @return array
     */
    protected function fieldsCatalogInventory()
    {
        $fields = $this->_connection->describeTable($this->_itemFactory->create()->getMainTable());
        $rows = [];
        $row = [];
        unset(
            $fields['item_id'],
            $fields['product_id'],
            $fields['low_stock_date'],
            $fields['stock_id'],
            $fields['stock_status_changed_auto']
        );
        foreach ($fields as $key => $field) {
            $row[$key] = $key;
        }

        $rows[] = $row;
        return $rows;
    }

    protected function collectRawData()
    {
        $data = [];
        $items = $this->fireloadCollection();
        foreach ($items as $itemId => $itemByStore) {

            /**
             * @var int $itemId
             * @var ProductEntity $item
             */
            foreach ($this->_storeIdToCode as $storeId => $storeCode) {
                $addtionalFields = [];
                $item = $itemByStore[$storeId];
                $additionalAttributes = [];
                $productLinkId = $item->getData($this->getProductEntityLinkField());
                foreach ($this->_getExportAttrCodes() as $code) {
                    $attrValue = $item->getData($code);
                    if (!$this->isValidAttributeValue($code, $attrValue)) {
                        continue;
                    }

                    if (isset($this->_attributeValues[$code][$attrValue]) && !empty($this->_attributeValues[$code])) {
                        $attrValue = $this->_attributeValues[$code][$attrValue];
                    }
                    $fieldName = isset($this->_fieldsMap[$code]) ? $this->_fieldsMap[$code] : $code;

                    if ($this->_attributeTypes[$code] == 'datetime') {
                        if (in_array($code, $this->dateAttrCodes)
                            || in_array($code, $this->userDefinedAttributes)
                        ) {
                            $attrValue = $this->_localeDate->formatDateTime(
                                new \DateTime($attrValue),
                                \IntlDateFormatter::SHORT,
                                \IntlDateFormatter::NONE,
                                null,
                                date_default_timezone_get()
                            );
                        } else {
                            $attrValue = $this->_localeDate->formatDateTime(
                                new \DateTime($attrValue),
                                \IntlDateFormatter::SHORT,
                                \IntlDateFormatter::SHORT
                            );
                        }
                    }

                    if ($storeId != Store::DEFAULT_STORE_ID
                        && isset($data[$itemId][Store::DEFAULT_STORE_ID][$fieldName])
                        && $data[$itemId][Store::DEFAULT_STORE_ID][$fieldName] == htmlspecialchars_decode($attrValue)
                    ) {
                        continue;
                    }

                    if ($this->_attributeTypes[$code] !== 'multiselect') {
                        if (is_scalar($attrValue)) {
                            if (!in_array($fieldName, $this->_getExportMainAttrCodes())) {
                                $additionalAttributes[$fieldName] = $fieldName .
                                    ImportProduct::PAIR_NAME_VALUE_SEPARATOR . $this->wrapValue($attrValue);
                                if ($this->checkDivideofAttributes()) {
                                    $addtionalFields[$fieldName] = $attrValue;
                                    if (!in_array($fieldName, $this->keysAdditional)) {
                                        $this->keysAdditional[] = $fieldName;
                                    }
                                }
                            }
                            if (in_array($fieldName, ['description', 'short_description'])) {
                                $attrValue = addslashes($attrValue);
                            }
                            $data[$itemId][$storeId][$fieldName] = htmlspecialchars_decode($attrValue);
                        }
                    } else {
                        $this->collectMultiselectValues($item, $code, $storeId);
                        if (!empty($this->collectedMultiselectsData[$storeId][$productLinkId][$code])) {
                            $additionalAttributes[$code] = $fieldName .
                                ImportProduct::PAIR_NAME_VALUE_SEPARATOR . implode(
                                    ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR,
                                    $this->wrapValue($this->collectedMultiselectsData[$storeId][$productLinkId][$code])
                                );
                            if ($this->checkDivideofAttributes()) {
                                if (!in_array($code, $this->keysAdditional)) {
                                    $this->keysAdditional[] = $code;
                                }
                                $addtionalFields[$code] = $this->collectedMultiselectsData[$storeId][$productLinkId][$code];
                            }
                        }
                    }
                }
                if (!empty($additionalAttributes)) {
                    $additionalAttributes = array_map('htmlspecialchars_decode', $additionalAttributes);
                    $data[$itemId][$storeId][self::COL_ADDITIONAL_ATTRIBUTES] =
                        implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalAttributes);
                } else {
                    unset($data[$itemId][$storeId][self::COL_ADDITIONAL_ATTRIBUTES]);
                }

                if (!empty($data[$itemId][$storeId]) || $this->hasMultiselectData($item, $storeId)) {
                    $attrSetId = $item->getAttributeSetId();
                    $data[$itemId][$storeId][self::COL_STORE] = $storeCode;
                    $data[$itemId][$storeId][self::COL_ATTR_SET] = $this->_attrSetIdToName[$attrSetId];
                    $data[$itemId][$storeId][self::COL_TYPE] = $item->getTypeId();
                }
                if (!empty($addtionalFields)) {
                    foreach ($addtionalFields as $key => $value) {
                        $data[$itemId][$storeId][$key] = $value;
                    }
                }
                $data[$itemId][$storeId][self::COL_SKU] = htmlspecialchars_decode($item->getSku());
                $data[$itemId][$storeId]['store_id'] = $storeId;
                $data[$itemId][$storeId]['product_id'] = $itemId;
                $data[$itemId][$storeId]['product_link_id'] = $productLinkId;
            }
        }

        return $data;
    }

    private function wrapValue(
        $value
    ) {
        if (!empty($this->_parameters[\Magento\ImportExport\Model\Export::FIELDS_ENCLOSURE])) {
            $wrap = function ($value) {
                return sprintf('"%s"', str_replace('"', '""', $value));
            };

            $value = is_array($value) ? array_map($wrap, $value) : $wrap($value);
        }

        return $value;
    }

    /**
     * @param $stockItemRows
     */
    protected function setAddHeaderColumns($stockItemRows)
    {
        $addData = [];

        if (!empty($stockItemRows)) {
            if (reset($stockItemRows)) {
                $addData = array_keys(end($stockItemRows));
                foreach ($addData as $key => $value) {
                    if (is_numeric($value)) {
                        unset($addData[$key]);
                    }
                }
            }
        }
        if (!$this->_headerColumns) {
            $this->_headerColumns = array_merge(
                [
                    self::COL_SKU,
                    self::COL_STORE,
                    self::COL_ATTR_SET,
                    self::COL_TYPE,
                    self::COL_CATEGORY,
                    self::COL_PRODUCT_WEBSITES,
                ],
                $this->_getExportMainAttrCodes(),
                [self::COL_ADDITIONAL_ATTRIBUTES],
                $addData,
                [
                    'related_skus',
                    'related_position',
                    'crosssell_skus',
                    'crosssell_position',
                    'upsell_skus',
                    'upsell_position',
                    'additional_images',
                    'additional_image_labels',
                    'hide_from_product_page',
                    'custom_options'
                ]
            );

        }

    }

    protected function addHeaderColumns()
    {
        if ($this->checkDivideofAttributes()) {
            $this->_headerColumns = array_merge($this->_headerColumns, $this->keysAdditional);
        }
    }

    protected function fireloadCollection()
    {
        $data = [];

        $collection = $this->_getEntityCollection();
        foreach (array_keys($this->_storeIdToCode) as $storeId) {
            $collection->setStoreId($storeId);
            foreach ($collection as $itemId => $item) {
                $data[$itemId][$storeId] = $item;
            }
        }
        $collection->clear();

        return $data;
    }

    protected function checkDivideofAttributes()
    {
        return isset($this->_parameters['divided_additional']) && $this->_parameters['divided_additional'];
    }

    private function appendMultirowData(&$dataRow, &$multiRawData)
    {
        $productId = $dataRow['product_id'];
        $productLinkId = $dataRow['product_link_id'];
        $storeId = $dataRow['store_id'];
        $sku = $dataRow[self::COL_SKU];

        unset($dataRow['product_id']);
        unset($dataRow['product_link_id']);
        unset($dataRow['store_id']);
        unset($dataRow[self::COL_SKU]);
        if (Store::DEFAULT_STORE_ID == $storeId) {
            unset($dataRow[self::COL_STORE]);
            $this->updateDataWithCategoryColumns($dataRow, $multiRawData['rowCategories'], $productId);
            if (!empty($multiRawData['rowWebsites'][$productId])) {
                $websiteCodes = [];
                foreach ($multiRawData['rowWebsites'][$productId] as $productWebsite) {
                    $websiteCodes[] = $this->_websiteIdToCode[$productWebsite];
                }
                $dataRow[self::COL_PRODUCT_WEBSITES] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $websiteCodes);
                $multiRawData['rowWebsites'][$productId] = [];
            }

            $multiRawData['mediaGalery'] = $this->getMediaGallery([$productLinkId]);
            if (!empty($multiRawData['mediaGalery'][$productLinkId])) {
                $additionalImages = [];
                $additionalImageLabels = [];
                $additionalImageIsDisabled = [];
                foreach ($multiRawData['mediaGalery'][$productLinkId] as $mediaItem) {
                    $additionalImages[] = $mediaItem['_media_image'];
                    $additionalImageLabels[] = $mediaItem['_media_label'];

                    if ($mediaItem['_media_is_disabled'] == true) {
                        $additionalImageIsDisabled[] = $mediaItem['_media_image'];
                    }
                }
                $dataRow['additional_images'] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImages);
                $dataRow['additional_image_labels'] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImageLabels);
                $dataRow['hide_from_product_page'] =
                    implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, $additionalImageIsDisabled);
                $multiRawData['mediaGalery'][$productLinkId] = [];
            }
            foreach ($this->_linkTypeProvider->getLinkTypes() as $linkTypeName => $linkId) {
                if (!empty($multiRawData['linksRows'][$productLinkId][$linkId])) {
                    $colPrefix = $linkTypeName . '_';
                    $associations = [];
                    foreach ($multiRawData['linksRows'][$productLinkId][$linkId] as $linkData) {
                        if ($linkData['default_qty'] !== null) {
                            $skuItem = $linkData['sku'] . ImportProduct::PAIR_NAME_VALUE_SEPARATOR .
                                $linkData['default_qty'];
                        } else {
                            $skuItem = $linkData['sku'];
                        }
                        $associations[$skuItem] = $linkData['position'];
                    }
                    $multiRawData['linksRows'][$productLinkId][$linkId] = [];
                    asort($associations);
                    $dataRow[$colPrefix . 'skus'] =
                        implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, array_keys($associations));
                    $dataRow[$colPrefix . 'position'] =
                        implode(Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR, array_values($associations));
                }
            }
            $dataRow = $this->rowCustomizer->addData($dataRow, $productId);
        }

        if (!empty($this->collectedMultiselectsData[$storeId][$productId])) {
            foreach (array_keys($this->collectedMultiselectsData[$storeId][$productId]) as $attrKey) {
                if (!empty($this->collectedMultiselectsData[$storeId][$productId][$attrKey])) {
                    $dataRow[$attrKey] = implode(
                        Import::DEFAULT_GLOBAL_MULTI_VALUE_SEPARATOR,
                        $this->collectedMultiselectsData[$storeId][$productId][$attrKey]
                    );
                }
            }
        }

        if (!empty($multiRawData['customOptionsData'][$productLinkId][$storeId])) {
            $customOptionsRows = $multiRawData['customOptionsData'][$productLinkId][$storeId];
            $multiRawData['customOptionsData'][$productLinkId][$storeId] = [];
            $customOptions = implode(ImportProduct::PSEUDO_MULTI_LINE_SEPARATOR, $customOptionsRows);

            $dataRow = array_merge($dataRow, ['custom_options' => $customOptions]);
        }

        if (empty($dataRow)) {
            return null;
        } elseif ($storeId != Store::DEFAULT_STORE_ID) {
            $dataRow[self::COL_STORE] = $this->_storeIdToCode[$storeId];
        }
        $dataRow[self::COL_SKU] = $sku;

        return $dataRow;
    }
}
