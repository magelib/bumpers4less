<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Checkout
 */


namespace Amasty\Checkout\Api;

interface GuestNewsletterManagementInterface
{
    /**
     * Set payment information before redirect to payment for guest.
     *
     * @param string $cartId
     * @param mixed|null $amcheckoutData
     * @return void.
     */
    public function subscribe($cartId, $email, $amcheckoutData);
}