<?php
/* Magedelight
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
 * @package Magedelight_Faqs
 * @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 * 
 */
namespace Magedelight\Arp\Helper;


class Data extends \Magento\Framework\App\Helper\AbstractHelper
{   
    public $storeManager;
    
    public $productCollectionFactory;
    
    public $customerSession;


    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $CollectionFactory
    ) {
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->productCollectionFactory = $CollectionFactory;
        parent::__construct($context);
    }
    
    public function isEnabled()
    {
        $currentUrl = $this->storeManager->getStore()->getBaseUrl();
        $domain = $this->getDomainName($currentUrl);
        $selectedWebsites = $this->getConfig('arp_products/general/select_website');
        $websites = explode(',',$selectedWebsites);
        if(in_array($domain, $websites) && $this->getConfig('arp_products/general/enabled_arp') && $this->getConfig('arp_products/license/data'))
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
        $websites = $this->storeManager->getWebsites();
        $websiteUrls = array();
        foreach($websites as $website)
        {
            foreach($website->getStores() as $store){
                $wedsiteId = $website->getId();
                $storeObj = $this->storeManager->getStore($store);
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
    
    public function getProductColletion() {
        return $this->productCollectionFactory->create();
    }
   
    public function getCustomerGroup() {
        if($this->customerSession->isLoggedIn()) {
            $groups = $this->customerSession->getCustomer()->getGroupId();
            return $groups;
        }
        return '0';
    }
    
    public function getColletionAfterSort($collection, $sortId) {
       if($sortId) {
            switch ($sortId) {
                case 1:
                    $collection->setOrder('name', 'ASC');
                    return $collection;
                    break;
                case 2:
                    $collection->setOrder('created_at', 'desc');
                    return $collection;
                    break;
                case 3:
                    $collection->setOrder('price', 'DESC');
                    return $collection;
                    break;
                case 4:
                    $collection->setOrder('price', 'ASC');
                    return $collection;
                    break;
                case 5:
                        $collection
                        ->getSelect()
                        ->joinLeft(
                                'sales_order_item AS sfoi',
                                'e.entity_id = sfoi.product_id',
                                'SUM(sfoi.qty_ordered) AS ordered_qty')
                        ->group('e.entity_id')
                        ->order('ordered_qty ' . $this->getCurrentDirectionReverse());
                    return $collection;
                    break;
                case 6:
                    return $collection;
                    break;
                default:
                    break;
            }
        }
    }
    public function getCurrentDirectionReverse() {
        return 'DESC';
    }
}
