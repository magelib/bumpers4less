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

namespace Magedelight\Bundlediscount\Block;

class Bundlecrosssell extends \Magento\Framework\View\Element\Template
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Tax\Model\CalculationFactory
     */
    protected $_calculationFactory;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    protected $_jsonEncoder;

    /**
     * @param \Magento\Catalog\Block\Product\Context   $context
     * @param \Magento\Tax\Model\CalculationFactory    $calculationFactory
     * @param \Magento\Customer\Model\Session          $customerSession
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param array                                    $data
     */
    public function __construct(
    \Magento\Catalog\Block\Product\Context $context, \Magento\Tax\Model\CalculationFactory $calculationFactory, \Magento\Customer\Model\Session $customerSession, \Magento\Framework\Json\EncoderInterface $jsonEncoder, array $data = []
    ) {
        $this->scopeConfig = $context->getScopeConfig();
        $this->_calculationFactory = $calculationFactory;
        $this->customerSession = $customerSession;
        $this->_jsonEncoder = $jsonEncoder;
        parent::__construct($context, $data);

        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $bundleCollection = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundlediscount')->getBundlesByCustomerCrosssell($this->customerSession);

        $this->setCollection($bundleCollection);
    }

    public function _prepareLayout()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $link_title = $this->scopeConfig->getValue('bundlediscount/general/link_title', $storeScope);
        $this->pageConfig->getTitle()->set(__($link_title));

        parent::_prepareLayout();

        return $this;
    }

    public function getLoadedBundles()
    {
        return $this->getCollection();
    }

    /**
     * @param type $product
     *
     * @return string
     */
    public function getProductJsonConfig($product)
    {
        $config = array();

        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_catalogData = $this->_objectManager->create('Magento\Catalog\Helper\Data');
        $priceHelper = $this->_objectManager->create('Magento\Framework\Pricing\Helper\Data');
        $this->_taxHelper = $this->_objectManager->create('Magento\Tax\Helper\Data');
        $this->_priceFormat = $this->_objectManager->create('Magento\Framework\Locale\Format');

        $calculator = $this->_calculationFactory->create();

        $_request = $calculator->getRateRequest(false, false, false);

        $_request->setProductClassId($product->getTaxClassId());
        $defaultTax = $calculator->getRate($_request);

        $_request = $calculator->getRateRequest();
        $_request->setProductClassId($product->getTaxClassId());
        $currentTax = $calculator->getRate($_request);

        $_regularPrice = $product->getPrice();
        $_finalPrice = $product->getFinalPrice();
        if ($product->getTypeId() == 'bundle') {
            $_priceInclTax = $this->_catalogData->getTaxPrice($product, $_finalPrice, true, null, null, null, null, null, false);
            $_priceExclTax = $this->_catalogData->getTaxPrice($product, $_finalPrice, false, null, null, null, null, null, false);
        } else {
            $_priceInclTax = $this->_catalogData->getTaxPrice($product, $_finalPrice, true);
            $_priceExclTax = $this->_catalogData->getTaxPrice($product, $_finalPrice);
        }
        $_tierPrices = array();
        $_tierPricesInclTax = array();
        foreach ($product->getTierPrice() as $tierPrice) {
            $_tierPrices[] = $priceHelper->currency(
                    $this->_catalogData->getTaxPrice($product, (float) $tierPrice['website_price'], false) - $_priceExclTax, false, false);
            $_tierPricesInclTax[] = $priceHelper->currency(
                    $this->_catalogData->getTaxPrice($product, (float) $tierPrice['website_price'], true) - $_priceInclTax, false, false);
        }
        $config = array(
            'productId' => $product->getId(),
            'priceFormat' => $this->_priceFormat->getPriceFormat(),
            'includeTax' => $this->_taxHelper->priceIncludesTax() ? 'true' : 'false',
            'showIncludeTax' => $this->_taxHelper->displayPriceIncludingTax(),
            'showBothPrices' => $this->_taxHelper->displayBothPrices(),
            'productPrice' => $priceHelper->currency($_finalPrice, false, false),
            'productOldPrice' => $priceHelper->currency($_regularPrice, false, false),
            'priceInclTax' => $priceHelper->currency($_priceInclTax, false, false),
            'priceExclTax' => $priceHelper->currency($_priceExclTax, false, false),
            /*
             * @var skipCalculate
             * @deprecated after 1.5.1.0
             */
            'skipCalculate' => ($_priceExclTax != $_priceInclTax ? 0 : 1),
            'defaultTax' => $defaultTax,
            'currentTax' => $currentTax,
            'idSuffix' => '_clone',
            'oldPlusDisposition' => 0,
            'plusDisposition' => 0,
            'plusDispositionTax' => 0,
            'oldMinusDisposition' => 0,
            'minusDisposition' => 0,
            'tierPrices' => $_tierPrices,
            'tierPricesInclTax' => $_tierPricesInclTax,
        );

        $responseObject = new \Magento\Framework\DataObject();

        $this->_eventManager->dispatch('catalog_product_view_config', ['response_object' => $responseObject]);
        if (is_array($responseObject->getAdditionalOptions())) {
            foreach ($responseObject->getAdditionalOptions() as $option => $value) {
                $config[$option] = $value;
            }
        }

        return $this->_jsonEncoder->encode($config);
    }

    public function getOptionsHtml(\Magento\Catalog\Model\Product $product)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        //$rendererList=  $objectManager->create('Magento\Framework\View\Element\RendererList');
        $renderer = $objectManager->create('Magento\ConfigurableProduct\Block\Product\View\Type\Configurable');

        if ($renderer) {
            $renderer->setProduct($product);
            $renderer->setTemplate('Magento_ConfigurableProduct::product/view/type/options/configurable.phtml');

            return $renderer->toHtml();
        }
    }
}
