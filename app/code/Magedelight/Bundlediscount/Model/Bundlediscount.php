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

namespace Magedelight\Bundlediscount\Model;

class Bundlediscount extends \Magento\Framework\Model\AbstractModel
{
    protected $_orderVar = 'product_list_order';
    protected $_limitVar = 'product_list_limit';
    protected $_dirVar = 'product_list_dir';
    protected $_dirValue = 'asc';
    protected $_limitValue = 9;
    protected $_pageVar = 'p';
    protected $_pageValue = '1';
    protected $_orderValue = 'created_at';

    /**
     * @param \Magento\Framework\Model\Context                        $context
     * @param \Magento\Framework\Registry                             $registry
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb           $resourceCollection
     * @param array                                                   $data
     */
    public function __construct(
    \Magento\Framework\Model\Context $context, \Magento\Framework\Registry $registry, \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null, \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null, array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    public function _construct()
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->request = $this->_objectManager->create('Magento\Framework\App\Request\Http');
        $this->toolbarBlock = $this->_objectManager->create('Magedelight\Bundlediscount\Block\ProductList\Toolbar');

        $this->_limitValue = $this->toolbarBlock->getLimit();

        $params = $this->request->getParams();

        if (isset($params[$this->_orderVar])) {
            $this->_orderValue = $params[$this->_orderVar];
        }
        if (isset($params[$this->_limitVar])) {
            $this->_limitValue = $params[$this->_limitVar];
        }
        if (isset($params[$this->_dirVar])) {
            $this->_dirValue = $params[$this->_dirVar];
        }
        if (isset($params[$this->_pageVar])) {
            $this->_pageValue = $params[$this->_pageVar];
        }

        $this->_init('Magedelight\Bundlediscount\Model\ResourceModel\Bundlediscount');
    }

    public function checkIdentifier($identifier, $storeId)
    {
        return $this->_getResource()->checkIdentifier($identifier, $storeId);
    }

    protected function _afterLoad()
    {
        parent::_afterLoad();
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_taxHelper = $this->_objectManager->create('Magento\Tax\Helper\Data');
        $this->_catalogData = $this->_objectManager->create('Magento\Catalog\Helper\Data');
        $displayBothPrice = (boolean) $this->_taxHelper->displayBothPrices();
        $displayIncludeTaxPrice = (boolean) $this->_taxHelper->displayPriceIncludingTax();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($this->getProductId());

        if ($displayBothPrice) {
            $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), true, null, null, null, null, null, false);
        } else {
            if ($displayIncludeTaxPrice) {
                $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), true, null, null, null, null, null, false);
            } else {
                $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), false, null, null, null, null, null, false);
            }
        }

        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->setProductName($product->getName())
                ->setProductPrice($finalPrice)
                ->setProductSku($product->getSku())
                ->setImageUrl($this->getImage($product))
                ->setTypeId($product->getTypeId())
                ->setHasCustomOptions($product->getOptions() ? 1 : 0)
                ->setIsSalable(($product->isSalable()) ? 1 : 0)
                ->setProductUrl($product->getProductUrl());

        $items = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundleitems')->getItemsByBundle($this->getBundleId());
        $this->setSelections($items);

        return $this;
    }

    public function getBundlesByCustomer($customer)
    {
        $isLoggedIn = $customer->isLoggedIn();
        $customerGroup = (!$isLoggedIn) ? 0 : $customer->getCustomerGroupId();

        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->date = $this->_objectManager->create('Magento\Framework\Stdlib\DateTime\DateTime');
        $this->_storeManager = $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface');
        $this->_taxHelper = $this->_objectManager->create('Magento\Tax\Helper\Data');
        $this->_catalogData = $this->_objectManager->create('Magento\Catalog\Helper\Data');

        $bundleCollection = $this->getCollection()
                ->addFieldToFilter('status', array('eq' => 1))
                ->addFieldToFilter('store_ids', array(array('finset' => array(0)), array('finset' => array($this->_storeManager->getStore()->getId()))))
                ->addFieldToFilter('customer_groups', array('finset' => array($customerGroup)))
                ->addfieldtofilter('date_from', array(
                    array('to' => $this->date->gmtDate('Y-m-d')),
                    array('date_from', 'null' => ''), ))
                ->addfieldtofilter('date_to', array(
                    array('gteq' => $this->date->gmtDate('Y-m-d')),
                    array('date_to', 'null' => ''), )
                )
                ->setCurPage($this->_pageValue)
                ->setPageSize($this->_limitValue)
                ->setOrder($this->_orderValue, strtoupper($this->_dirValue));
        $displayBothPrice = (boolean) $this->_taxHelper->displayBothPrices();
        $displayIncludeTaxPrice = (boolean) $this->_taxHelper->displayPriceIncludingTax();
        foreach ($bundleCollection as $bundle) {
            $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->setStoreId($this->_storeManager->getStore()->getId())->load($bundle->getProductId());
            if ($displayBothPrice) {
                $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), true, null, null, null, null, null, false);
            } else {
                if ($displayIncludeTaxPrice) {
                    $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), true, null, null, null, null, null, false);
                } else {
                    $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), false, null, null, null, null, null, false);
                }
            }

            $bundleCollection->getItemByColumnValue('bundle_id', $bundle->getId())
                    ->setProductName($product->getName())
                    ->setProductPrice($finalPrice)
                    ->setProductSku($product->getSku())
                    ->setImageUrl($this->getImage($product))
                    ->setTypeId($product->getTypeId())
                    ->setHasCustomOptions($product->getOptions() ? 1 : 0)
                    ->setIsSalable(($product->isSalable()) ? 1 : 0)
                    ->setProductUrl($product->getProductUrl());

            $items = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundleitems')->getItemsByBundle($bundle->getBundleId());
            $bundleCollection->getItemByColumnValue('bundle_id', $bundle->getId())->setSelections($items);
        }

        return ($bundleCollection->count() > 0) ? $bundleCollection : null;
    }

    public function getBundlesByCustomerCrosssell($customer)
    {
        $bundleparentid = array();
        $bundleparentidsingle = array();
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->date = $this->_objectManager->create('Magento\Framework\Stdlib\DateTime\DateTime');
        $this->_storeManager = $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface');
        $this->_taxHelper = $this->_objectManager->create('Magento\Tax\Helper\Data');
        $this->_catalogData = $this->_objectManager->create('Magento\Catalog\Helper\Data');

        $cart = $this->_objectManager->create('Magento\Checkout\Model\Cart')->getQuote();
        $bundleids = $cart->getData('bundle_ids');
        if (!empty($bundleids)) {
            $bundleidsary = explode(',', $bundleids);
            for ($v = 0; $v < count($bundleidsary); ++$v) {
                $bundleparentid[] = $this->load($bundleidsary[$v])->getProductId();
            }
        }
        foreach ($cart->getAllItems() as $item) {
            $bundleparentidsingle[] = $item->getData('product_id');
        }
        $bundleparentid = array_unique(array_merge($bundleparentid, $bundleparentidsingle));
        $isLoggedIn = $customer->isLoggedIn();
        $customerGroup = (!$isLoggedIn) ? 0 : $customer->getCustomerGroupId();
        $bundleCollection = $this->getCollection()
                ->addFieldToFilter('status', array('eq' => 1))
                ->addFieldToFilter('product_id', array('in' => $bundleparentid))
                ->addFieldToFilter('store_ids', array(array('finset' => array(0)), array('finset' => array($this->_storeManager->getStore()->getId()))))
                ->addFieldToFilter('customer_groups', array('finset' => array($customerGroup)))
                ->addfieldtofilter('date_from', array(
                    array('to' => $this->date->gmtDate('Y-m-d')),
                    array('date_from', 'null' => ''), ))
                ->addfieldtofilter('date_to', array(
                    array('gteq' => $this->date->gmtDate('Y-m-d')),
                    array('date_to', 'null' => ''), )
                )
                ->setCurPage($this->_pageValue)
                ->setPageSize($this->_limitValue)
                ->setOrder($this->_orderValue, strtoupper($this->_dirValue));

        !empty($bundleidsary) ? $bundleCollection->addFieldToFilter('bundle_id', array('nin' => $bundleidsary)) : '';        //92
        $displayBothPrice = (boolean) $this->_taxHelper->displayBothPrices();
        $displayIncludeTaxPrice = (boolean) $this->_taxHelper->displayPriceIncludingTax();
        foreach ($bundleCollection as $bundle) {
            $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->setStoreId($this->_storeManager->getStore()->getId())->load($bundle->getProductId());
            if ($displayBothPrice) {
                $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), true, null, null, null, null, null, false);
            } else {
                if ($displayIncludeTaxPrice) {
                    $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), true, null, null, null, null, null, false);
                } else {
                    $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), false, null, null, null, null, null, false);
                }
            }

            $bundleCollection->getItemByColumnValue('bundle_id', $bundle->getId())
                    ->setProductName($product->getName())
                    ->setProductPrice($finalPrice)
                    ->setProductSku($product->getSku())
                    ->setImageUrl($this->getImage($product))
                    ->setTypeId($product->getTypeId())
                    ->setHasCustomOptions($product->getOptions() ? 1 : 0)
                    ->setIsSalable(($product->isSalable()) ? 1 : 0)
                    ->setProductUrl($product->getProductUrl());

            $items = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundleitems')->getItemsByBundle($bundle->getBundleId());
            $bundleCollection->getItemByColumnValue('bundle_id', $bundle->getId())->setSelections($items);
        }

        return ($bundleCollection->count() > 0) ? $bundleCollection : null;
    }

    public function getBundlesByProduct($product, $customer)
    {
        $isLoggedIn = $customer->isLoggedIn();
        $customerGroup = (!$isLoggedIn) ? 0 : $customer->getCustomerGroupId();

        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->date = $this->_objectManager->create('Magento\Framework\Stdlib\DateTime\DateTime');
        $this->_storeManager = $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface');
        $this->_taxHelper = $this->_objectManager->create('Magento\Tax\Helper\Data');
        $this->_catalogData = $this->_objectManager->create('Magento\Catalog\Helper\Data');

        if ($product->getId()) {
            $bundleCollection = $this->getCollection()
                    ->addFieldToFilter('product_id', array('eq' => $product->getId()))
                    ->addFieldToFilter('status', array('eq' => 1))
                    ->addFieldToFilter('store_ids', array(array('finset' => array(0)), array('finset' => array($this->_storeManager->getStore()->getId()))))
                    ->addFieldToFilter('customer_groups', array('finset' => array($customerGroup)))
                    ->addfieldtofilter('date_from', array(
                        array('to' => $this->date->gmtDate('Y-m-d')),
                        array('date_from', 'null' => ''), ))
                    ->addfieldtofilter('date_to', array(
                        array('gteq' => $this->date->gmtDate('Y-m-d')),
                        array('date_to', 'null' => ''), )
                    )
                    ->setCurPage($this->_pageValue)
                    ->setPageSize($this->_limitValue)
                    ->setOrder($this->_orderValue, strtoupper($this->_dirValue));
            $displayBothPrice = (boolean) $this->_taxHelper->displayBothPrices();
            $displayIncludeTaxPrice = (boolean) $this->_taxHelper->displayPriceIncludingTax();
            foreach ($bundleCollection as $bundle) {
                $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->setStoreId($this->_storeManager->getStore()->getId())->load($bundle->getProductId());
                if ($displayBothPrice) {
                    $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), true, null, null, null, null, null, false);
                } else {
                    if ($displayIncludeTaxPrice) {
                        $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), true, null, null, null, null, null, false);
                    } else {
                        $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), false, null, null, null, null, null, false);
                    }
                }

                $bundleCollection->getItemByColumnValue('bundle_id', $bundle->getId())
                        ->setProductName($product->getName())
                        ->setProductPrice($finalPrice)
                        ->setProductSku($product->getSku())
                        ->setImageUrl($this->getImage($product))
                        ->setTypeId($product->getTypeId())
                        ->setHasCustomOptions($product->getOptions() ? 1 : 0)
                        ->setIsSalable(($product->isSalable()) ? 1 : 0)
                        ->setProductUrl($product->getProductUrl());

                $items = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundleitems')->getItemsByBundle($bundle->getBundleId());
                $bundleCollection->getItemByColumnValue('bundle_id', $bundle->getId())->setSelections($items);
            }

            return ($bundleCollection->count() > 0) ? $bundleCollection : null;
        } else {
            return;
        }
    }

    public function getBundleObjects($bundleIds = array(), $object = null, $customer)
    {
        if (!is_array($bundleIds)) {
            $bundleIds = array($bundleIds);
        }
        $isLoggedIn = $customer->isLoggedIn();
        $customerGroup = (!$isLoggedIn) ? 0 : $customer->getCustomerGroupId();

        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->date = $this->_objectManager->create('Magento\Framework\Stdlib\DateTime\DateTime');
        $this->_storeManager = $this->_objectManager->create('Magento\Store\Model\StoreManagerInterface');
        $this->_taxHelper = $this->_objectManager->create('Magento\Tax\Helper\Data');
        $this->_catalogData = $this->_objectManager->create('Magento\Catalog\Helper\Data');

        if (count($bundleIds)) {
            $bundleCollection = $this->getCollection()
                    ->addFieldToFilter('status', array('eq' => 1))
                    ->addFieldToFilter('store_ids', array(array('finset' => array(0)), array('finset' => array($this->_storeManager->getStore()->getId()))))
                    ->addFieldToFilter('customer_groups', array('finset' => array($customerGroup)))
                    ->addfieldtofilter('date_from', array(
                        array('to' => $this->date->gmtDate('Y-m-d')),
                        array('date_from', 'null' => ''), ))
                    ->addfieldtofilter('date_to', array(
                        array('gteq' => $this->date->gmtDate('Y-m-d')),
                        array('date_to', 'null' => ''), )
                    )
                    ->addFieldToFilter('bundle_id', array('in' => $bundleIds))
                    ->setCurPage($this->_pageValue)
                    ->setPageSize($this->_limitValue)
                    ->setOrder($this->_orderValue, strtoupper($this->_dirValue));

            $displayBothPrice = (boolean) $this->_taxHelper->displayBothPrices();
            $displayIncludeTaxPrice = (boolean) $this->_taxHelper->displayPriceIncludingTax();
            foreach ($bundleCollection as $bundle) {
                $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->setStoreId($this->_storeManager->getStore()->getId())->load($bundle->getProductId());
                if ($displayBothPrice) {
                    $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), true, null, null, null, null, null, false);
                } else {
                    if ($displayIncludeTaxPrice) {
                        $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), true, null, null, null, null, null, false);
                    } else {
                        $finalPrice = $this->_catalogData->getTaxPrice($product, $product->getFinalPrice(), false, null, null, null, null, null, false);
                    }
                }

                $bundleCollection->getItemByColumnValue('bundle_id', $bundle->getId())
                        ->setProductName($product->getName())
                        ->setProductPrice($finalPrice)
                        ->setProductSku($product->getSku())
                        ->setImageUrl($this->getImage($product))
                        ->setTypeId($product->getTypeId())
                        ->setHasCustomOptions($product->getOptions() ? 1 : 0)
                        ->setIsSalable(($product->isSalable()) ? 1 : 0)
                        ->setProductUrl($product->getProductUrl());

                $items = $this->_objectManager->create('Magedelight\Bundlediscount\Model\Bundleitems')->getItemsByBundle($bundle->getBundleId());
                $bundleCollection->getItemByColumnValue('bundle_id', $bundle->getId())->setSelections($items);

                if ($object && $object->count() > 0) {
                    $object->addItem($bundleCollection->getItemByColumnValue('bundle_id', $bundle->getId())->setSelections($items));
                }
            }
            if ($object && $object->count() > 0) {
                return $object;
            } else {
                return ($bundleCollection->count() > 0) ? $bundleCollection : null;
            }
        } else {
            return;
        }
    }

    public function calculateDiscountAmount($bundle)
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helper = $this->_objectManager->create('Magedelight\Bundlediscount\Helper\Data');
        $priceHelper = $this->_objectManager->create('Magento\Framework\Pricing\Helper\Data');

        $result = array('total_amount' => 0.00, 'discount_amount' => 0.00, 'final_amount' => 0.00, 'discount_label' => 0.0);
        $totalAmount = 0.00;
        $discountAmount = 0.00;
        $finalAmount = 0.00;
        $discountLabel = '';
        if ($bundle->getId()) {
            $excludeFromBaseProductFlag = ($bundle->getExcludeBaseProduct() == 0) ? false : true;
            if (!$excludeFromBaseProductFlag) {
                $totalAmount += $bundle->getProductPrice() * $bundle->getQty();
            }
            if ($bundle->getSelections() != null) {
                foreach ($bundle->getSelections() as $_selection) {
                    $totalAmount += ($_selection->getQty() * $_selection->getPrice());
                }
            }

            if ($bundle->getDiscountType() == 0) {
                $discountLabel = $priceHelper->currency($bundle->getDiscountPrice(), true, false);
                $discountAmount = (float) $bundle->getDiscountPrice();
            } else {
                $discountAmount = ($bundle->getDiscountPrice() * $totalAmount) / 100;
                $discountLabel = $this->_helper->formatPercentage($bundle->getDiscountPrice()).'%';
            }

            if ($discountAmount > $totalAmount) {
                $discountAmount = $totalAmount;
            }
            $finalAmount = $totalAmount - $discountAmount;
            $result['total_amount'] = $totalAmount;
            $result['discount_amount'] = $discountAmount;
            $result['final_amount'] = $finalAmount;
            $result['discount_label'] = $discountLabel;
        }

        return $result;
    }

    public function canShowAddToCartButton()
    {
        $canshow = true;
        $selections = $this->getSelections();
        if ($this->getIsSalable() == 0) {
            $canshow = false;
        }
        if ($canshow) {
            foreach ($selections as $_selection) {
                if ($_selection->getIsSalable() == 0) {
                    $canshow = false;
                    break;
                }
            }
        }

        return $canshow;
    }

    public function getImage($product)
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $image = 'category_page_list';
        $this->imageBuilder = $this->_objectManager->create('Magento\Catalog\Helper\Image');

        return $this->imageBuilder->init($product, $image)->constrainOnly(false)->keepTransparency(true)->keepAspectRatio(true)->keepFrame(true)->backgroundColor(array(255, 255, 255))->resize(90, 90)->getUrl();
    }

    public function hasOptions($excludeSelection = false)
    {
        $hasOptions = false;
        $productTypes = array('grouped', 'configurable', 'bundle', 'downloadable');
        $selections = $this->getSelections();

        if (in_array($this->getTypeId(), $productTypes)) {
            $hasOptions = true;
        }
        if (!$hasOptions && !$excludeSelection) {
            foreach ($selections as $_selection) {
                if (in_array($_selection->getTypeId(), $productTypes)) {
                    $hasOptions = true;
                    break;
                }
            }
        }

        return $hasOptions;
    }

    public function hasCustomOptions($excludeSelection = false)
    {
        $hasCustomOptions = false;
        $selections = $this->getSelections();

        if ($this->getHasCustomOptions() == 1) {
            $hasCustomOptions = true;
        }
        if (!$hasCustomOptions && !$excludeSelection) {
            foreach ($selections as $_selection) {
                if ($_selection->getHasCustomOptions() == 1) {
                    $hasCustomOptions = true;
                    break;
                }
            }
        }

        return $hasCustomOptions;
    }

    public function calculateProductQtys($bundleIds)
    {
        if (!is_array($bundleIds)) {
            $bundleIds = array($bundleIds);
        }
        $result = array();
        foreach ($bundleIds as $id) {
            $bundle = $this->load($id);
            if (!isset($result[$bundle->getProductId()])) {
                $result[$bundle->getProductId()][$id] = $bundle->getQty();
            } else {
                $result[$bundle->getProductId()][$id] = $bundle->getQty();
            }
            foreach ($bundle->getSelections() as $_selection) {
                if (!isset($result[$_selection->getProductId()])) {
                    $result[$_selection->getProductId()][$id] = $_selection->getQty();
                } else {
                    $result[$_selection->getProductId()][$id] = $_selection->getQty();
                }
            }
        }

        return $result;
    }
}
