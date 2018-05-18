<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product;

use Magento\Framework\Stdlib\DateTime;

class CategoryProcessor extends \Magento\CatalogImportExport\Model\Import\Product\CategoryProcessor
{

    protected $generateUrl;

    protected $resource;

    protected $storeId;

    protected $attributes = [
        'name',
        'is_active',
        'include_in_menu',
        'url_key',
        'url_path'
    ];

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    public function __construct(
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryColFactory,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
    ) {
        $this->localeDate = $localeDate;
        parent::__construct($categoryColFactory, $categoryFactory);
    }

    /**
     * @param $rowData
     * @return array
     */
    public function getRowCategories($rowData, $separator)
    {
        $categoryIds = [];
        if (isset($rowData[\Firebear\ImportExport\Model\Import\Product::COL_CATEGORY])
            && $rowData[\Firebear\ImportExport\Model\Import\Product::COL_CATEGORY]) {
            if (!empty($rowData[\Firebear\ImportExport\Model\Import\Product::COL_CATEGORY])) {
                $catData = explode(
                    $separator,
                    $rowData[\Firebear\ImportExport\Model\Import\Product::COL_CATEGORY]
                );
                foreach ($catData as $cData) {
                    if ($cData == '/') {
                        continue;
                    }
                    $secondCategory = null;
                    if (is_numeric($cData)) {
                        $collectionId = $this->categoryColFactory->create()->addFieldToFilter('entity_id', $cData);
                        if ($collectionId->getSize()) {
                            $secondCategory = $collectionId->getFirstItem()->getId();
                        }
                    } else {
                        $collection = $this->categoryColFactory->create()->addFieldToFilter('path', $cData);
                        if ($collection->getSize()) {
                            $secondCategory = $cData;
                        }
                    }
                    if (empty($secondCategory)) {
                        try {
                            $secondCategory = $this->upsertCategory($cData);
                        } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                            $this->addFailedCategory($cData, $e);
                        }
                    }
                    $categoryIds[] = $secondCategory;

                }
            }
        }

        return $categoryIds;
    }

    /**
     * @param string $name
     * @param int $parentId
     * @return null
     */
    protected function createCategory($name, $parentId)
    {
        /** @var \Magento\Catalog\Model\Category $category */
        $category = $this->categoryFactory->create();
        if (!($parentCategory = $this->getCategoryById($parentId))) {
            $parentCategory = $this->categoryFactory->create()->load($parentId);
        }

        $entityRowsIn = [
            'attribute_set_id' => $category->getDefaultAttributeSetId(),
            'parent_id' => $parentId,
            'created_at' => $this->localeDate->date()->format(DateTime::DATETIME_PHP_FORMAT),
            'updated_at' => $this->localeDate->date()->format(DateTime::DATETIME_PHP_FORMAT),
            'path' => $parentCategory->getPath(),
            'level' => $parentCategory->getLevel() + 1,
            'name' => $name
        ];
        $id = $this->saveCategoryEntity(
            $entityRowsIn
        );

        return $id;
    }

    /**
     * @param string $category
     * @param \Magento\Framework\Exception\AlreadyExistsException $exception
     * @return $this
     */
    private function addFailedCategory($category, $exception)
    {
        $this->failedCategories[] =
            [
                'category' => $category,
                'exception' => $exception,
            ];
        return $this;
    }

    protected function checkUrlKeyDuplicates($url, $category, $number)
    {
        if ($this->getGenerateUrl()) {

            $resource = $this->getResource();

            $select = $resource->getConnection()->select()->from(
                ['url_rewrite' => $resource->getTable('url_rewrite')],
                ['request_path', 'store_id']
            )->where('request_path LIKE "%' . $url . '.html"');
            $urlKeyDuplicates = $resource->getConnection()->fetchAssoc(
                $select
            );
            if (count($urlKeyDuplicates) > 0) {
                $url = $this->checkUrlKeyDuplicates($category->formatUrlKey($category->getName()) . "-" . $number,
                    $category, $number + 1);
            }
        }

        return $url;
    }

    public function setGeneratUrl($number)
    {
        $this->generateUrl = $number;
    }

    public function getGenerateUrl()
    {
        return $this->generateUrl;
    }

    public function setResource($resource)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    public function getStoreId()
    {
        return $this->storeId;
    }

    public function saveCategoryEntity(array $entityRowsIn)
    {
        static $entityTable = null;
        $category = $this->categoryFactory->create();
        $resource = $category->getResource();

        if (!$entityTable) {
            $entityTable = $resource->getTable('catalog_category_entity');
        }

        $connection = $resource->getConnection();
        if ($entityRowsIn) {
            $name = $entityRowsIn['name'];
            unset($entityRowsIn['name']);
            $connection->insertMultiple($entityTable, $entityRowsIn);
            $id = $connection->lastInsertId($entityTable);
            $entityRowsUp = [
                'path' => $entityRowsIn['path'] . "/" . $id,
                'updated_at' => $this->localeDate->date()->format(DateTime::DATETIME_PHP_FORMAT),
                'entity_id' => $id
            ];
            $connection->insertOnDuplicate($entityTable, $entityRowsUp, ['updated_at', 'path']);
            $attributes = [];
            foreach ($this->attributes as $attr) {
                $attribute = $resource->getAttribute($attr);
                $attrId = $attribute->getId();
                $attrTable = $attribute->getBackend()->getTable();
                $storeIds = [0];
                if (!$this->getStoreId()) {
                    $storeIds[] = $this->getStoreId();
                }
                $attrValue = null;
                if ($attr == 'name') {
                    $attrValue = $name;
                }
                if (in_array($attr, ['is_active', 'include_in_menu'])) {
                    $attrValue = true;
                }
                if ($attr == 'url_key') {
                    $urlKey = $this->checkUrlKeyDuplicates($category->formatUrlKey($name),
                        $category,
                        0
                    );
                    $attrValue = $urlKey;
                }
                if ($attr == 'url_path') {
                    $parentCategory = $this->categoryFactory->create()->load($entityRowsIn['parent_id']);
                    $path = $parentCategory->getUrlPath();
                    $attrValue = $path === '' ? $urlKey : $path . '/' . $urlKey;
                }
                foreach ($storeIds as $storeId) {
                    if (!empty($attrValue)) {
                        $attributes[$attrTable][$id][$attrId][$storeId] = $attrValue;
                    }
                }
                if (!empty($attributes)) {
                    $this->saveCategoryAttributes(
                        $attributes
                    );
                }
            }
            return $id;
        }

        return null;
    }

    protected function saveCategoryAttributes(array $attributesData)
    {
        $category = $this->categoryFactory->create();
        $resource = $category->getResource();
        $connection = $resource->getConnection();
        $metadataPool = \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Magento\Framework\EntityManager\MetadataPool::class);
        $linkFieldId = $metadataPool
            ->getMetadata(\Magento\Catalog\Api\Data\CategoryInterface::class)
            ->getLinkField();
        foreach ($attributesData as $tableName => $idData) {
            $tableData = [];
            foreach ($idData as $id => $attributes) {
                foreach ($attributes as $attributeId => $storeValues) {
                    foreach ($storeValues as $storeId => $storeValue) {
                        $tableData[] = [
                            $linkFieldId => $id,
                            'attribute_id' => $attributeId,
                            'store_id' => $storeId,
                            'value' => $storeValue,
                        ];
                    }
                }
            }
            $connection->insertOnDuplicate($tableName, $tableData, ['value']);
        }

        return $this;
    }

}