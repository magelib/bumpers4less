<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Checkout
 */


namespace Amasty\Checkout\Plugin;

use Magento\Checkout\Model\Session as CheckoutSession;

class DefaultConfigProvider
{
    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;
    /**
     * @var \Amasty\Checkout\Helper\Item
     */
    protected $itemHelper;
    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $layout;

    public function __construct(
        CheckoutSession $checkoutSession,
        \Amasty\Checkout\Helper\Item $itemHelper,
        \Magento\Framework\View\LayoutInterface $layout
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->layout = $layout;
        $this->itemHelper = $itemHelper;
    }

    public function afterGetConfig(\Magento\Checkout\Model\DefaultConfigProvider $subject, $config)
    {
        if (!in_array('amasty_checkout', $this->layout->getUpdate()->getHandles()))
            return $config;

        $quote = $this->checkoutSession->getQuote();

        foreach ($config['quoteItemData'] as &$item) {
            $additionalConfig = $this->itemHelper->getItemOptionsConfig($quote, $item['item_id']);

            if (!empty($additionalConfig)) {
                $item['amcheckout'] = $additionalConfig;
            }
        }

        return $config;
    }
}
