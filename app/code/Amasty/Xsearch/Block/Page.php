<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */

/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Amasty\Xsearch\Block;

use Amasty\Xsearch\Controller\RegistryConstants;
use Magento\Framework\App\Filesystem\DirectoryList;

class Page extends \Magento\Framework\View\Element\Template
{
    const XML_PATH_TEMPLATE_NAME_LENGTH = 'page/name_length';
    const XML_PATH_TEMPLATE_DESC_LENGTH = 'page/desc_length';
    const XML_PATH_TEMPLATE_LIMIT = 'page/limit';
    const XML_PATH_TEMPLATE_TITLE = 'page/title';

    protected $_template = 'page.phtml';
    protected $_pageCollection;

    private $coreRegistry;
    
    /**
     * @var \Amasty\Xsearch\Helper\Data
     */
    private $xsearchHelper;
    
    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    private $string;
    
    /**
     * @var \Amasty\Xsearch\Model\ResourceModel\Page\Fulltext\CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Amasty\Xsearch\Model\ResourceModel\Page\Fulltext\CollectionFactory $collectionFactory,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Amasty\Xsearch\Helper\Data $xsearchHelper,
        array $data = []
    ) {
        $this->coreRegistry = $context->getRegistry();
        $this->xsearchHelper = $xsearchHelper;
        $this->string = $string;
        $this->collectionFactory = $collectionFactory;

        parent::__construct(
            $context,
            $data
        );
    }

    protected function _getPageCollection()
    {
        if ($this->_pageCollection === null) {
            $this->_pageCollection = $this->collectionFactory->create();
        }

        return $this->_pageCollection;
    }

    protected function _getQuery()
    {
        return $this->coreRegistry->registry(RegistryConstants::CURRENT_AMASTY_XSEARCH_QUERY);
    }

    protected function _beforeToHtml()
    {
        $this->_getPageCollection()
            ->addSearchFilter($this->_getQuery()->getQueryText())
            ->addStoreFilter($this->_storeManager->getStore())
            ->addFieldToFilter('is_active', 1)
            ->setPageSize($this->getLimit())
            ->setCurPage(1);

        $this->_getPageCollection()->load();

        return parent::_beforeToHtml();
    }

    public function getLoadedPageCollection()
    {
        return $this->_getPageCollection();
    }

    public function highlight($text)
    {
        return $this->xsearchHelper->highlight($text, $this->_getQuery()->getQueryText());
    }

    public function getName($_page)
    {
        $nameLength = $this->getNameLength();

        $_nameStripped = $this->stripTags($_page->getTitle(), null, true);

        $text =
            $this->string->strlen($_nameStripped) > $nameLength ?
            $this->string->substr($_nameStripped, 0, $this->getNameLength()) . '...'
            : $_nameStripped;

        return $this->highlight($text);
    }

    public function getTitle()
    {
        return $this->xsearchHelper->getModuleConfig(self::XML_PATH_TEMPLATE_TITLE);
    }

    public function showDescription($_page)
    {
        return $this->string->strlen($_page->getContent()) > 0;
    }

    public function getDescription($_page)
    {
        $descLength = $this->getDescLength();

        $_descStripped = $this->stripTags($_page->getContent(), null, true);

        $text =
            $this->string->strlen($_descStripped) > $descLength ?
            $this->string->substr($_descStripped, 0, $this->getDescLength()) . '...'
            : $_descStripped;

        return $this->highlight($text);
    }

    public function getLimit()
    {
        return $this->xsearchHelper->getModuleConfig(self::XML_PATH_TEMPLATE_LIMIT);
    }

    public function getNameLength()
    {
        return $this->xsearchHelper->getModuleConfig(self::XML_PATH_TEMPLATE_NAME_LENGTH);
    }

    public function getDescLength()
    {
        return $this->xsearchHelper->getModuleConfig(self::XML_PATH_TEMPLATE_DESC_LENGTH);
    }
}
