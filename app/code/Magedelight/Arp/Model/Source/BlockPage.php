<?php
/* Magedelight
 * Copyright (C) 2016 Magedelight <info@magedelight.com>
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
 * @category MD
 * @package MD_Cybersourcesa
 * @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 * 
 */
namespace Magedelight\Arp\Model\Source;
 
class BlockPage implements \Magento\Framework\Data\OptionSourceInterface
{

    /**#@+
     * Product Status values
     */
    const PRODUCT_PAGE = 1;

    const SHOPPING_CART = 2;
    
    const CATEGORY_PAGE = 3;
    
    const ORDER_SUCCESS_PAGE = 4;
    
    const CMS_PAGE = 5; 


    
    
    public static function getOptionArray()
    {
        return [
            self::PRODUCT_PAGE => __('Product page'),
            self::SHOPPING_CART => __('Shopping cart page'),
            self::CATEGORY_PAGE => __('Category page'),
            self::ORDER_SUCCESS_PAGE => __('Order success page'),
            self::CMS_PAGE => __('Cms page')
        ];
    }
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $options[] = ['label' => '--Please select page---', 'value' => ''];
        $availableOptions = self::getOptionArray();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
    /**
     * Retrieve option text by option value
     *
     * @param string $optionId
     * @return string
     */
    public function getOptionText($optionId)
    {
        $options = self::getOptionArray();

        return isset($options[$optionId]) ? $options[$optionId] : null;
    }
}
