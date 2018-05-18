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

namespace Magedelight\Bundlediscount\Block\Product\View\Type\Bundle;

/**
 * Bundle option renderer.
 */
class Option extends \Magento\Bundle\Block\Catalog\Product\View\Type\Bundle\Option
{
    /**
     * Format price string.
     *
     * @param \Magento\Catalog\Model\Product $selection
     * @param bool                           $includeContainer
     *
     * @return string
     */
    public function renderPriceString($selection, $includeContainer = true)
    {
        /** @var \Magento\Bundle\Pricing\Price\BundleOptionPrice $price */
        $price = $this->getProduct()->getPriceInfo()->getPrice('bundle_option');
        $amount = $price->getOptionSelectionAmount($selection);

        $priceHtml = $this->getLayout()->createBlock('Magento\Framework\Pricing\Render', '', ['data' => ['price_render_handle' => 'catalog_product_prices']])->renderAmount(
            $amount,
            $price,
            $selection,
            [
                'include_container' => $includeContainer,
            ]
        );

        return $priceHtml;
    }
}
