<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Config;

use Magento\Framework\Option\ArrayInterface;

class CartPrice implements ArrayInterface
{
    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function toOptionArray()
    {
        return [
        ['value'=>'name','label'=>__('Name')],
        ['value'=>'is_active','label'=>__('Is Active')],
        ['value'=>'website_ids','label'=>__('Website Ids')],
        ['value'=>'customer_group_ids','label'=>__('Customer Group Ids')],
        ['value'=>'coupon_type','label'=>__('Coupon Type')],
        ['value'=>'coupon_code','label'=>__('Coupon Code')],
        ['value'=>'discount_amount','label'=>__('Discount Amount')]
        ];
    }
    public function toArray() {
        return [
        0=>__('name'),1=>__('is_active'),2=>__('website_ids'),3=>__('customer_group_ids'),4=>__('coupon_type'),5=>__('coupon_code'),6=>__('discount_amount')
        ];
    }
}
