<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */

namespace Amasty\Smtp\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Registry;
use Magento\Sales\Model\Order\Shipment;

class Data extends AbstractHelper
{
    /**
     * @var Registry
     */
    protected $registry;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;
    /**
     * @var \Magento\Framework\App\State
     */
    protected $appState;

    /**
     * @param \Magento\Framework\App\Helper\Context      $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\App\State               $appState
     * @param Registry                                   $registry
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\App\State $appState,
        Registry $registry
    ) {
        parent::__construct($context);

        $this->registry = $registry;
        $this->storeManager = $storeManager;
        $this->appState = $appState;
    }

    public function getCurrentStore()
    {
        $store = $this->storeManager->getStore();

        if ($this->appState->getAreaCode() == \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
            /** @var \Magento\Sales\Model\Order $order */
            if ($order = $this->registry->registry('current_order')) {
                return $order->getStoreId();
            }
            
            return 0;
        }

        return $store->getId();
    }
}
