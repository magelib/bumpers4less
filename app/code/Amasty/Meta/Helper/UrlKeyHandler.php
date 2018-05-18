<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Meta
 */

namespace Amasty\Meta\Helper;

class UrlKeyHandler extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;
    protected $_tablePrefix;
    protected $_productTypeId;
    protected $_urlPathId;
    protected $_urlKeyId;
    protected $_pageSize = 100;

    /**
     * Base product target path.
     */
    const BASE_PRODUCT_TARGET_PATH  = 'catalog/product/view/id/%d';
    /**
     * Base path for product in category
     */
    const BASE_PRODUCT_CATEGORY_TARGET_PATH = 'catalog/product/view/id/%d/category/%d';

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /**
     * Product factory
     *
     * @var \Magento\Catalog\Model\ProductFactory
     */
    protected $_productFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var Data
     */
    protected $_helperData;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\App\ResourceConnection $resourceConnection,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Amasty\Meta\Helper\Data $helperData
    ) {
        $this->resource = $resourceConnection;
        $this->_productFactory = $productFactory;
        $this->_storeManager = $storeManager;
        $this->_helperData = $helperData;
        parent::__construct($context);
        $this->_construct();
    }

    public function _construct()
    {
        $this->_connection = $this->resource->getConnection('core_write');

        //product type id
        $select = $this->_connection->select()->from($this->resource->getTableName('eav_entity_type'))
            ->where("entity_type_code = 'catalog_product'");
        $this->_productTypeId = $this->_connection->fetchOne($select);

        //url path id
        $select = $this->_connection->select()->from($this->resource->getTableName('eav_attribute'))
            ->where("entity_type_id = $this->_productTypeId AND (attribute_code = 'url_path')");
        $this->_urlPathId = $this->_connection->fetchOne($select);

        //url key id
        $select = $this->_connection->select()->from($this->resource->getTableName('eav_attribute'))
            ->where("entity_type_id = $this->_productTypeId AND (attribute_code = 'url_key')");
        $this->_urlKeyId = $this->_connection->fetchOne($select);
    }

    /**
     * @param $urlKeyTemplate
     * @param array $storeIds
     * @param int $page
     */
    public function process($urlKeyTemplate, $storeIds = [], $page = 1)
    {
        $storeEntities = $this->_getStores($storeIds);

        foreach ($storeEntities as $store) {

            $products = $this->_productFactory->create()->getCollection()
                ->addAttributeToSelect('*')
                ->setCurPage($page)
                ->setPageSize($this->getPageSize())
                ->setStore($store);

            foreach ($products as $product) {
                $this->processProduct($product, $store, $urlKeyTemplate);
            }
        }
    }

    public function estimate($storeIds = [])
    {
        $products = $this->_productFactory->create()->getCollection();

        if ($storeIds) {
            $products->setStore($storeIds[0]);
        }

        return $products->getSize();
    }

    protected function _getStores($storeIds)
    {
        $storeEntities =$this->_storeManager->getStores(true, true);
        if (! empty($storeIds)) {
            foreach ($storeEntities as $key => $storeEntity) {
                if (! in_array($key, $storeIds)) {
                    unset($storeEntities[$key]);
                }
            }
        }

        return $storeEntities;
    }

    /**
     * @param        $product
     * @param        $store
     * @param string $urlKeyTemplate
     */
    public function processProduct($product, $store, $urlKeyTemplate = '')
    {
        if (empty($urlKeyTemplate)) {
            $urlKeyTemplate = trim($this->scopeConfig->getValue(
                'ammeta/product/url_template',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
                $store->getCode()
            ));
        }

        if (empty($urlKeyTemplate)) {
            return;
        }

        $helper = $this->_helperData;

        $product->setStoreId($store->getId());
        $urlKey = $helper->cleanEntityToCollection()
            ->addEntityToCollection($product)
            ->parse($urlKeyTemplate, true);

        $urlKey = $product->formatUrlKey($urlKey);

        //update url_key
        $this->_updateUrlKey($product, $store->getId(), $urlKey);
        //update url_path

        $urlSuffix = $this->scopeConfig->getValue(
            'catalog/seo/product_url_suffix',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        $this->_updateUrlPath($product, $store->getId(), $urlKey, $urlSuffix);

        $product->setUrlKey($urlKey);
    }

    /**
     * @param $product
     * @param $storeId
     * @param $urlKey
     * @param string $urlSuffix
     */
    protected function _updateUrlKey($product, $storeId, $urlKey, $urlSuffix = '')
    {
        $this->_updateAttribute($this->_urlKeyId, $product, $storeId, $urlKey, $urlSuffix);
    }

    /**
     * @param $product
     * @param $storeId
     * @param $urlKey
     * @param string $urlSuffix
     */
    protected function _updateUrlPath($product, $storeId, $urlKey, $urlSuffix = '')
    {
        $this->_updateAttribute($this->_urlPathId, $product, $storeId, $urlKey, $urlSuffix);
    }

    /**
     * @param $attributeId
     * @param $product
     * @param $storeId
     * @param $urlKey
     * @param $urlSuffix
     */
    protected function _updateAttribute($attributeId, $product, $storeId, $urlKey, $urlSuffix)
    {
        $table  = 'catalog_product_entity_varchar';

        $select = $this->_connection->select()->from($this->resource->getTableName($table))
            ->where("attribute_id = $this->_urlKeyId AND entity_id = {$product->getId()} AND store_id = {$storeId}");
        $row    = $this->_connection->fetchRow($select);

        if ($row) {
            $this->_connection->update($this->resource->getTableName($table),
                ['value' => $urlKey . $urlSuffix],
                "attribute_id = $attributeId AND entity_id = {$product->getId()} AND store_id = {$storeId}");
        } else {
            $data = [
                'attribute_id'   => $attributeId,
                'entity_id'      => $product->getId(),
                'store_id'       => $storeId,
                'value'          => $urlKey . $urlSuffix
            ];
            $this->_connection->insert($this->resource->getTableName($table), $data);
        }
    }

    public function getPageSize()
    {
        return $this->_pageSize;
    }

    /**
     * @param int $productId
     * @param int|null $categoryId
     * @return string
     */
    protected function _getProductTargetPath($productId, $categoryId = null)
    {
        return empty($categoryId) ?
            sprintf(self::BASE_PRODUCT_TARGET_PATH, $productId) :
            sprintf(self::BASE_PRODUCT_CATEGORY_TARGET_PATH, $productId, $categoryId);
    }
}
