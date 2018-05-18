<?php
/**
 * Magedelight
 * Copyright (C) 2016 Magedelight <info@magedelight.com>
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
 * @package Magedelight_Bundlediscount
 * @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 */

namespace Magedelight\Bundlediscount\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Mddata extends AbstractHelper 
{
    protected $_curl;

    protected $_storeManager;

    protected $configWritter;

    protected $messageManager;
    
    protected $_cacheTypeList;
    
    protected $_cacheFrontendPool;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWritter,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
    ) 
    {
        $this->_curl = $curl;
        $this->messageManager = $messageManager;
        $this->_storeManager = $storeManager;
        $this->configWritter = $configWritter;
        $this->_cacheTypeList = $cacheTypeList;
        $this->_cacheFrontendPool = $cacheFrontendPool;
        parent::__construct($context);
    }

    public function getExtensionKey() 
    {
        $extensionKey = "ek-magento2-product-bundled-discount";
        return $extensionKey;
    }

    public function getAllowedDomainsCollection() 
    {
        $mappedDomains =array();
        $url = $this->_storeManager->getStore()->getBaseUrl();
        $serial = $this->scopeConfig->getValue('bundlediscount/license/serial_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $activation = $this->scopeConfig->getValue('bundlediscount/license/activation_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        //$isLinkEnable = $this->scopeConfig->getValue('bundlediscount/general/enable_link', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $isBundleDiscountLinkEnable = $this->scopeConfig->getValue('bundlediscount/others/enable_frontend', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $isBundleOnCartEnable = $this->scopeConfig->getValue('bundlediscount/others/enable_bundle_cart', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $selectedWebsites = $this->scopeConfig->getValue('bundlediscount/others/select_website', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (strpos($url, 'localhost') === false && strpos($url, '127.0.0.1') === false) 
        {
            if ($serial == '' && $activation == '') {
                $this->messageManager->addError("Serial and Activation keys not found.Please enter valid keys for 'Product Bundled Discount' extension.");
            }
            if ($activation == '') {
                $this->messageManager->addError("Activation key not found.Please enter valid activation key for 'Product Bundled Discount' extension.");
            }

            if ($serial == '') {
                $this->messageManager->addError("Serial key not found.Please enter valid serial key for 'Product Bundled Discount' extension.");
            }
            
            $parsedUrl = parse_url($url);
            $domain = str_replace(array('www.', 'http://', 'https://'), '', $parsedUrl['host']);
            $hash = $serial.''.$domain;
            /* Curl post to check if key is valid **/
            $keys['sk'] = $serial;
            $keys['ak'] = $activation;
            $keys['domain'] = $domain;
            $keys['product_name'] = 'Product Bundled Discount';
            $keys['ek'] = $this->getExtensionKey();
            $keys['sw'] = $selectedWebsites;
            $field_string = http_build_query($keys);

            $curlPostUrl = 'https://www.magedelight.com/ktplsys/index/validate?'.$field_string; // live url

            try {
                $this->configWritter->save('bundlediscount/license/data', 1);
                $this->_curl->post($curlPostUrl, $keys);
                $response = $this->_curl->getBody();
                $mappedDomains = json_decode($response);
                if(is_object(json_decode($response)) && null !== json_decode($response)){
                    $this->configWritter->save('bundlediscount/license/data', 0);
                    
                    if (is_object($mappedDomains)) {
                        $mappedDomains = get_object_vars($mappedDomains);
                    }
                    
                    if(!isset($mappedDomains['domains'])){
                        //$this->configWritter->save('bundlediscount/general/enable_link', 0);
                        $this->configWritter->save('bundlediscount/others/enable_frontend', 0);
                        $this->configWritter->save('bundlediscount/others/enable_bundle_cart', 0);
                    }

                    if($isBundleDiscountLinkEnable == 'No' && $isBundleOnCartEnable == 'No')
                    {
                        $this->messageManager->addNotice($mappedDomains['msg']);    
                    }

                    if(isset($mappedDomains['domains']))
                    {
                        $websites = explode(',',$selectedWebsites);
                        $selected = array();
                        $updateSelected = '';
                        if(count($websites) > 0){
                            foreach($websites as $web){
                                if(in_array($web, $mappedDomains['domains']))
                                {
                                    $selected[] = $web;
                                }
                            }
                        }
                        $updateSelected = implode(',', $selected);
                        $this->configWritter->save('bundlediscount/others/select_website', $updateSelected);
                        if(empty($updateSelected)){
                            $this->configWritter->save('bundlediscount/others/enable_bundle_cart', 0);
                            $this->configWritter->save('bundlediscount/license/data', 0); 
                        }
                        
                        if(count($mappedDomains['domains']) > 0 && empty($selectedWebsites))
                        {
                            $this->messageManager->addNotice('Please select website(s) to enable the extension.');
                            //$this->configWritter->save('bundlediscount/general/enable_link', 0);
                            $this->configWritter->save('bundlediscount/others/enable_frontend', 0);
                            $this->configWritter->save('bundlediscount/others/enable_bundle_cart', 0);
                            $this->configWritter->save('bundlediscount/license/data', 0);
                        }

                        if(empty($mappedDomains['domains']))
                        {
                            $this->configWritter->save('bundlediscount/license/data', 0);
                           
                            //$this->configWritter->save('bundlediscount/general/enable_link', 0);
                            $this->configWritter->save('bundlediscount/others/enable_frontend', 0);
                            $this->configWritter->save('bundlediscount/others/enable_bundle_cart', 0);
                            $this->configWritter->save('bundlediscount/license/serial_key', '');
                            $this->configWritter->save('bundlediscount/license/activation_key', '');
                            $this->configWritter->save('bundlediscount/others/select_website', '');

                            $this->messageManager->addError('Invalid activation and serial key for "Product Bundled Discount".');
                        }else{
                            $this->configWritter->save('bundlediscount/license/data', 1);
                        }
                    }
                }
                $types = array('config','full_page');
                foreach ($types as $type) {
                    $this->_cacheTypeList->cleanType($type);
                }
                foreach ($this->_cacheFrontendPool as $cacheFrontend) {
                    $cacheFrontend->getBackend()->clean();
                }
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }

            return $mappedDomains;
        }
    }

}
