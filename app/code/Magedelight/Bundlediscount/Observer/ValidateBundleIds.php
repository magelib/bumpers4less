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

class ValidateBundleIds implements ObserverInterface
{
    protected $logger;
    protected $customerSession;
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
        $quote = $observer->getEvent()->getQuote();
        $items = $quote->getAllItems();
        if ($quote->getId()) {
            $bundleIds = explode(',', $quote->getData('bundle_ids'));
            if ($bundleIds[0] == '') {
                unset($bundleIds[0]);
            }

            if (count($bundleIds) > 0) {
                $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

                $qtyArrays = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundlediscount')->calculateProductQtys($bundleIds);
                $quoteQtys = $this->_calculateQuoteItemsQtys($quote, $qtyArrays);

                $result = array();
                foreach ($bundleIds as $bundleId) {
                    $isValid = true;
                    foreach ($quoteQtys as $productId => $qty) {
                        if (isset($qtyArrays[$productId][$bundleId])) {
                            if ($qtyArrays[$productId][$bundleId] <= $qty) {
                                $quoteQtys[$productId] -= $qtyArrays[$productId][$bundleId];
                            } else {
                                $isValid = false;
                            }
                        }
                    }
                    if ($isValid) {
                        $result[] = $bundleId;
                    }
                }
                $idString = (count($result) == 0) ? '' : (count($result) == 1) ? $result[0] : implode(',', $result);

                $quote->setData('bundle_ids', $idString);
            }
        }

        return $this;
    }

    protected function _calculateQuoteItemsQtys($quote, $qtys)
    {
        $result = array();
        $keys = array_keys($qtys);
        foreach ($quote->getAllItems() as $item) {
            if (!isset($result[$item->getProductId()])) {
                $result[$item->getProductId()] = $item->getQty();
            } else {
                $result[$item->getProductId()] += $item->getQty();
            }
        }
        foreach ($keys as $productId) {
            if (!isset($result[$productId])) {
                $result[$productId] = 0;
            }
        }

        return $result;
    }
}
