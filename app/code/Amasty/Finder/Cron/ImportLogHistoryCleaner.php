<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Finder\Cron;

class ImportLogHistoryCleaner
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * ImportLogHistoryCleaner constructor.
     *
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\ObjectManagerInterface          $objectManager
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->objectManager = $objectManager;
    }

    public function execute()
    {
        $lifetime = $this->scopeConfig->getValue('amfinder/import/archive_lifetime');
        $date = strftime('%Y-%m-%d %H:%M:%S', strtotime("-{$lifetime} days"));
        /**
         * @var $list \Amasty\Finder\Model\ResourceModel\ImportHistory\Collection
         */
        $list = $this->objectManager->create('Amasty\Finder\Model\ImportHistory')->getCollection()->addFieldToFilter('ended_at', array("lteq" => $date));
        $list->walk('delete');
    }
}
