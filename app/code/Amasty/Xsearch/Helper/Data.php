<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Helper;

use Magento\Store\Model\ScopeInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MODULE_NAME = 'amasty_xsearch/';
    const XML_PATH_TEMPLATE_CATEGORY_POSITION = 'category/position';
    const XML_PATH_TEMPLATE_PRODUCT_POSITION = 'product/position';
    const XML_PATH_TEMPLATE_PAGE_POSITION = 'page/position';
    const XML_PATH_TEMPLATE_POPULAR_SEARCHES_POSITION = 'popular_searches/position';
    const XML_PATH_TEMPLATE_RECENT_SEARCHES_POSITION = 'recent_searches/position';

    const XML_PATH_TEMPLATE_CATEGORY_ENABLED = 'category/enabled';
    const XML_PATH_TEMPLATE_PRODUCT_ENABLED = 'product/enabled';
    const XML_PATH_TEMPLATE_PAGE_ENABLED = 'page/enabled';
    const XML_PATH_TEMPLATE_POPULAR_SEARCHES_ENABLED = 'popular_searches/enabled';
    const XML_PATH_TEMPLATE_RECENT_SEARCHES_ENABLED = 'recent_searches/enabled';

    /**
     * @var \Magento\Catalog\Model\Config
     */
    private $configAttribute;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    private $collection;

    public function __construct(
        \Magento\Catalog\Model\Config $configAttribute,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection $collection,
        \Magento\Framework\App\Helper\Context $context
    ) {
        parent::__construct($context);
        $this->configAttribute = $configAttribute;
        $this->collection = $collection;
    }

    public function getModuleConfig($path)
    {
        return $this->scopeConfig->getValue(self::MODULE_NAME . $path, ScopeInterface::SCOPE_STORE);
    }

    public function highlight($text, $query)
    {
        preg_match_all('~\w+~', $query, $m);

        if (!$m) {
            return $text;
        }

        $re = '/(' . implode('|', $m[0]) . ')/iu';

        return preg_replace($re, '<span class="amasty-xsearch-highlight">$0</span>', $text);
    }

    protected function _pushItem($position, $block, &$html)
    {
        $position = $this->getModuleConfig($position);
        while (isset($html[$position])) {
            $position++;
        }
        $currentHtml = $block->toHtml();
        $this->replaceVarisbles($currentHtml);
        $html[$position] = $currentHtml;
    }

    protected function replaceVarisbles(&$currentHtml)
    {
        $currentHtml = preg_replace('@\{{(.+?)\}}@', '', $currentHtml);
    }

    public function getBlocksHtml(\Magento\Framework\View\Layout $layout)
    {
        $html = [];

        if ($this->getModuleConfig(self::XML_PATH_TEMPLATE_CATEGORY_ENABLED)) {
            $this->_pushItem(
                self::XML_PATH_TEMPLATE_CATEGORY_POSITION,
                $layout->createBlock('Amasty\Xsearch\Block\Category', 'amasty.xsearch.category'),
                $html
            );
        }

        if ($this->getModuleConfig(self::XML_PATH_TEMPLATE_PRODUCT_ENABLED)) {
            $this->_pushItem(
                self::XML_PATH_TEMPLATE_PRODUCT_POSITION,
                $layout->createBlock('Amasty\Xsearch\Block\Product', 'amasty.xsearch.product'),
                $html
            );
        }

        if ($this->getModuleConfig(self::XML_PATH_TEMPLATE_PAGE_ENABLED)) {
            $this->_pushItem(
                self::XML_PATH_TEMPLATE_PAGE_POSITION,
                $layout->createBlock('Amasty\Xsearch\Block\Page', 'amasty.xsearch.page'),
                $html
            );
        }

        if ($this->getModuleConfig(self::XML_PATH_TEMPLATE_POPULAR_SEARCHES_ENABLED)) {
            $this->_pushItem(
                self::XML_PATH_TEMPLATE_POPULAR_SEARCHES_POSITION,
                $layout->createBlock('Amasty\Xsearch\Block\Search\Popular', 'amasty.xsearch.search.popular'),
                $html
            );
        }

        if ($this->getModuleConfig(self::XML_PATH_TEMPLATE_RECENT_SEARCHES_ENABLED)) {
            $this->_pushItem(
                self::XML_PATH_TEMPLATE_RECENT_SEARCHES_POSITION,
                $layout->createBlock('Amasty\Xsearch\Block\Search\Recent', 'amasty.xsearch.search.recent'),
                $html
            );
        }

        ksort($html);

        return implode('', $html);
    }

    /**
     * @param string $requiredData
     * @return array
     */
    public function getProductAttributes($requiredData = '')
    {
        if ($requiredData == 'is_searchable') {
            $attributeNames = [];
            foreach ($this->collection->addIsSearchableFilter()->getItems() as $attribute) {
                $attributeNames[] = $attribute->getAttributeCode();
            }

            return $attributeNames;
        } else {
            return $this->collection->getItems();
        }
    }
}
