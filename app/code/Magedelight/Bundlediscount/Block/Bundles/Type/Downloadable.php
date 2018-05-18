<?php
/**
* Magedelight
* Copyright (C) 2014 Magedelight <info@magedelight.com>.
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
* @copyright Copyright (c) 2014 Mage Delight (http://www.magedelight.com/)
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
* @author Magedelight <info@magedelight.com>
*/

namespace Magedelight\Bundlediscount\Block\Bundles\Type;

use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\Downloadable\Model\Link;

class Downloadable extends \Magento\Downloadable\Block\Catalog\Product\Links
{
    public function getJsonConfig()
    {
        $finalPrice = $this->getProduct()->getPriceInfo()
            ->getPrice(FinalPrice::PRICE_CODE);

        $linksConfig = [];
        foreach ($this->getLinks() as $link) {
            $amount = $finalPrice->getCustomAmount($link->getPrice());
            $linksConfig[$link->getId()] = [
                'finalPrice' => $amount->getValue(),
                'basePrice' => $amount->getBaseAmount(),
            ];
        }

        return $this->encoder->encode(['links' => $linksConfig]);
    }

    /**
     * @param Link $link
     *
     * @return string
     */
    public function getLinkPrice(Link $link)
    {
        return $this->getLayout()->createBlock('Magento\Framework\Pricing\Render')->renderAmount(
            $this->getLinkAmount($link),
            $this->getPriceType(),
            $this->getProduct()
        );
    }
}
