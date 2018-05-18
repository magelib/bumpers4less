<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

/**
 * {{block class="Amasty\\Finder\\Block\\Form" block_id="finder_form" id="3"}}
 */
namespace Amasty\Finder\Block;

use Magento\Framework\View\Element\Template;

class Form extends \Magento\Framework\View\Element\Template
{
    /**
     * @var bool
     */
    protected $_isApplied = false;

    /**
     * @var \Amasty\Finder\Model\Finder
     */
    protected $_finderModel;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $jsonEncoder;

    /**
     * @var \Magento\Catalog\Model\Layer
     */
    protected $catalogLayer;

    /**
     * @var \Magento\Framework\Url\Encoder
     */
    protected $urlEncoder;

    /**
     * @var int
     */
    protected $_parentDropdownId = 0;

    protected $_request;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Framework\Url\Encoder $urlEncoder,
        array $data = []
    ) {
        $this->objectManager = $objectManager;
        $this->coreRegistry = $registry;
        $this->jsonEncoder = $jsonEncoder;
        $this->catalogLayer = $layerResolver->get();
        $this->urlEncoder = $urlEncoder;
        $this->_request = $request;
        parent::__construct($context, $data);
        $this->apply();
        //$this->setCacheLifetime(null);
    }

    /**
     * @return \Amasty\Finder\Model\Finder
     */
    public function getFinder()
    {
        if (is_null($this->_finderModel)){
            $this->_finderModel = $this->objectManager->create('Amasty\Finder\Model\Finder')
                ->load($this->getId());

        }
        return $this->_finderModel;
    }

    /**
     * @param \Amasty\Finder\Model\Dropdown $dropdown
     *
     * @return string
     */
    public function getDropdownAttributes(\Amasty\Finder\Model\Dropdown $dropdown)
    {
        $html = sprintf('name="finder[%s]" id="finder-%s--%s" data-dropdown-id="%s"',
            $dropdown->getId(), $this->getId(), $dropdown->getId(), $dropdown->getId());

        $parentValueId  = $this->getFinder()->getSavedValue($this->_parentDropdownId);
        $currentValueId = $this->getFinder()->getSavedValue($dropdown->getId());

        if ($this->_isHidden($dropdown) && (!$parentValueId) && (!$currentValueId)) {
            $html .= 'disabled="disabled"';
        }

        return $html;
    }

    /**
     * @param \Amasty\Finder\Model\Dropdown $dropdown
     *
     * @return array
     */
    public function getDropdownValues(\Amasty\Finder\Model\Dropdown $dropdown)
    {
        $values   = array();


        $parentValueId  = $this->getFinder()->getSavedValue($this->_parentDropdownId);
        $currentValueId = $this->getFinder()->getSavedValue($dropdown->getId());

        if ($this->_isHidden($dropdown) && (!$parentValueId) && (!$currentValueId)){
            return $values;
        }

        $values = $dropdown->getValues($parentValueId, $currentValueId);

        $this->_parentDropdownId = $dropdown->getId();

        return $values;
    }

    public function isButtonsVisible()
    {
        $cnt = count($this->getFinder()->getDropdowns());

        // we have just 1 dropdown. show thw button
        if (1 == $cnt) {
            return true;
        }

        $partialSearch = $this->_scopeConfig->isSetFlag(
            'amfinder/general/partial_search',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        // at least one value is selected and we allow partial search
        if ($this->getFinder()->getSavedValue('current') && $partialSearch) {
            return true;
        }

        // all values are selected.
        if (($this->getFinder()->getSavedValue('last'))){
            return true;
        }


        return false;
    }

    protected function _isHidden(\Amasty\Finder\Model\Dropdown $dropdown)
    {
        //it's not the first dropdown && value is not selected

        return ($dropdown->getPos() && !$this->getFinder()->getSavedValue($dropdown->getId()));
    }

    public function getAjaxUrl()
    {
        $isCurrentlySecure = $this->_storeManager->getStore()
            ->isCurrentlySecure();
        if ($isCurrentlySecure) {
            $url =  $this->getUrl('amfinder/index/options',array('_secure'=>true));
        } else {
            $url =  $this->getUrl('amfinder/index/options',array('_secure'=>false));
        }
        return $url;
    }


    public function getBackUrl()
    {
        //  no params
        //  category type CMS -> amfinder / amshopby
        //  cms page including homepage -> amfinder / amshopby

        //  with params
        //  amfinder/amshopby page with params -> amfinder / amshopby amshopby with params
        //  normal category page with pagams -> the same category
        //  landing page -> the same landing page
        $securedFlag = $this->_storeManager->getStore()
            ->isCurrentlySecure();
        $secured = array('_secure'=>$securedFlag);

        $customUrl = $this->getConfigValue('custom_category');

        if ($this->getFinder()->getCustomUrl()) {
            $customUrl = $this->getFinder()->getCustomUrl();
        }

        if ($customUrl) {
            $url = $this->_urlBuilder->getCurrentUrl();

            // from some different url to custom url
            if (strpos($url, $customUrl) === false)
                $url  = $this->getUrl($customUrl,$secured);

            // in other case just use the current url
            return $this->formatUrl($url);
        }

        $url = $this->getUrl('amfinder',$secured);

        /*if (Mage::helper('ambase')->isModuleActive('Amasty_Shopby'))
            $url = $url = $this->_urlBuilder->getBaseUrl($secured) . $this->_scopeConfig->getValue('amshopby/seo/key',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE);*/

        //not category page
        $category = $this->coreRegistry->registry('current_category');
        if (!$category)
            return $this->formatUrl($url);

        if($this->coreRegistry->registry('current_product')) {
            return $this->formatUrl($url);
        }

        if ($category->getDisplayMode() == \Magento\Catalog\Model\Category::DM_PAGE)
            return $this->formatUrl($url);

        $url = $this->_urlBuilder->getCurrentUrl();

        return $this->formatUrl($url);
    }

    public function getResetUrl()
    {
        if ($this->getConfigValue('reset_home') == 'current' ||
            $this->_request->getFullActionName() == 'cms_index_index') {
            return $this->formatUrl($this->_urlBuilder->getCurrentUrl());
        } else {
            return $this->getBackUrl();
        }
    }


    public function getActionUrl()
    {
        $securedFlag = $this->_storeManager->getStore()->isCurrentlySecure();
        $url = $this->getUrl('amfinder/index/search',array('_secure'=>$securedFlag));
        return $url;
    }

    protected function _toHtml()
    {
        $this->apply();

        $id = $this->getId();
        if (!$id){
            return __('Please specify the Parts Finder ID');
        }

        $finder = $this->getFinder();
        if (!$finder->getId()){
            return __('Please specify an exiting Parts Finder ID');
        }

        return parent::_toHtml();
    }

    public function apply()
    {
        if ($this->_isApplied) {
            return $this;
        }
        $this->_isApplied = true;

        $tpl = $this->getTemplate();
        if (!$tpl){
            $tpl = $this->getFinder()->getTemplate();
            if (!$tpl) {
                $tpl = 'vertical';
            }
        }
        $this->setTemplate('Amasty_Finder::' . $tpl . '.phtml');



        $finder = $this->getFinder();
        $urlParam = $this->getRequest()->getParam('find');

        // XSS disabling
        $filter = array("<", ">");
        $urlParam = str_replace ($filter, "|", $urlParam);
        $urlParam = htmlspecialchars($urlParam);

        if ($urlParam){
            $urlParam = $finder->parseUrlParam($urlParam);
            $current  = $finder->getSavedValue('current');

            if ($urlParam && ($current != $urlParam)){ // url has higher priority than session
                $dropdowns = $finder->getDropdownsByCurrent($urlParam);
                $finder->saveFilter($dropdowns, $this->getCurrentCategoryId(), [$this->getCurrentApplyUrl()]);
            }
        }

        $isUniversal = (bool) $this->getConfigValue('universal');
        $isUniversalLast = (bool) $this->getConfigValue('universal_last');

        $finder->applyFilter($this->catalogLayer, $isUniversal, $isUniversalLast);

        return $this;
    }

    public function formatUrl($url)
    {
        $securedFlag = $this->_storeManager->getStore()->isCurrentlySecure();

        if ($securedFlag)
            $url = str_replace("http://", "https://", $url);

        return $this->urlEncoder->encode($url);
    }


    public function getCurrentCategoryId()
    {
        return $this->catalogLayer->getCurrentCategory()->getId();
    }


    public function getConfigValue($path)
    {
        return $this->_scopeConfig->getValue('amfinder/general/'.$path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function getJsonConfig()
    {
        return $this->jsonEncoder->encode([
            'ajaxUrl'       => $this->getAjaxUrl(),
            'isNeedLast'    => intval($this->getConfigValue('partial_search')),
            'autoSubmit'    => intval($this->getConfigValue('auto_submit'))
        ]);
    }

    public function getCurrentApplyUrl()
    {
        $currentUrl = $this->_urlBuilder->getCurrentUrl();
        $currentUrl = explode('?', $currentUrl);
        $currentUrl = array_shift($currentUrl);
        return $currentUrl;
    }

    public function getCurrentApplyUrlEncoded()
    {
        $currentUrl = $this->getCurrentApplyUrl();
        return $this->urlEncoder->encode($currentUrl);
    }


}
