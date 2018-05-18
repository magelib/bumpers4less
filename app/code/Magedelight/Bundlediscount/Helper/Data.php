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

namespace Magedelight\Bundlediscount\Helper;

use Magento\Framework\Pricing\PriceCurrencyInterface;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    protected $_customerSession;

    /**
     * Customer Group factory.
     *
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $_customerGroupFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var \Magento\Catalog\Helper\Data
     */
    protected $_catalogData;
    protected $_optionHelper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    /**
     * Request object.
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Url\EncoderInterface
     */
    protected $urlEncoder;
    
    protected $_storeManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\App\Helper\Context $urlContext
     */
    public function __construct(
    \Magento\Framework\App\Helper\Context $context, 
    \Magento\Framework\App\Helper\Context $urlContext,
    \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_urlBuilder = $urlContext->getUrlBuilder();
        $this->_request = $urlContext->getRequest();
        $this->urlEncoder = $urlContext->getUrlEncoder();
        $this->_storeManager = $storeManager;
        parent::__construct($context);
    }
    
    public function isEnabled()
    {
        $currentUrl = $this->_storeManager->getStore()->getBaseUrl();
        $domain = $this->getDomainName($currentUrl);
        $selectedWebsites = $this->getConfig('bundlediscount/others/select_website');
        $websites = explode(',',$selectedWebsites);
        if(in_array($domain, $websites) && $this->getConfig('bundlediscount/general/enable_link') && $this->getConfig('bundlediscount/license/data'))
        {
          return true;
        }else{
          return false;
        }
    }

    public function getDomainName($domain){
        $string = '';
        
        $withTrim = str_replace(array("www.","http://","https://"),'',$domain);
        
        /* finding the first position of the slash  */
        $string = $withTrim;
        
        $slashPos = strpos($withTrim,"/",0);
        
        if($slashPos != false){
            $parts = explode("/",$withTrim);
            $string = $parts[0];
        }
        return $string;
    }

    public function getWebsites()
    {
        $websites = $this->_storeManager->getWebsites();
        $websiteUrls = array();
        foreach($websites as $website)
        {
            foreach($website->getStores() as $store){
                $wedsiteId = $website->getId();
                $storeObj = $this->_storeManager->getStore($store);
                $storeId = $storeObj->getId();
                $url = $storeObj->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
                $parsedUrl = parse_url($url);
                $websiteUrls[] = str_replace(array('www.', 'http://', 'https://'), '', $parsedUrl['host']);
            }
        }

        return $websiteUrls;
    }

    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue(
            $config_path,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
    
    public function formatPercentage($string)
    {
        preg_match('/^\d+\.\d+$/', $string, $matches);
        if (count($matches) > 0) {
            $parts = explode('.', $string);
            $firstPart = $parts[0];
            $decimalPart = $parts[1];
            $decimalDigits = str_split($decimalPart);
            if (!isset($decimalDigits[0])) {
                $decimalDigits[0] = 0;
            }
            if (!isset($decimalDigits[1])) {
                $decimalDigits[1] = 0;
            }
            if (!isset($decimalDigits[2])) {
                $decimalDigits[2] = 0;
            }
            if (!isset($decimalDigits[3])) {
                $decimalDigits[3] = 0;
            }

            $decimalDigits[1] = ($decimalDigits[2] > 5) ? $decimalDigits[1] + 1 : $decimalDigits[1];
            $convertdString = $firstPart;
            $convertdString .= ($decimalDigits[0] == '0' && $decimalDigits[1] == '0') ? '' : '.'.$decimalDigits[0];
            $convertdString .= ($decimalDigits[1] == '0') ? '' : $decimalDigits[1];

            return $convertdString;
        }

        return $string;
    }

    public function getStoreCodeMaps()
    {
        $result = array();
        $om = \Magento\Framework\App\ObjectManager::getInstance();
        $storeCollection = $om->get('Magento\Store\Model\StoreManagerInterface')->getStores($withDefault = false);

        foreach ($storeCollection as $store) {
            $result[$store->getId()] = $store->getCode();
        }
        $result[0] = 'all';

        return $result;
    }

    public function getDiscountLabel($storeId = null)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $label = (string) $this->scopeConfig->getValue('bundlediscount/general/discount_label', $storeScope);

        if (is_null($label) || strlen($label) <= 0) {
            $label = $this->__('Bundle Promotions Discount');
        }

        return $label;
    }

    public function getBundleAddToCartUrl($bundleId)
    {
        $routeParams = array(
            \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED => $this->urlEncoder->encode($this->_urlBuilder->getCurrentUrl()),
            '_secure' => $this->_request->isSecure(),
            'bundle_id' => $bundleId,
        );

        return $this->_urlBuilder->getUrl('md_bundlediscount/cart/add', $routeParams);
    }

    public function getPromotionHeading()
    {
        $title = __('Bundle Promotions');
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $config = (string) $this->scopeConfig->getValue('bundlediscount/general/heading_title', $storeScope);

        if ($config != '') {
            return $config;
        }

        return $title;
    }
}
