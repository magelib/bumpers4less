<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_SeoHtmlSitemap
 */


namespace Amasty\SeoHtmlSitemap\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Amasty\SeoHtmlSitemap\Helper\Data as SitemapHelper;
use Amasty\SeoHtmlSitemap\Helper\Renderer as RendererHelper;
use Amasty\SeoHtmlSitemap\Model\SitemapFactory;

class Sitemap extends Template
{
    protected $_helper;

    protected $_helperRenderer;

    protected $_modelSitemapFactory;

    protected $_sitemapData;

    public function __construct(
        Context $context,
        SitemapHelper $helper,
        RendererHelper $helperRenderer,
        SitemapFactory $sitemapFactory,
        array $data = []
    ) {
        $this->_helper = $helper;
        $this->_helperRenderer = $helperRenderer;
        $this->_modelSitemapFactory = $sitemapFactory;
        parent::__construct($context, $data);
    }

    protected function _beforeToHtml()
    {
        $sitemapDataModel = $this->_modelSitemapFactory->create();
        $this->_sitemapData = [
            'links'         => $sitemapDataModel->getLinks(),
            'linksTitle'    => $this->_helper->getLinksTitle(),
            'linksColumns'  => $this->_helper->getLinksNumberOfColumns(),
            'title'         => $this->_helper->getPageTitle(),
            'search'        => $this->_helper->canShowSearchField()
        ];

        //category collection
        if ($this->_helper->canShowCategories()) {
            $this->_sitemapData['categories']         = $sitemapDataModel->getCategories();
            $this->_sitemapData['categoriesColumns']  = $this->_helper->getCategoriesNumberOfColumns();
            $this->_sitemapData['categoriesTitle']    = $this->_helper->getCategoriesTitle();
            $this->_sitemapData['categoriesGrid']     = $this->_helper->getCategoriesShowAs();
        }

        //product collection
        if ($this->_helper->canSnowProducts()) {
            $this->_sitemapData['products']               = $sitemapDataModel->getProducts();
            $this->_sitemapData['productsLetterSplit']    = $this->_helper->getProductsSplitByLetter();
            $this->_sitemapData['productsTitle']          = $this->_helper->getProductsTitle();
            $this->_sitemapData['productsColumns']        = $this->_helper->getProductsNumberOfColumns();
        }

        //pages
        if ($this->_helper->canShowCmsPages()) {
            $this->_sitemapData['pages']              = $sitemapDataModel->getCMSPages();
            $this->_sitemapData['pagesTitle']         = $this->_helper->getCMSHeaderTitle();
            $this->_sitemapData['pagesColumns']       = $this->_helper->getCMSNumberOfColumns();
        }

        //landing pages
        if ($this->_helper->canShowLandingPages()) {
            $this->_sitemapData['landingPages']       = $sitemapDataModel->getLandingPages();
            $this->_sitemapData['landingTitle']       = $this->_helper->getLandingTitle();
            $this->_sitemapData['landingColumns']     = $this->_helper->getLandingNumberOfColumns();
        }

        $this->addData($this->_sitemapData);
        return parent::_beforeToHtml();
    }

    public function canShowSearchField()
    {
        return $this->_helper->canShowSearchField();
    }

    public function canShowCMSPages()
    {
        if (!$this->_helper->canShowCMSPages() || empty($this->getSitemapData('pages'))) {
            return false;
        }

        return true;
    }

    public function canShowLinks()
    {
        if (empty($this->getSitemapData('links'))) {
            return false;
        }

        return true;
    }

    public function canShowLandingPages()
    {
        if (!$this->_helper->isModuleOutputEnabled('Amasty_Xlanding') || !$this->_helper->canShowLandingPages()
            || empty($this->getSitemapData('landingPages'))) {
            return false;
        }

        return true;
    }

    public function canShowProducts()
    {
        if (!$this->_helper->canSnowProducts() || empty($this->getSitemapData('products'))) {
            return false;
        }

        return true;
    }

    public function canShowCategories()
    {
        if (!$this->_helper->canShowCategories() || empty($this->getSitemapData('categories'))) {
            return false;
        }

        return true;
    }

    public function getSitemapData($index = '')
    {
        if (!isset($this->_sitemapData[$index])) {
            return false;
        }

        return $this->_sitemapData[$index];
    }

    public function getProductShowType()
    {
        return ($this->_helper->getProductsSplitByLetter()) ? 'product_split' : 'product';
    }

    public function getCategoryShowType()
    {
        $type = 'categories_list';

        if ($this->_helper->getCategoriesShowAs() == SitemapHelper::CATEGORY_TREE_TYPE) {
            $type = 'categories_tree';
        }

        return $type;
    }

    public function getCategories()
    {
        $categories = $this->getSitemapData('categories');

        return ($this->isTree()) ? $categories['children'] : $categories;
    }

    public function isTree()
    {
        if ($this->_helper->getCategoriesShowAs() == SitemapHelper::CATEGORY_TREE_TYPE) {
            return true;
        }

        return false;
    }

    public function renderChunks($collection, $type, $columnSize = 1, $isTree = false)
    {
        return $this->_helperRenderer->renderArrayChunks($collection, $type, $columnSize, $isTree);
    }
}