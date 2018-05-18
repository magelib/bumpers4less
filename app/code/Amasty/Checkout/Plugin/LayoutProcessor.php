<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Checkout
 */


namespace Amasty\Checkout\Plugin;

class LayoutProcessor
{
    protected $orderFixes = [];
    
    public function setOrder($field, $order)
    {
        $this->orderFixes[$field] = $order;
    }

    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        $result
    ) {
        $layoutRoot = &$result['components']['checkout']['children']['steps']['children']['shipping-step']['children']
                      ['shippingAddress']['children']['shipping-address-fieldset']['children'];

        foreach ($this->orderFixes as $code => $order) {
            $layoutRoot[$code]['sortOrder'] = $order;
        }

        foreach ($result['components']['checkout']['children']['steps']['children']['billing-step']['children']
                 ['payment']['children']['payments-list']['children'] as &$paymentMethod) {
            $paymentMethod['template'] = 'Amasty_Checkout/billing-address';
        }

        return $result;
    }
}
