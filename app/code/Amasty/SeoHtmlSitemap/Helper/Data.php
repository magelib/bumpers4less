<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_SeoHtmlSitemap
 */


namespace Amasty\SeoHtmlSitemap\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    const CATEGORY_TREE_TYPE                    = 1;
    const CATEGORY_LIST_TYPE                    = 2;

    // General
    const CONFIG_PAGE_TITLE_PATH                = 'amseohtmlsitemap/general/title';
    const CONFIG_META_DESCRIPTION_PATH          = 'amseohtmlsitemap/general/meta_description';
    const CONFIG_LAYOUT_PATH                    = 'amseohtmlsitemap/general/layout';
    const CONFIG_SHOW_SEARCH_FIELD              = 'amseohtmlsitemap/general/show_search_field';

    // Categories
    const CONFIG_SHOW_CATEGORIES_PATH           = 'amseohtmlsitemap/categories/show_categories';
    const CONFIG_CATEGORIES_TITLE               = 'amseohtmlsitemap/categories/categories_title';
    const CONFIG_CATEGORIES_SHOW_AS             = 'amseohtmlsitemap/categories/show_as';
    const CONFIG_CATEGORIES_COLUMN_NUMBER       = 'amseohtmlsitemap/categories/column_number';

    // Products
    const CONFIG_SHOW_PRODUCTS_PATH             = 'amseohtmlsitemap/products/show_products';
    const CONFIG_PRODUCTS_TITLE                 = 'amseohtmlsitemap/products/products_title';
    const CONFIG_PRODUCTS_COLUMN_NUMBER         = 'amseohtmlsitemap/products/column_number';
    const CONFIG_PRODUCTS_SPLIT_BY_LETTER       = 'amseohtmlsitemap/products/split_by_letter';
    const CONFIG_PRODUCTS_HIDE_OUT_OF_STOCK     = 'amseohtmlsitemap/products/hide_out_of_stock';

    // CMS Pages
    const CONFIG_SHOW_CMS_PAGES_PATH            = 'amseohtmlsitemap/cms/show_cms_pages';
    const CONFIG_CMS_PAGES_TITLE                = 'amseohtmlsitemap/cms/cms_title';
    const CONFIG_EXCLUDE_CMS_PAGES              = 'amseohtmlsitemap/cms/exclude_cms_pages';
    const CONFIG_CMS_COLUMN_NUMBER              = 'amseohtmlsitemap/cms/column_number';
    const CONFIG_EXCLUDE_CMS_PAGES_PATH         = 'amseohtmlsitemap/cms/exclude_cms_pages_values';

    // Landing Pages
    const CONFIG_SHOW_LANDING_PATH              = 'amseohtmlsitemap/landing/show_landing_pages';
    const CONFIG_LANDING_TITLE                  = 'amseohtmlsitemap/landing/landing_title';
    const CONFIG_LANDING_COLUMN_NUMBER          = 'amseohtmlsitemap/landing/column_number';

    // Additional links
    const CONFIG_LINKS_PATH                     = 'amseohtmlsitemap/additional/additional_links';
    const CONFIG_LINKS_TITLE                    = 'amseohtmlsitemap/additional/links_title';
    const CONFIG_LINKS_COLUMN_NUMBER            = 'amseohtmlsitemap/additional/column_number';

    protected $scopeConfig;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
        $this->scopeConfig = $context->getScopeConfig();
    }

    /**
     * @param $path
     * @return mixed
     */
    public function getScopeValue($path)
    {
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getPageTitle()
    {
        return trim((string)$this->getScopeValue(self::CONFIG_PAGE_TITLE_PATH));
    }

    public function getMetaDescription()
    {
        return trim((string)$this->getScopeValue(self::CONFIG_META_DESCRIPTION_PATH));
    }

    public function getLayout()
    {
        return $this->getScopeValue(self::CONFIG_LAYOUT_PATH);
    }

    public function canShowSearchField()
    {
        return (bool)$this->getScopeValue(self::CONFIG_SHOW_SEARCH_FIELD);
    }

    public function getCMSHeaderTitle()
    {
        return trim((string)$this->getScopeValue(self::CONFIG_CMS_PAGES_TITLE));
    }

    public function getCMSNumberOfColumns()
    {
        return (int)$this->getScopeValue(self::CONFIG_CMS_COLUMN_NUMBER);
    }

    public function canShowCMSPages()
    {
        return (bool)$this->getScopeValue(self::CONFIG_SHOW_CMS_PAGES_PATH);
    }

    public function getExcludeCMSPages()
    {
        $cmsPages = $this->getScopeValue(self::CONFIG_EXCLUDE_CMS_PAGES_PATH);
        return $cmsPages;
    }

    public function getCategoriesTitle()
    {
        return trim((string)$this->getScopeValue(self::CONFIG_CATEGORIES_TITLE));
    }

    public function canShowCategories()
    {
        return (bool)$this->getScopeValue(self::CONFIG_SHOW_CATEGORIES_PATH);
    }

    public function getCategoriesShowAs()
    {
        return (int)$this->getScopeValue(self::CONFIG_CATEGORIES_SHOW_AS);
    }

    public function getCategoriesNumberOfColumns()
    {
        return (int)$this->getScopeValue(self::CONFIG_CATEGORIES_COLUMN_NUMBER);
    }

    public function getProductsTitle()
    {
        return trim((string)$this->getScopeValue(self::CONFIG_PRODUCTS_TITLE));
    }

    public function canSnowProducts()
    {
        return (bool)$this->getScopeValue(self::CONFIG_SHOW_PRODUCTS_PATH);
    }

    public function getProductsNumberOfColumns()
    {
        return (int)$this->getScopeValue(self::CONFIG_PRODUCTS_COLUMN_NUMBER);
    }

    public function getProductsSplitByLetter()
    {
        return (bool)$this->getScopeValue(self::CONFIG_PRODUCTS_SPLIT_BY_LETTER);
    }

    public function getProductsHideOutOfStock()
    {
        return (bool)$this->getScopeValue(self::CONFIG_PRODUCTS_HIDE_OUT_OF_STOCK);
    }

    public function getLandingTitle()
    {
        return trim((string)$this->getScopeValue(self::CONFIG_LANDING_TITLE));
    }

    public function canShowLandingPages()
    {
        return (bool)$this->getScopeValue(self::CONFIG_SHOW_LANDING_PATH);
    }

    public function getLandingNumberOfColumns()
    {
        return (int)$this->getScopeValue(self::CONFIG_LANDING_COLUMN_NUMBER);
    }

    public function getLinksTitle()
    {
        return trim((string)$this->getScopeValue(self::CONFIG_LINKS_TITLE));
    }

    public function getAdditionalLinks()
    {
        return (string)$this->getScopeValue(self::CONFIG_LINKS_PATH);
    }

    public function getLinksNumberOfColumns()
    {
        return (int)$this->getScopeValue(self::CONFIG_LINKS_COLUMN_NUMBER);
    }

}