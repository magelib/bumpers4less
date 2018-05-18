<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Block;

use Amasty\Xsearch\Helper\Data as Helper;

class Jsinit extends \Magento\Framework\View\Element\Template
{
    const XML_PATH_TEMPLATE_WIDTH = 'general/width';
    const XML_PATH_TEMPLATE_MIN_CHARS = 'general/min_chars';

    const XML_PATH_RECENT_SEARCHES_FIRST_CLICK = 'recent_searches/first_click';
    const XML_PATH_POPULAR_SEARCHES_FIRST_CLICK = 'popular_searches/first_click';

    const XML_PATH_LAYOUT_ENABLED = 'layout/enabled';
    const XML_PATH_LAYOUT_BORDER = 'layout/border';
    const XML_PATH_LAYOUT_HOVER = 'layout/hover';
    const XML_PATH_LAYOUT_HIGHLIGHT = 'layout/highlight';
    const XML_PATH_LAYOUT_BACKGROUND = 'layout/background';
    const XML_PATH_LAYOUT_TEXT = 'layout/text';
    const XML_PATH_LAYOUT_HOVER_TEXT = 'layout/hover_text';

    /** @var \Amasty\Xsearch\Block\Search\Recent */
    private $recentSearch;

    /** @var \Amasty\Xsearch\Block\Search\Popular */
    private $popularSearch;

    /** @var \Magento\Framework\Url\Helper\Data */
    private $urlHelper;
    
    /**
     * @var Helper
     */
    private $helper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        Helper $helper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->urlHelper = $urlHelper;
        $this->helper = $helper;
    }

    public function getWidth()
    {
        return $this->helper->getModuleConfig(self::XML_PATH_TEMPLATE_WIDTH);
    }

    public function getMinChars()
    {
        return $this->helper->getModuleConfig(self::XML_PATH_TEMPLATE_MIN_CHARS);
    }

    public function getLayoutEnabled()
    {
        return $this->helper->getModuleConfig(self::XML_PATH_LAYOUT_ENABLED);
    }

    public function getLayoutBorder()
    {
        return $this->helper->getModuleConfig(self::XML_PATH_LAYOUT_BORDER);
    }

    public function getLayoutHover()
    {
        return $this->helper->getModuleConfig(self::XML_PATH_LAYOUT_HOVER);
    }

    public function getLayoutHighlight()
    {
        return $this->helper->getModuleConfig(self::XML_PATH_LAYOUT_HIGHLIGHT);
    }

    public function getLayoutBackground()
    {
        return $this->helper->getModuleConfig(self::XML_PATH_LAYOUT_BACKGROUND);
    }

    public function getLayoutText()
    {
        return $this->helper->getModuleConfig(self::XML_PATH_LAYOUT_TEXT);
    }

    public function getLayoutHoverText()
    {
        return $this->helper->getModuleConfig(self::XML_PATH_LAYOUT_HOVER_TEXT);
    }

    /**
     * @return string
     */
    public function getCurrentUrlEncoded()
    {
        return $this->urlHelper->getEncodedUrl();
    }

    /**
     * @return bool
     */
    public function isShowRecentPreload()
    {
        return $this->getShowRecentByFirstClick()
            && $this->getRecentSearch()->getLoadedSearchCollection()->getSize() > 0;
    }

    /**
     * @return bool
     */
    public function isShowPopularPreload()
    {
        return $this->getShowPopularByFirstClick()
            && $this->getPopularSearch()->getLoadedSearchCollection()->getSize() > 0;
    }

    /**
     * @return string
     */
    public function getPreload()
    {
        $recentHtml = '';
        $popularHtml = '';
        if ($this->isShowRecentPreload()) {
            $recentHtml .= $this->getRecentSearch()->toHtml();
        }

        if ($this->isShowPopularPreload()) {
            $popularHtml .= $this->getPopularSearch()->toHtml();
        }

        $recentPos = $this->helper->getModuleConfig(Helper::XML_PATH_TEMPLATE_RECENT_SEARCHES_POSITION);
        $popularPos = $this->helper->getModuleConfig(Helper::XML_PATH_TEMPLATE_POPULAR_SEARCHES_POSITION);
        if ($recentPos < $popularPos) {
            return $recentHtml . $popularHtml;
        }

        return $popularHtml . $recentHtml;
    }

    /**
     * @return \Amasty\Xsearch\Block\Search\Recent
     */
    private function getRecentSearch()
    {
        if (!$this->recentSearch) {
            $this->recentSearch = $this->_layout
                ->createBlock('Amasty\Xsearch\Block\Search\Recent', 'amasty.xsearch.search.recent');
        }

        return $this->recentSearch;
    }

    /**
     * @return \Amasty\Xsearch\Block\Search\Popular
     */
    private function getPopularSearch()
    {
        if (!$this->popularSearch) {
            $this->popularSearch = $this->_layout
                ->createBlock('Amasty\Xsearch\Block\Search\Popular', 'amasty.xsearch.search.popular');
        }

        return $this->popularSearch;
    }

    /**
     * @return bool
     */
    private function getShowRecentByFirstClick()
    {
        return (bool) $this->helper->getModuleConfig(self::XML_PATH_RECENT_SEARCHES_FIRST_CLICK);
    }

    /**
     * @return bool
     */
    private function getShowPopularByFirstClick()
    {
        return (bool) $this->helper->getModuleConfig(self::XML_PATH_POPULAR_SEARCHES_FIRST_CLICK);
    }

}
