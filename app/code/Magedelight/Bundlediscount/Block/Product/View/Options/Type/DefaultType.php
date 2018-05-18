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

/**
 * Product options default type block.
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */

namespace Magedelight\Bundlediscount\Block\Product\View\Options\Type;

use Magento\Catalog\Pricing\Price\CustomOptionPriceInterface;

class DefaultType extends \Magento\Catalog\Block\Product\View\Options\Type\DefaultType
{
    /**
     * @return string
     */
    public function getFormatedPrice()
    {
        if ($option = $this->getOption()) {
            return $this->_formatPrice(
                [
                    'is_percent' => $option->getPriceType() == 'percent',
                    'pricing_value' => $option->getPrice($option->getPriceType() == 'percent'),
                ]
            );
        }

        return '';
    }

    /**
     * Return formated price.
     *
     * @param array $value
     * @param bool  $flag
     *
     * @return string
     */
    protected function _formatPrice($value, $flag = true)
    {
        if ($value['pricing_value'] == 0) {
            return '';
        }

        $sign = '+';
        if ($value['pricing_value'] < 0) {
            $sign = '-';
            $value['pricing_value'] = 0 - $value['pricing_value'];
        }

        $priceStr = $sign;

        $customOptionPrice = $this->getProduct()->getPriceInfo()->getPrice('custom_option_price');
        $context = [CustomOptionPriceInterface::CONFIGURATION_OPTION_FLAG => true];
        $optionAmount = $customOptionPrice->getCustomAmount($value['pricing_value'], null, $context);
        $priceStr .= $this->getLayout()->createBlock('Magento\Framework\Pricing\Render', '', ['data' => ['price_render_handle' => 'catalog_product_prices']])->renderAmount(
            $optionAmount,
            $customOptionPrice,
            $this->getProduct()
        );

        if ($flag) {
            $priceStr = '<span class="price-notice">'.$priceStr.'</span>';
        }

        return $priceStr;
    }
}
