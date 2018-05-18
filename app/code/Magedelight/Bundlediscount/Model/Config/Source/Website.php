<?php

namespace Magedelight\Bundlediscount\Model\Config\Source;

class Website implements \Magento\Framework\Option\ArrayInterface
{ 
    protected $logger;

    protected $storeManager;

    protected $helper;

    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magedelight\Bundlediscount\Helper\Mddata $helper
    )
    {
        $this->_logger = $logger;
        $this->helper = $helper;
        $this->_storeManager = $storeManager;
    }

    /**
     * Return array of options as value-label pairs, eg. value => label
     *
     * @return array
     */
    public function toOptionArray()
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

        $mappedDomainsArr = $this->helper->getAllowedDomainsCollection();
        $responseArray = array();
        
        try {
            if(!empty($mappedDomainsArr))
            {
                $i =0;
                foreach($mappedDomainsArr['domains'] as $key => $domain)
                {
                    if(in_array($domain, $websiteUrls))
                    {
                        $responseArray[] = ['value' => $domain, "label" => $domain];
                        $i++;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->_logger->debug($e->getMessage());
        }

        return $responseArray;
    }
}