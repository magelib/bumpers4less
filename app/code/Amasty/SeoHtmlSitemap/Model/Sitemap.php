<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_SeoHtmlSitemap
 */


namespace Amasty\SeoHtmlSitemap\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Catalog\Model\CategoryRepository;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory as PageCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Amasty\SeoHtmlSitemap\Model\ResourceModel\Page\Xlanding\CollectionFactory as LandingPageCollectionFactory;
use Magento\CatalogInventory\Helper\Stock;
use Magento\Cms\Helper\Page as CmsPageHelper;
use Amasty\SeoHtmlSitemap\Helper\LandingPage as LandingPageHelper;
use Amasty\SeoHtmlSitemap\Helper\Data as SeoSitemapHelper;

class Sitemap extends AbstractModel
{
    protected $_helper;

    protected $_cmsPageHelper;

    protected $_landingPageHelper;

    protected $_stockHelper;

    protected $_categoryRepository;

    protected $_categoryTree;

    protected $_pageCollectionFactory;

    protected $_productCollectionFactory;

    protected $_categoryCollectionFactory;

    protected $_landingPageCollectionFactory;

    protected $_pageLayoutBuilder;

    protected $_storeManager;

    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Registry $registry,
        Stock $stockHelper,
        SeoSitemapHelper $seoSitemapHelper,
        CmsPageHelper $cmsPageHelper,
        LandingPageHelper $landingPageHelper,
        CategoryRepository $categoryRepository,
        PageCollectionFactory $pageCollectionFactory,
        ProductCollectionFactory $productCollectionFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        LandingPageCollectionFactory $landingPageCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\Tree $categoryTree,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_helper               = $seoSitemapHelper;
        $this->_cmsPageHelper        = $cmsPageHelper;
        $this->_landingPageHelper    = $landingPageHelper;
        $this->_stockHelper          = $stockHelper;
        $this->_storeManager         = $storeManager;
        $this->_categoryRepository   = $categoryRepository;
        $this->_categoryTree         = $categoryTree;
        $this->_pageCollectionFactory = $pageCollectionFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_landingPageCollectionFactory = $landingPageCollectionFactory;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function getCMSPages()
    {
        $cmsPagesList = [];
        $pageCollection = $this->_getPageCollection();

        foreach ($pageCollection as $pageItem) {
            $cmsPagesList[] = [
                'title' => $pageItem->getTitle(),
                'url'   => $this->_cmsPageHelper->getPageUrl($pageItem->getId())
            ];
        }

        return $cmsPagesList;
    }

    public function getLinks()
    {
        $links     = [];
        $addLinks  = $this->_helper->getAdditionalLinks();
        $linksList = preg_split('/$\R?^/m', $addLinks);
        foreach ($linksList as $link) {
            if (strpos($link, ',') === false) {
                continue;
            }

            list($linkText, $linkUrl) = explode(',', trim($link), 2);
            if (empty($linkText) || empty($linkUrl)) {
                continue;
            }

            $links[] = [
                'title' => htmlspecialchars(trim($linkText)),
                'url'   => htmlspecialchars(trim($linkUrl))
            ];
        }

        return $links;
    }

    public function getLandingPages()
    {
        if (!$this->_helper->isModuleOutputEnabled('Amasty_Xlanding')) {
            return [];
        }

        $landingPagesCollection = $this->_getLandingPageCollection();

        $landingPagesList = [];
        foreach ($landingPagesCollection as $pageItem) {
            if ($pageItem->getTitle()) {
                $landingPagesList[] = [
                    'title' => $pageItem->getTitle(),
                    'url'   => $this->_landingPageHelper->getPageUrl($pageItem->getId())
                ];
            }
        }

        return $landingPagesList;
    }

    public function getProducts()
    {
        $productCollection = $this->_getProductCollection();

        if ($this->_helper->getProductsSplitByLetter()) {
            $letterGroups = [];
            foreach ($productCollection as $product) {
                $letter = strtoupper(substr($product->getName(), 0, 1));
                if (is_numeric($letter) || $letter == ' ') {
                    $letter = '#';
                }

                $letterGroups[$letter]['letter'] = $letter;
                $letterGroups[$letter]['items'][] = $product;
            }

            return $letterGroups;
        }

        return $productCollection;
    }

    public function getCategories()
    {
        $parentId = $this->_storeManager->getStore()->getRootCategoryId();

        if ($this->_helper->getCategoriesShowAs() == SeoSitemapHelper::CATEGORY_LIST_TYPE) {
            $catIds = $this->_getChildCategoryIds($parentId);

            $categoryList = $this->_categoryCollectionFactory->create()->addIdFilter($catIds)->addAttributeToSelect('*');

            return $categoryList;
        }

        $categoryCollection = $this->_getCategoryCollection();

        $tree = $this->_categoryTree->load();

        $root = $tree->getNodeById($parentId);

        if ($root && $root->getId() == 1) {
            $root->setName(__('Root'));
        }

        $tree->addCollectionData($categoryCollection, true);

        return $this->_nodeToArray($root);
    }

    protected function _getExcludeCMSPages()
    {
        $excludeCMSPages       = [];
        $excludeCMSPagesConfig = $this->_helper->getExcludeCMSPages();
        if ($excludeCMSPagesConfig !== null) {
            $excludeCMSPagesConfigList = explode(",", $excludeCMSPagesConfig);
            foreach ($excludeCMSPagesConfigList as $item) {
                $excludeCMSPages[] = trim($item);
            }
        }

        return $excludeCMSPages;
    }

    protected function _getPageCollection()
    {
        $excludeCMSPages = $this->_getExcludeCMSPages();
        $collection = $this->_pageCollectionFactory->create();

        if (!empty($excludeCMSPages)) {
            $collection->addFilter('identifier', ['nin' => $excludeCMSPages], 'public');
        }
        $collection->setOrder('title', 'ASC');
        $collection->addFilter('is_active', '1');
        $collection->addStoreFilter($this->_storeManager->getStore()->getId());

        return $collection;
    }

    protected function _getLandingPageCollection()
    {
        $collection = $this->_landingPageCollectionFactory->create();

        $collection->addFilter('is_active', '1');
        $collection->addStoreFilter($this->_storeManager->getStore()->getId());

        return $collection;
    }

    protected function _getProductCollection()
    {
        $collection = $this->_productCollectionFactory->create();

        $collection->addAttributeToSelect(['name', 'url_key', 'thumbnail', 'thumbnail_label', 'url_path', 'image']);
        $collection->addStoreFilter($this->_storeManager->getStore()->getId());
        $collection->addUrlRewrite();

        $collection->addAttributeToFilter('status', 1);
        $collection->addAttributeToFilter('visibility', [
            'in' => [
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_IN_CATALOG,
                \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH
            ]
        ]);

        $collection->addAttributeToSort('name', 'ASC');

        if ($this->_helper->getProductsHideOutOfStock()) {
            $this->_stockHelper->addInStockFilterToCollection($collection);
        }

        return $collection;
    }

    protected function _getCategoryCollection()
    {
        $collection = $this->_categoryCollectionFactory->create();

        $rootId = $this->_storeManager->getStore()->getRootCategoryId();

        $collection->addAttributeToSelect(['url_key', 'name', 'thumbnail', 'image']);
        $collection->addFieldToFilter('path', ['like'=> "1/$rootId/%"]);
        $collection->addAttributeToFilter('level', ['gt' => 1]);
        $collection->addAttributeToFilter('is_active', 1);
        $collection->addUrlRewriteToResult();

        return $collection;
    }

    protected function _getChildCategoryIds($parentId)
    {
        $category = $this->_categoryRepository->get($parentId);
        $childIds = $category->getAllChildren();
        $catIds = explode(',', $childIds);

        if (($key = array_search($parentId, $catIds)) !== false) {
            unset($catIds[$key]);
        }

        return $catIds;
    }

    protected function _nodeToArray(\Magento\Framework\Data\Tree\Node $node)
    {
        $result = [];
        $result['category_id'] = $node->getId();
        $result['name']        = $node->getName();
        $result['level']       = $node->getLevel();
        $result['url']         = $node->getRequestPath();
        $result['children']    = [];

        foreach ($node->getChildren() as $child) {
            $result['children'][] = $this->_nodeToArray($child);
        }

        return $result;
    }
}