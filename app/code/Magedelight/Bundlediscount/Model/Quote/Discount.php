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

namespace Magedelight\Bundlediscount\Model\Quote;

class Discount extends \Magento\Quote\Model\Quote\Address\Total\AbstractTotal
{
    protected $quoteValidator = null;
    protected $_qtyArrays = array();

    /**
     * @param \Magento\Quote\Model\QuoteValidator $quoteValidator
     */
    public function __construct(\Magento\Quote\Model\QuoteValidator $quoteValidator)
    {
        $this->quoteValidator = $quoteValidator;
    }

    public function collect(
    \Magento\Quote\Model\Quote $quote, \Magento\Quote\Api\Data\ShippingAssignmentInterface $shippingAssignment, \Magento\Quote\Model\Quote\Address\Total $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);
        $address = $shippingAssignment->getShipping()->getAddress();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->helper = $objectManager->create('Magedelight\Bundlediscount\Helper\Data');
        $this->_priceModel = $objectManager->create('Magento\Catalog\Model\Product\Type\Price');

        $label = $this->helper->getDiscountLabel();
        $count = 0;
        $appliedCartDiscount = 0;
        $totalDiscountAmount = 0;
        $subtotalWithDiscount = 0;
        $baseTotalDiscountAmount = 0;
        $baseSubtotalWithDiscount = 0;

        $bundleIds = explode(',', $quote->getData('bundle_ids'));
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        if ($bundleIds[0] == '') {
            unset($bundleIds[0]);
        }

        $this->_qtyArrays = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundlediscount')->calculateProductQtys($bundleIds);

        $items = $quote->getAllItems();

        if (!count($items)) {
            $address->setBundleDiscountAmount($totalDiscountAmount);
            $address->setBaseBundleDiscountAmount($baseTotalDiscountAmount);

            return $this;
        }

        $addressQtys = $this->_calculateAddressQtys($address);

        $finalBundleIds = $this->_validateBundleIds($addressQtys, $bundleIds);
        if (is_array($addressQtys) && count($addressQtys) > 0) {
            $count += array_sum(array_values($addressQtys));
        }

        foreach ($finalBundleIds as $id) {
            $bundle = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundlediscount')->load($id);
            $excludeFromBaseProductFlag = ($bundle->getExcludeBaseProduct() == 0) ? false : true;
            $totalAmountOfBundle = 0;
            $tempArray = array();
            foreach ($items as $item) {
                if ($item instanceof \Magento\Quote\Model\Quote\Address\Item) {
                    $quoteItem = $item->getAddress()->getQuote()->getItemById($item->getQuoteItemId());
                } else {
                    $quoteItem = $item;
                }
                $product = $quoteItem->getProduct();
                $product->setCustomerGroupId($quoteItem->getQuote()->getCustomerGroupId());
                if (isset($this->_qtyArrays[$quoteItem->getProduct()->getId()][$id])) {
                    if (!in_array($quoteItem->getProduct()->getId(), $tempArray)) {
                        if ($excludeFromBaseProductFlag && $product->getId() == $bundle->getProductId()) {
                            continue;
                        }
                        $tempArray[] = $quoteItem->getProduct()->getId();

                        $qty = $this->_qtyArrays[$quoteItem->getProduct()->getId()][$id];
                        $price = $quoteItem->getDiscountCalculationPrice();
                        $calcPrice = $quoteItem->getCalculationPrice();
                        $itemPrice = $price === null ? $calcPrice : $price;
                        $totalAmountOfBundle += $itemPrice * $qty;
                    }
                }
            }

            if ($bundle->getDiscountType() == 0) {
                $totalDiscountAmount += $bundle->getDiscountPrice();

                $baseTotalDiscountAmount += $bundle->getDiscountPrice();
            } else {
                $totalDiscountAmount += ($bundle->getDiscountPrice() * $totalAmountOfBundle) / 100;
                $baseTotalDiscountAmount += ($bundle->getDiscountPrice() * $totalAmountOfBundle) / 100;
            }
        }

        $totalDiscountAmount = round($totalDiscountAmount, 2);
        $baseTotalDiscountAmount = round($baseTotalDiscountAmount, 2);

        $this->helper = $this->_objectManager->create('Magento\Tax\Helper\Data');

        $totaldata = $total->getData();

        $subTotal = $totaldata['subtotal'];
        $baseSubTotal = $totaldata['base_subtotal'];
        if ($totalDiscountAmount > 0 && $this->helper->applyTaxAfterDiscount()) {
            if ($count > 0) {
                $divided = $totalDiscountAmount / $count;
                $baseDivided = $baseTotalDiscountAmount / $count;
                foreach ($items as $item) {
                    $dividedItemDiscount = round(($item->getRowTotal() * $totalDiscountAmount) / $subTotal, 2);
                    $baseDividedItemDiscount = round(($item->getBaseRowTotal() * $baseTotalDiscountAmount) / $baseSubTotal, 2);

                    $oldDiscountAmount = $item->getDiscountAmount();
                    $oldBaseDiscountAmount = $item->getBaseDiscountAmount();
                    $origionalDiscountAmount = $item->getOriginalDiscountAmount();
                    $baseOrigionalDiscountAmount = $item->getBaseOriginalDiscountAmount();

                    $item->setDiscountAmount($oldDiscountAmount + $dividedItemDiscount);
                    $item->setBaseDiscountAmount($oldBaseDiscountAmount + $baseDividedItemDiscount);
                    $item->setOriginalDiscountAmount($origionalDiscountAmount + $dividedItemDiscount);
                    $item->setBaseOriginalDiscountAmount($baseOrigionalDiscountAmount + $baseDividedItemDiscount);
                }
            }
        }

        $address->setBundleDiscountAmount($totalDiscountAmount);

        $address->setBaseBundleDiscountAmount($baseTotalDiscountAmount);
        $quote->setBundleDiscountAmount($totalDiscountAmount);
        $quote->setBaseBundleDiscountAmount($baseTotalDiscountAmount);

        $discountAmount = -$totalDiscountAmount;

        if ($total->getDiscountDescription()) {
            // If a discount exists in cart and another discount is applied, the add both discounts.
            $appliedCartDiscount = $total->getDiscountAmount();
            $discountAmount = $total->getDiscountAmount() + $discountAmount;
            $label = $total->getDiscountDescription().', '.$label;
        }

        $getSubTotal = $total->getSubtotal();
        $tempDiscount = str_replace('-', '', $discountAmount);
        if ($tempDiscount > $getSubTotal) {
            $discountAmount = '-'.$getSubTotal;
        }

        $total->setDiscountDescription($label);
        $total->setDiscountAmount($discountAmount);
        $total->setBaseDiscountAmount($discountAmount);
        $total->setSubtotalWithDiscount($total->getSubtotal() + $discountAmount);
        $total->setBaseSubtotalWithDiscount($total->getBaseSubtotal() + $discountAmount);

        if (isset($appliedCartDiscount)) {
            $total->addTotalAmount($this->getCode(), $discountAmount - $appliedCartDiscount);
            $total->addBaseTotalAmount($this->getCode(), $discountAmount - $appliedCartDiscount);
        } else {
            $total->addTotalAmount($this->getCode(), $discountAmount);
            $total->addBaseTotalAmount($this->getCode(), $discountAmount);
        }

        return $this;
    }

    protected function _calculateAddressQtys(\Magento\Quote\Model\Quote\Address $address)
    {
        $result = array();
        $keys = array_keys($this->_qtyArrays);
        foreach ($address->getAllVisibleItems() as $item) {
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

    protected function _validateBundleIds($addressQtys, $bundleIds)
    {
        $result = array();
        if (!is_array($bundleIds)) {
            $bundleIds = array($bundleIds);
        }

        foreach ($bundleIds as $bundleId) {
            $isValid = true;
            foreach ($addressQtys as $productId => $qty) {
                if (isset($this->_qtyArrays[$productId][$bundleId])) {
                    if ($this->_qtyArrays[$productId][$bundleId] <= $qty) {
                        $addressQtys[$productId] -= $this->_qtyArrays[$productId][$bundleId];
                    } else {
                        $isValid = false;
                    }
                }
            }
            if ($isValid) {
                $result[] = $bundleId;
            }
        }

        return $result;
    }

    /**
     * Add discount total information to address.
     *
     * @param \Magento\Quote\Model\Quote               $quote
     * @param \Magento\Quote\Model\Quote\Address\Total $total
     *
     * @return array|null
     */
    public function fetch(\Magento\Quote\Model\Quote $quote, \Magento\Quote\Model\Quote\Address\Total $total)
    {
        $result = null;
        $amount = $total->getDiscountAmount();

        if ($amount != 0) {
            $description = $total->getDiscountDescription();
            $result = [
                'code' => $this->getCode(),
                'title' => strlen($description) ? __('Discount (%1)', $description) : __('Discount'),
                'value' => $amount,
            ];
        }

        return $result;
    }
}
