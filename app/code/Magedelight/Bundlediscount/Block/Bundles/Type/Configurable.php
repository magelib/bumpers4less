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

class Configurable extends \Magento\ConfigurableProduct\Block\Product\View\Type\Configurable
{
    public function getJsonConfig()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $this->jsonDecoder = $objectManager->create('Magento\Framework\Json\Decoder');

        $config = $this->jsonDecoder->decode(parent::getJsonConfig());
        $config['containerId'] = '.configurable-container-'.$this->getProduct()->getId();

        return $this->jsonEncoder->encode($config);
    }

    public function getAttributeOptionsData($product)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $currentProduct = $this->getProduct();

        $this->helper = $objectManager->create('Magento\ConfigurableProduct\Helper\Data');
        $this->configurableAttributeData = $objectManager->create('Magento\ConfigurableProduct\Model\ConfigurableAttributeData');

        $options = $this->helper->getOptions($currentProduct, $this->getAllowProducts());
        $attributesData = $this->configurableAttributeData->getAttributesData($currentProduct, $options);

        return $attributesData;
    }

    public function getPriceJsonConfig()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->priceCurrency = $objectManager->create('Magento\Framework\Pricing\PriceCurrencyInterface');
        $this->_localeFormat = $objectManager->create('Magento\Framework\Locale\FormatInterface');
        /* @var $product \Magento\Catalog\Model\Product */
        $product = $this->getProduct();

        if (!$this->hasOptions()) {
            $config = [
                'productId' => $product->getId(),
                'priceFormat' => $this->_localeFormat->getPriceFormat(),
                ];

            return $this->jsonEncoder->encode($config);
        }

        $tierPrices = [];
        $tierPricesList = $product->getPriceInfo()->getPrice('tier_price')->getTierPriceList();
        foreach ($tierPricesList as $tierPrice) {
            $tierPrices[] = $this->priceCurrency->convert($tierPrice['price']->getValue());
        }
        $config = [
            'productId' => $product->getId(),
            'priceFormat' => $this->_localeFormat->getPriceFormat(),
            'prices' => [
                'oldPrice' => [
                    'amount' => $this->priceCurrency->convert(
                        $product->getPriceInfo()->getPrice('regular_price')->getAmount()->getValue()
                    ),
                    'adjustments' => [],
                ],
                'basePrice' => [
                    'amount' => $this->priceCurrency->convert(
                        $product->getPriceInfo()->getPrice('final_price')->getAmount()->getBaseAmount()
                    ),
                    'adjustments' => [],
                ],
                'finalPrice' => [
                    'amount' => $this->priceCurrency->convert(
                        $product->getPriceInfo()->getPrice('final_price')->getAmount()->getValue()
                    ),
                    'adjustments' => [],
                ],
            ],
            'idSuffix' => '_clone',
            'tierPrices' => $tierPrices,
        ];

        $responseObject = new \Magento\Framework\DataObject();
        $this->_eventManager->dispatch('catalog_product_view_config', ['response_object' => $responseObject]);
        if (is_array($responseObject->getAdditionalOptions())) {
            foreach ($responseObject->getAdditionalOptions() as $option => $value) {
                $config[$option] = $value;
            }
        }

        return $this->jsonEncoder->encode($config);
    }
    /**
     * Return true if product has options.
     *
     * @return bool
     */
    public function hasOptions()
    {
        if ($this->getProduct()->getTypeInstance()->hasOptions($this->getProduct())) {
            return true;
        }

        return false;
    }
}
