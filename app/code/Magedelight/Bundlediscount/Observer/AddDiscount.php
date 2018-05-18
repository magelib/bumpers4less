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

namespace Magedelight\Bundlediscount\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddDiscount implements ObserverInterface
{
    protected $logger;
    protected $customerSession;
    protected $helper;
    protected $request;
    protected $_priceModel;
    protected $_messageManager;

    /**
     * @param \Magento\Framework\App\Request\Http         $request
     * @param \Psr\Log\LoggerInterface                    $logger
     * @param \Magento\Checkout\Model\Session             $session
     * @param \Magento\Catalog\Model\Product\Type\Price   $priceModel
     * @param \Magento\Customer\Model\Session             $customerSession
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
    \Magento\Framework\App\Request\Http $request, \Psr\Log\LoggerInterface $logger, \Magento\Checkout\Model\Session $session, \Magento\Catalog\Model\Product\Type\Price $priceModel, \Magento\Customer\Model\Session $customerSession, \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->request = $request;
        $this->logger = $logger;

        $this->session = $session;
        $this->_priceModel = $priceModel;
        $this->customerSession = $customerSession;
        $this->_messageManager = $messageManager;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     *
     * @return bool
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($observer->getEvent()->getQuote()->isVirtual()) {
            $address = $observer->getEvent()->getQuote()->getBillingAddress();
        } else {
            $address = $observer->getEvent()->getQuote()->getShippingAddress();
        }

        $quote = $address->getQuote();
        if ((($address->getAddressType() == 'shipping' && !$quote->isVirtual()) || ($quote->isVirtual() && $address->getAddressType() == 'billing')) && $quote->getBundleDiscountAmount() > 0) {
            $discountDescription = $address->getDiscountDescription();
            $discountAmount = $address->getDiscountAmount();
            $baseDiscountAmount = $address->getBaseDiscountAmount();

            $grandTotal = $address->getGrandTotal();
            $baseGrandTotal = $address->getBaseGrandTotal();

            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

            $this->helper = $objectManager->create('Magedelight\Bundlediscount\Helper\Data');

            if (is_null($discountDescription) || strlen($discountDescription) <= 0) {
                $address->setDiscountDescription($this->helper->getDiscountLabel());
            } else {
                $address->setDiscountDescription($discountDescription.'&'.$this->helper->getDiscountLabel());
            }
            $address->setDiscountAmount($discountAmount - $quote->getBundleDiscountAmount());
            $address->setBaseDiscountAmount($baseDiscountAmount - $quote->getBaseBundleDiscountAmount());

            $address->setGrandTotal($grandTotal - $quote->getBundleDiscountAmount());
            $address->setBaseGrandTotal($baseGrandTotal - $quote->getBaseBundleDiscountAmount());
        }

        return $this;
    }
}
