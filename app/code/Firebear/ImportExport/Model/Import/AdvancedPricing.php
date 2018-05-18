<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Symfony\Component\Console\Output\ConsoleOutput;
use Firebear\ImportExport\Model\Import\Product as ImportProduct;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Psr\Log\LoggerInterface;

class AdvancedPricing extends \Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing
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

    private $productEntityLinkField;

    protected $entityProducts;

    /**
     * AdvancedPricing constructor.
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\ImportExport\Helper\Data $importExportData
     * @param \Magento\ImportExport\Model\ResourceModel\Import\Data $importData
     * @param \Magento\Eav\Model\Config $config
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory
     * @param \Magento\Catalog\Model\Product $productModel
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\CatalogImportExport\Model\Import\Product\StoreResolver $storeResolver
     * @param Product $importProduct
     * @param \Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing\Validator $validator
     * @param \Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing\Validator\Website $websiteValidator
     * @param \Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing\Validator\TierPrice $tierPriceValidator
     * @param ConsoleOutput $output
     * @param \Firebear\ImportExport\Helper\Data $helper
     * @param LoggerInterface $logger
     * @param \Firebear\ImportExport\Model\ResourceModel\Import\Data
     */
    public function __construct(
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\ImportExport\Helper\Data $importExportData,
        \Magento\ImportExport\Model\ResourceModel\Import\Data $importData,
        \Magento\Eav\Model\Config $config,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Framework\Stdlib\StringUtils $string,
        ProcessingErrorAggregatorInterface $errorAggregator,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceFactory,
        \Magento\Catalog\Model\Product $productModel,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\CatalogImportExport\Model\Import\Product\StoreResolver $storeResolver,
        ImportProduct $importProduct,
        \Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing\Validator $validator,
        \Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing\Validator\Website $websiteValidator,
        \Magento\AdvancedPricingImportExport\Model\Import\AdvancedPricing\Validator\TierPrice $tierPriceValidator,
        \Symfony\Component\Console\Output\ConsoleOutput $output,
        \Firebear\ImportExport\Helper\Data $helper,
        LoggerInterface $logger,
        \Firebear\ImportExport\Model\ResourceModel\Import\Data $importFireData
    ) {
        parent::__construct(
            $jsonHelper,
            $importExportData,
            $importData,
            $config,
            $resource,
            $resourceHelper,
            $string,
            $errorAggregator,
            $dateTime,
            $resourceFactory,
            $productModel,
            $catalogData,
            $storeResolver,
            $importProduct,
            $validator,
            $websiteValidator,
            $tierPriceValidator
        );
        $this->_logger = $logger;
        $this->output = $output;
        $this->_debugMode = $helper->getDebugMode();
        $this->_dataSourceModel = $importFireData;
    }

    /**
     * @param array $prices
     * @param string $table
     * @return $this
     */
    protected function processCountExistingPrices($prices, $table)
    {
        $tableName = $this->_resourceFactory->create()->getTable($table);
        $productEntityLinkField = $this->getProductEntityLinkField();
        $existingPrices = $this->_connection->fetchAssoc(
            $this->_connection->select()->from(
                $tableName,
                ['value_id', $productEntityLinkField, 'all_groups', 'customer_group_id']
            )->where($productEntityLinkField . ' in(?)', $this->getEntity($productEntityLinkField))
        );

        $oldSkus = $this->retrieveOldSkus();
        foreach ($existingPrices as $existingPrice) {
            foreach ($oldSkus as $sku => $productId) {
                if ($existingPrice[$productEntityLinkField] == $productId && isset($prices[$sku])) {
                    $this->incrementCounterUpdated($prices[$sku], $existingPrice);
                }
            }
        }

        return $this;
    }

    /**
     * @return string
     */
    private function getProductEntityLinkField()
    {
        if (!$this->productEntityLinkField) {
            $this->productEntityLinkField = $this->getMetadataPool()
                ->getMetadata(\Magento\Catalog\Api\Data\ProductInterface::class)
                ->getLinkField();
        }

        return $this->productEntityLinkField;
    }

    /**
     * @return $this
     */
    protected function saveAndReplaceAdvancedPrices()
    {
        $behavior = $this->getBehavior();
        if (\Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE == $behavior) {
            $this->_cachedSkuToDelete = null;
        }
        $listSku = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $tierPrices = [];
            foreach ($bunch as $rowNum => $rowData) {
                $rowData = $this->joinIdenticalyData($rowData);
                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addLogWriteln(__('sku: %1 is not valited', $rowData[self::COL_SKU]), $this->output, 'info');
                    continue;
                }
                $time = explode(" ", microtime());
                $startTime = $time[0] + $time[1];
                $sku = $rowData[self::COL_SKU];
                if (!$this->validateRow($rowData, $rowNum)) {
                    $this->addRowError(ValidatorInterface::ERROR_SKU_IS_EMPTY, $rowNum);
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                $rowSku = $rowData[self::COL_SKU];
                $listSku[] = $rowSku;
                if (!empty($rowData[self::COL_TIER_PRICE_WEBSITE])) {
                    $tierPrices[$rowSku][] = [
                        'all_groups' => $rowData[self::COL_TIER_PRICE_CUSTOMER_GROUP] == self::VALUE_ALL_GROUPS,
                        'customer_group_id' => $this->getCustomerGroupId(
                            $rowData[self::COL_TIER_PRICE_CUSTOMER_GROUP]
                        ),
                        'qty' => $rowData[self::COL_TIER_PRICE_QTY],
                        'value' => $rowData[self::COL_TIER_PRICE],
                        'website_id' => $this->getWebsiteId($rowData[self::COL_TIER_PRICE_WEBSITE])
                    ];
                }
                $time = explode(" ", microtime());
                $endTime = $time[0] + $time[1];
                $totalTime = $endTime - $startTime;
                $totalTime = round($totalTime, 5);
                $this->addLogWriteln(__('sku: %1 .... %2s', $sku, $totalTime), $this->output, 'info');

            }

            $this->getEntities($listSku);
            if (\Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE == $behavior) {
                if ($listSku) {
                    $this->processCountNewPrices($tierPrices);
                    if ($this->deleteProductTierPrices(array_unique($listSku), self::TABLE_TIER_PRICE)) {
                        $this->saveProductPrices($tierPrices, self::TABLE_TIER_PRICE);
                        $this->setUpdatedAt($listSku);
                    }
                }
            } elseif (\Magento\ImportExport\Model\Import::BEHAVIOR_APPEND == $behavior) {
                $this->processCountExistingPrices($tierPrices, self::TABLE_TIER_PRICE)
                    ->processCountNewPrices($tierPrices);
                $this->saveProductPrices($tierPrices, self::TABLE_TIER_PRICE);

                if ($listSku) {
                    $this->setUpdatedAt($listSku);
                }
            }
        }

        return $this;
    }

    /**
     * @param $listSku
     */
    protected function getEntities($listSku)
    {
        $this->entityProducts = $this->_connection->fetchAll(
            $this->_connection->select()->from(
                $this->_catalogProductEntity,
                ['sku', $this->getProductEntityLinkField()]
            )->where('sku in(?)', $listSku)
        );
    }

    /**
     * @param $field
     * @return array
     */
    protected function getEntity($field)
    {
        $array = [];
        if (!empty($this->entityProducts)) {
            foreach ($this->entityProducts as $value) {
                $array[] = $value[$field];
            }
        }

        return $array;
    }

    /**
     * @return array
     */
    protected function retrieveOldSkus()
    {
        $select = $this->_connection->select()->from(
            $this->_catalogProductEntity,
            ['sku', $this->getProductEntityLinkField()]
        );
        if ($skus = $this->getEntity('sku')) {
            $select->where('sku in(?)', $this->getEntity('sku'));
        }
        $this->_oldSkus = $this->_connection->fetchPairs(
            $select
        );
        return $this->_oldSkus;
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
}
