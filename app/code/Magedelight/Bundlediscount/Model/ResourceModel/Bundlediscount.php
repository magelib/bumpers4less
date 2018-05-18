<?php

/**
* Magedelight
* Copyright (C) 2016 Magedelight <info@magedelight.com>.
*
* NOTICE OF LICENSE
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
*
* @category Magedelight
*
* @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
* @author Magedelight <info@magedelight.com>
*/

namespace Magedelight\Bundlediscount\Model\ResourceModel;

use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\DirectoryList;

class Bundlediscount extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
    * Errors in import process.
    *
    * @var array
    */
    protected $_importErrors = [];

    /**
    * @var \Magento\Framework\App\Config\ScopeConfigInterface
    */
    protected $_coreConfig;

    /**
    * @var \Psr\Log\LoggerInterface
    */
    protected $_logger;

    /**
    * @var \Magento\Store\Model\StoreManagerInterface
    */
    protected $_storeManager;

    /**
    * Filesystem instance.
    *
    * @var \Magento\Framework\Filesystem
    */
    protected $_filesystem;

    /**
    * Customer factory.
    *
    * @var \Magento\Customer\Model\CustomerFactory
    */
    protected $_customerFactory;
    protected $_exportHeaderColumn = array('bundle_id', 'base_sku', 'bundle_name', 'base_qty', 'discount_type', 'discount_price', 'status', 'exclude_base_product', 'sort_order', 'store_ids', 'customer_groups', 'start_from', 'ends_on', 'item_sku', 'item_qty', 'item_sort_order');
    protected $_bundleExportIgnoreColumns = array('created_at', 'updated_at');
    protected $_bundleColumnMaps = array(
        'bundle_id' => 'bundle_id',
        'product_id' => 'base_sku',
        'name' => 'bundle_name',
        'qty' => 'base_qty',
        'discount_type' => 'discount_type',
        'discount_price' => 'discount_price',
        'status' => 'status',
        'exclude_base_product' => 'exclude_base_product',
        'sort_order' => 'sort_order',
        'store_ids' => 'store_ids',
        'customer_groups' => 'customer_groups',
        'date_from' => 'start_from',
        'date_to' => 'ends_on',
    );
    protected $_itemsColumnMaps = array(
        'product_id' => 'item_sku',
        'qty' => 'item_qty',
        'sort_order' => 'item_sort_order',
    );
    protected $_discountLabels = array(
        'Fixed' => 0,
        'Percentage' => 1,
    );
    protected $_storeMaps = array();
    protected $_groupsMap = array();
    protected $_helper;
    protected $_itemExportIgnoreColumns = array('item_id', 'bundle_id');
    protected $_importHeaderColumns = array();
    protected $_datavalidationMessages = array();

    /**
    * @param \Magento\Framework\Model\ResourceModel\Db\Context  $context
    * @param \Psr\Log\LoggerInterface                           $logger
    * @param \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig
    * @param \Magento\Store\Model\StoreManagerInterface         $storeManager
    * @param Filesystem                                         $filesystem
    * @param \Magento\Customer\Model\CustomerFactory            $customerFactory
    * @param \Magedelight\Bundlediscount\Helper\Data            $helper
    * @param \Magento\Customer\Model\GroupFactory               $collectionFactory
    * @param string                                             $connectionName
    */
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context, \Psr\Log\LoggerInterface $logger, \Magento\Framework\App\Config\ScopeConfigInterface $coreConfig, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\Filesystem $filesystem, \Magento\Customer\Model\CustomerFactory $customerFactory, \Magedelight\Bundlediscount\Helper\Data $helper, \Magento\Customer\Model\GroupFactory $collectionFactory, $connectionName = null
    ) {
        $this->_coreConfig = $coreConfig;
        $this->_logger = $logger;
        $this->_storeManager = $storeManager;
        $this->_filesystem = $filesystem;
        $this->_customerFactory = $customerFactory;
        $this->_helper = $helper;
        $this->collectionFactory = $collectionFactory;
        parent::__construct($context, $connectionName);
    }

    public function _construct()
    {
        $this->_init('md_bundlediscount_bundles', 'bundle_id');
        $this->_storeMaps = $this->_helper->getStoreCodeMaps();
        $this->_groupsMap = $this->collectionFactory->create()->getCollection()->load()->toOptionHash();
    }

    public function uploadAndImport(\Magento\Framework\DataObject $object)
    {

        try {
            $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $uploader = $this->_objectManager->create('Magento\MediaStorage\Model\File\Uploader', ['fileId' => 'bundlediscountimport']);
            $uploader->setAllowedExtensions(['csv']);
        } catch (\Exception $e) {
            if ($e->getCode() == '666') {
                return $this;
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__($e->getMessage()));
            }
        }

        $csvFile = $uploader->validateFile()['tmp_name'];
        $website = $this->_storeManager->getWebsite($object->getScopeId());

        $this->_importWebsiteId = (int) $website->getId();
        $this->_importUniqueHash = [];
        $this->_importErrors = [];
        $this->_importedRows = 0;

        $tmpDirectory = $this->_filesystem->getDirectoryRead(DirectoryList::SYS_TMP);
        $path = $tmpDirectory->getRelativePath($csvFile);
        $stream = $tmpDirectory->openFile($path);

        // check and skip headers
        $headers = $stream->readCsv();
        if ($headers === false || count($headers) < 1) {
            $stream->close();
            throw new \Magento\Framework\Exception\LocalizedException(__('Please correct Bundle Discount File Format.'));
        }

        $this->_importHeaderColumns = $headers;
        $isValidateColumns = $this->_validateColumns($headers);
        $connection = $this->getConnection();
        $connection->beginTransaction();

        try {
            if ($isValidateColumns) {
                $rowNumber = 0;
                $data = array();
                $importData = array();
                $finalData = array();
                while (false !== ($csvLine = $stream->readCsv())) {
                    $isBundle = $this->isBundlesRow($csvLine);
                    if ($isBundle) {
                        ++$rowNumber;
                        $isValid = true;
                        $isSelectionTrue = true;
                        $itemRow = 0;
                    }
                    foreach ($csvLine as $key => $value) {
                        $columnName = ($isBundle) ? array_search($this->_importHeaderColumns[$key], $this->_bundleColumnMaps) : array_search($this->_importHeaderColumns[$key], $this->_itemsColumnMaps);

                        if ($isBundle) {
                            if (is_string($columnName) && strlen($columnName) > 0) {
                                $data[$rowNumber][$columnName] = $this->getMappedValues($value, $this->_importHeaderColumns[$key], 'import', true, $isValid);
                                if (!$isValid) {
                                    unset($data[$rowNumber]);
                                }
                            }
                        } else {
                            if (is_string($columnName) && strlen($columnName) > 0 && $isValid) {
                                $data[$rowNumber]['selections'][$itemRow][$columnName] = $this->getMappedValues($value, $this->_importHeaderColumns[$key], 'import', false, $isSelectionTrue);
                                if (!$isSelectionTrue) {
                                    unset($data[$rowNumber]['selections'][$itemRow]);
                                    continue;
                                }
                            }
                        }
                    }

                    if (!$isBundle) {
                        ++$itemRow;
                    }
                }
                $this->saveBundlesData($data);
                foreach ($this->_datavalidationMessages as $invalidMessage) {
                    $this->_importErrors[] = $invalidMessage;
                }
            } else {
                foreach ($this->_errorMessages as $message) {
                    $this->_importErrors[] = $message;
                }

                return false;
            }
        } catch (\Exception $e) {
            $connection->rollback();
            $stream->streamClose();

            throw new \Magento\Framework\Exception\LocalizedException($e->getMessage());
        } catch (\Exception $e) {
            $connection->rollback();
            $stream->streamClose();
            $this->_logger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException('An error occurred while import data.');
        }

        $connection->commit();

        if ($this->_importErrors) {
            $error = __(
                'We couldn\'t import this file because of these errors: %1', implode(" \n", $this->_importErrors)
            );
            throw new \Magento\Framework\Exception\LocalizedException($error);
        }

        return $this;
    }

    protected function _validateColumns($columns)
    {
        $invalidColumns = array();
        $validColumns = true;
        foreach ($columns as $column) {
            if (!in_array($column, $this->_exportHeaderColumn)) {
                $invalidColumns[] = $column;
            }
        }
        if (count($invalidColumns) > 0) {
            $this->_importErrors[] = __('Invalid columns %1 specified in import file', implode(', ', $invalidColumns));
            $validColumns = false;
        }

        return $validColumns;
    }

    protected function isBundlesRow($row)
    {
        $isBundle = false;
        $itemsRow = array_values($this->_itemsColumnMaps);
        $itemSkuKey = (in_array('item_sku', $this->_importHeaderColumns)) ? array_search('item_sku', $this->_importHeaderColumns) : null;
        $itemQtyKey = (in_array('item_qty', $this->_importHeaderColumns)) ? array_search('item_qty', $this->_importHeaderColumns) : null;
        $itenSortOrderKey = (in_array('item_sort_order', $this->_importHeaderColumns)) ? array_search('item_sort_order', $this->_importHeaderColumns) : null;

        if (!$itemSkuKey && !$itemQtyKey && !$itenSortOrderKey) {
            $isBundle = true;
        } else {
            $itemSkuValue = (!is_null($itemSkuKey)) ? $row[$itemSkuKey] : '';
            $itemQtyValue = (!is_null($itemQtyKey)) ? $row[$itemQtyKey] : '';
            $itemSortOrderValue = (!is_null($itenSortOrderKey)) ? $row[$itenSortOrderKey] : '';

            if (strlen($itemSkuValue) <= 0 && strlen($itemSkuValue) <= 0 && strlen($itemSkuValue) <= 0) {
                $isBundle = true;
            }
        }

        return $isBundle;
    }

    public function saveBundlesData($data)
    {
        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        $mainTable = $this->_resources->getTableName('md_bundlediscount_bundles');
        $itemsTable = $this->_resources->getTableName('md_bundlediscount_items');

        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $connection = $this->_resources->getConnection();

        $index = 1;
        try{
            foreach ($data as $bundle) {
                $selections = isset($bundle['selections']) ? $bundle['selections'] : array();
                unset($bundle['selections']);
                $isValid = true;
                $this->_validateRowData($bundle, $index, $isValid);
                if (!$isValid) {
                    continue;
                }
                $result = 0;

                if (isset($bundle['bundle_id']) && trim($bundle['bundle_id'], '"') != '') {
                    $bundleModel = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundlediscount')->load($bundle['bundle_id']);
                }
                if (!$bundleModel->getBundleId()) {
                    unset($bundle['bundle_id']);
                    if (array_key_exists('date_from', $bundle) && ($bundle['date_from'] == '' || $bundle['date_from'] == '""')) {
                        unset($bundle['date_from']);
                    }
                    if (array_key_exists('date_to', $bundle) && ($bundle['date_to'] == '' || $bundle['date_to'] == '""')) {
                        unset($bundle['date_to']);
                    }

                    $bundleModel = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundlediscount');
                    $bundleModel->setData('discount_type', $bundle['discount_type'])
                    ->setData('name', $bundle['name'])
                    ->setData('discount_price', $bundle['discount_price'])
                    ->setData('status', $bundle['status'])
                    ->setData('exclude_base_product', $bundle['exclude_base_product'])
                    ->setData('customer_groups', $bundle['customer_groups'])
                    ->setData('store_ids', $bundle['store_ids'])
                    ->setData('sort_order', $bundle['sort_order'])
                    ->setData('product_id', $bundle['product_id'])
                    ->setData('updated_at', date('Y-m-d H:i:s'))
                    ->setData('date_from', isset($bundle['date_from']) ? date('l jS F Y', strtotime($bundle['date_from'])) : '' )
                    ->setData('date_to', isset($bundle['date_to']) ? date('l jS F Y', strtotime($bundle['date_to'])) : '')
                    ->setData('qty', $bundle['qty']);

                    $bundleModel->save();

                    $lastBundleId = $connection->fetchOne('SELECT last_insert_id()');
                } else {
                    $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $bundleModel = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundlediscount');
                    $bundleModel->setData('discount_type', $bundle['discount_type'])
                    ->setData('name', $bundle['name'])
                    ->setData('discount_price', $bundle['discount_price'])
                    ->setData('status', $bundle['status'])
                    ->setData('exclude_base_product', $bundle['exclude_base_product'])
                    ->setData('customer_groups', $bundle['customer_groups'])
                    ->setData('store_ids', $bundle['store_ids'])
                    ->setData('sort_order', $bundle['sort_order'])
                    ->setData('product_id', $bundle['product_id'])
                    ->setData('updated_at', date('Y-m-d H:i:s'))
                    ->setData('date_from', isset($bundle['date_from']) ? date('l jS F Y', strtotime($bundle['date_from'])) : '' )
                    ->setData('date_to', isset($bundle['date_to']) ? date('l jS F Y', strtotime($bundle['date_to'])) : '')
                    ->setData('qty', $bundle['qty']);

                    $bundleModel->setId($bundle['bundle_id']);
                    $bundleModel->save();

                    $lastBundleId = $bundle['bundle_id'];
                }

                foreach ($selections as $selection) {
                    $isSelectionValid = true;
                    $this->_validateRowData($selection, $index, $isSelectionValid);
                    if (!$isSelectionValid) {
                        continue;
                    }
                    $selection['bundle_id'] = $lastBundleId;
                    $itemQuery = 'SELECT `item_id` FROM `'.$itemsTable.'` WHERE `bundle_id`='.$lastBundleId.' AND `product_id`='.$selection['product_id'].' LIMIT 1';
                    $itemResult = $connection->fetchCol($itemQuery);
                    if (count($itemResult) <= 0) {
                        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $itemsModel = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundleitems');
                        $itemsModel->setData('bundle_id', $selection['bundle_id'])
                        ->setData('product_id', $selection['product_id'])
                        ->setData('qty', $selection['qty'])
                        ->setData('sort_order', $selection['sort_order']);

                        $itemsModel->save();
                    } else {
                        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                        $itemsModel = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundleitems');
                        $itemsModel->setData('bundle_id', $selection['bundle_id'])
                        ->setData('product_id', $selection['product_id'])
                        ->setData('qty', $selection['qty'])
                        ->setData('sort_order', $selection['sort_order']);

                        $itemsModel->setId($itemResult[0]);
                        $itemsModel->save();
                    }
                }
                ++$index;
            }
        } catch (\Exception $e) {

            $this->_logger->critical($e);
            throw new \Magento\Framework\Exception\LocalizedException($e->getMessage());
        }
    }

    protected function _validateRowData($data, $index, &$isValid)
    {
        if (isset($data['selections'])) {
            $selections = $data['selections'];
        } else {
            $selections = array();
        }
        unset($data['selections']);
        $dateRegx = '/^(19|20)\d\d[\-\/.](0[1-9]|1[012])[\-\/.](0[1-9]|[12][0-9]|3[01])$/';
        foreach ($data as $key => $value) {
            $value = trim($value, '"');
            switch ($key) {
                case 'bundle_id':
                    if (is_numeric($value) && $value <= 0) {
                        $isValid = false;
                        $this->_datavalidationMessages[] = __('Invalid value %1 for column %2 at row %3', $key, $value, $index);
                    }
                    break;
                case 'discount_price':
                    $value = (is_numeric($value)) ? (float) $value : $value;
                    if (!is_numeric($value) || $value <= 0) {
                        $isValid = false;

                        $this->_datavalidationMessages[] = __('Invalid value %1 for column %2 at row %3', $key, $value, $index);
                    }
                    break;
                case 'status':
                    $value = (is_numeric($value)) ? (int) $value : $value;
                    if ($value != 1 && $value != 0) {
                        $isValid = false;

                        $this->_datavalidationMessages[] = __('Invalid value %1 for column %2 at row %3', $key, $value, $index);
                    }
                    break;
                case 'exclude_base_product':
                    $value = (is_numeric($value)) ? (int) $value : $value;
                    if ($value != 1 && $value != 0) {
                        $isValid = false;

                        $this->_datavalidationMessages[] = __('Invalid value %1 for column %2 at row %3', $key, $value, $index);
                    }
                    break;
                case 'date_from':

                    if (strlen($value) > 0) {
                        $value =  date("Y-m-d", strtotime($value));
                        if (!preg_match($dateRegx, $value)) {
                            $isValid = false;

                            $this->_datavalidationMessages[] = __("Invalid value %1 for column %2 at row %3.Date should be in format of 'YYYY-mm-dd'.", $this->_bundleColumnMaps[$key], $value, $index);
                        }
                    }
                    break;
                case 'date_to':

                    if (strlen($value) > 0) {
                        $value =  date("Y-m-d", strtotime($value));
                        if (!preg_match($dateRegx, $value)) {
                            $isValid = false;
                            $this->_datavalidationMessages[] = __("Invalid value %1 for column %2 at row %3.Date should be in format of 'YYYY-mm-dd'.", $this->_bundleColumnMaps[$key], $value, $index);
                        } else {
                            $fromDate = trim($data['date_from'], '"');
                            if (preg_match($dateRegx, $fromDate)) {
                                $fromTimestamp = strtotime($fromDate);
                                $toTimestamp = strtotime($value);
                                if ($toTimestamp < $fromTimestamp) {
                                    $isValid = false;
                                    $this->_datavalidationMessages[] = __("Date values are invalid for columns '%1' and '%2' at row %3.'%4' should be less than '%5'.", $this->_bundleColumnMaps[$key], $this->_bundleColumnMaps['date_from'], $index, $this->_bundleColumnMaps[$key], $this->_bundleColumnMaps['date_from']);
                                }
                            }
                        }
                    }
                    break;
            }
        }

        foreach ($selections as $i => $selection) {
            foreach ($selection as $key => $value) {
                switch ($key) {
                    case 'product_id':
                        if (!is_numeric($value) || $value <= 0) {
                            $isValid = false;

                            $this->_datavalidationMessages[] = __('Invalid value %1 for column %2.', $key, $value);
                        }
                        break;
                    case 'qty':
                        if (!is_numeric($value) || $value <= 0) {
                            $isValid = false;

                            $this->_datavalidationMessages[] = __('Invalid value %1 for column %2.', $key, $value);
                        }
                        break;
                }
            }
        }

        return $this;
    }

    protected function _getImportRow($row, $rowNumber = 0, $headers)
    {
        if (count($row) < 4) {
            $this->_importErrors[] = __('Please correct Table Rates format in the Row #%1.', $rowNumber);

            return false;
        }
        $emailKey = array_search('email', $headers);
        $skuKey = array_search('sku', $headers);
        $qtyKey = array_search('qty', $headers);
        $priceKey = array_search('price', $headers);
        $websiteKey = array_search('website', $headers);
        // strip whitespace from the beginning and end of each row
        foreach ($row as $k => $v) {
            $row[$k] = trim($v);
        }

        $email = $row[$emailKey];
        $sku = $row[$skuKey];
        $qty = $row[$qtyKey];
        if ($websiteKey) {
            $website_id = $row[$websiteKey];
        }
        $newprice = $row[$priceKey];
        $logprice = $row[$priceKey];
        if (!is_numeric($qty)) {
            $this->_importErrors[] = __('Invalid Qty Price "%1" in the Row #%2.', $row[$qtyKey], $rowNumber);

            return false;
        } else {
            if ($qty <= 0) {
                $this->_importErrors[] = __('Qty should be greater than 0 in the Row #%1.', $rowNumber);

                return false;
            }
        }
        $matches = array();
        if (!is_numeric($newprice)) {
            preg_match('/(.*)%/', $newprice, $matches);
            if ((is_array($matches) && count($matches) <= 0) || !is_numeric($matches[1])) {
                $this->_importErrors[] = __('Invalid New Price "%1" in the Row #%2.', $row[$priceKey], $rowNumber);

                return false;
            } elseif (is_numeric($matches[1]) && ($matches[1] <= 0 || $matches[1] > 100)) {
                $this->_importErrors[] = __('Invalid New Price "%1" in the Row #%2.Percentage should be greater than 0 and less or equals than 100.', $row[$priceKey], $rowNumber);

                return false;
            }
        }

        if (!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/", $email)) {
            $this->_importErrors[] = __('Invalid email "%1" in the Row #%2.', $row[$emailKey], $rowNumber);

            return false;
        }

        if ($websiteKey) {
            $customer = $this->_customerFactory->create()->getCollection()
            ->addNameToSelect()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('group_id')
            ->addFieldToFilter('email', $email)
            ->addFieldToFilter('website_id', $website_id)
            ->getFirstItem();
        } else {
            $customer = $this->_customerFactory->create()->getCollection()
            ->addNameToSelect()
            ->addAttributeToSelect('entity_id')
            ->addAttributeToSelect('email')
            ->addAttributeToSelect('group_id')
            ->addFieldToFilter('email', $email)
            ->getFirstItem();
        }

        $customerId = $customer->getId();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product')->loadByAttribute('sku', $sku);

        if ($product->getTypeId() == 'grouped' || $product->getTypeId() == 'bundle' || $product->getTypeId() == 'configurable') {
            $this->_importErrors[] = __('%1 Products are not allowed in the row #%2.', ucfirst($product->getTypeId()), $rowNumber);

            return false;
        }

        $productName = $product->getName();
        $productId = $product->getId();
        $price = $product->getPrice();
        if (is_array($matches) && count($matches) > 0) {
            if ($product->getTypeId() != 'bundle') {
                $newprice = $product->getPrice() - ($product->getPrice() * ($matches[1] / 100));
            } else {
                if ($matches[1] < 0 || $matches[1] > 100) {
                    $this->_importErrors[] = __('Invalid New Price "%1" in the row #%2.Percentage should be greater than 0 and less or equals than 100.', $newprice, $rowNumber);

                    return false;
                } else {
                    $newprice = $matches[1];
                }
            }
        } else {
            if ($product->getTypeId() == 'bundle') {
                if ($newprice < 0 || $newprice > 100) {
                    $this->_importErrors[] = __('Invalid New Price "%1" in the row #%2.Percentage should be greater than 0 and less or equals than 100.', $newprice, $rowNumber);

                    return false;
                }
            }
        }

        return array(
            'customer_id' => $customerId,
            'customer_name' => $customer->getName(),
            'customer_email' => $email,
            'product_id' => $productId,
            'product_name' => $productName,
            'price' => $price,
            'log_price' => ($product->getTypeId() != 'bundle') ? $logprice : str_replace('%', '', $logprice),
            'new_price' => ($product->getTypeId() != 'bundle') ? $newprice : str_replace('%', '', $newprice), // New price for customer
            'qty' => $qty,
        );
    }

    public function getExportData()
    {
        $data = array();
        $this->_resources = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        $mainTable = $this->_resources->getTableName('md_bundlediscount_bundles');
        $itemsTable = $this->_resources->getTableName('md_bundlediscount_items');

        $connection = $this->_resources->getConnection();

        $select = $connection->select()->from(['o' => $mainTable]);

        $bundles = $connection->fetchAll($select);

        $data[0] = $this->_exportHeaderColumn;
        $index = 1;

        foreach ($bundles as $bundle) {
            $bunch = $this->getBlankBunch();
            foreach ($bundle as $key => $value) {
                if (in_array($key, $this->_bundleExportIgnoreColumns)) {
                    continue;
                }
                $column = $this->_bundleColumnMaps[$key];

                $bKey = array_search($column, $this->_exportHeaderColumn);
                $bunch[$bKey] = $this->getMappedValues($value, $key, 'export', true);
            }

            $data[$index] = $bunch;
            ++$index;

            $itemsQuery = $connection->select()->from(['o' => $itemsTable])->where('o.bundle_id=?', $bundle['bundle_id']);

            $items = $connection->fetchAll($itemsQuery);
            foreach ($items as $item) {
                $iBunch = $this->getBlankBunch();
                foreach ($item as $iKey => $iValue) {
                    if (in_array($iKey, $this->_itemExportIgnoreColumns)) {
                        continue;
                    }
                    $iColumn = $this->_itemsColumnMaps[$iKey];
                    $key = array_search($iColumn, $this->_exportHeaderColumn);
                    $iBunch[$key] = $this->getMappedValues($iValue, $iKey, 'export', false);
                }
                $data[$index] = $iBunch;
                ++$index;
            }
        };

        return $data;
    }

    public function getMappedValues($value, $column = null, $type = 'export', $isBundle = true, $isValid = false)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->_productModel = $objectManager->create('Magento\Catalog\Model\Product');

        $tmpArray = array();
        $returnValue = $value;
        if (!$column) {
            return $value;
        }
        if ($type == 'export') {
            $column = ($isBundle) ? $this->_bundleColumnMaps[$column] : $this->_itemsColumnMaps[$column];
        }
        $wrongValues = array();
        switch ($column) {
            case 'discount_type':
                $returnValue = ($type == 'export') ? array_search($value, $this->_discountLabels) : $this->_discountLabels[$value];
                if ($type != 'export') {
                    if ($returnValue !== 1 && $returnValue !== 0) {
                        $wrongValues[] = $value;
                    }
                }
                break;
            case 'store_ids':
                if ($type != 'export') {
                    $store_ids = explode('||', $value);
                } else {
                    $store_ids = explode(',', $value);
                }
                foreach ($store_ids as $storeId) {
                    $tmpArray[] = ($type == 'export') ? $this->_storeMaps[$storeId] : array_search($storeId, $this->_storeMaps);
                    if ($type != 'export') {
                        if (!is_numeric($tmpArray[count($tmpArray) - 1])) {
                            $wrongValues[] = $storeId;
                            array_pop($tmpArray);
                        }
                    }
                }
                if ($type != 'export') {
                    $returnValue = implode(',', $tmpArray);
                } else {
                    $returnValue = implode('||', $tmpArray);
                }
                break;
            case 'customer_groups':
                if ($type != 'export') {
                    $customer_groups = explode('||', $value);
                } else {
                    $customer_groups = explode(',', $value);
                }
                foreach ($customer_groups as $group) {
                    $tmpArray[] = ($type == 'export') ? $this->_groupsMap[$group] : array_search($group, $this->_groupsMap);
                    if ($type != 'export') {
                        if (!is_numeric($tmpArray[count($tmpArray) - 1])) {
                            $wrongValues[] = $group;
                            array_pop($tmpArray);
                        }
                    }
                }
                if ($type != 'export') {
                    $returnValue = implode(',', $tmpArray);
                } else {
                    $returnValue = implode('||', $tmpArray);
                }
                break;
            case 'base_sku':
                if($type == 'export') { 
                    $returnValue = $this->_productModel->load($value)->getSku();
                }
                else{ 
                    if($this->_productModel->getIdBySku($value)){
                        $returnValue = $this->_productModel->getIdBySku($value);
                    }else{
                        $returnValue = 0;
                        $wrongValues[] = $value;
                    }
                }
                if ($type != 'export') {
                    if (!is_numeric($returnValue)) {
                        $wrongValues[] = $value;
                    }
                }
                break;
            case 'item_sku':

                if($type == 'export') { 
                    $returnValue = $this->_productModel->load($value)->getSku();
                }
                else{ 
                    if($this->_productModel->getIdBySku($value)){
                        $returnValue = $this->_productModel->getIdBySku($value);
                    }else{
                        $returnValue = 0;
                        $wrongValues[] = $value;
                    }
                }
                if ($type != 'export') {
                    if (!is_numeric($returnValue)) {
                        $wrongValues[] = $value;
                    }
                }
                break;
            default:
                $returnValue = $value;
                break;
        }

        if ($type != 'export' && count($wrongValues) > 0) {
            $isValid = false;
            $this->_datavalidationMessages[] = __('Invalid value \'%1\' for column \'%2\' in uploaded file.', implode(',', $wrongValues), $column);
        }

        return ($type == 'export') ? $returnValue : $returnValue;
    }

    public function getBlankBunch()
    {
        return array('', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '');
    }
}
