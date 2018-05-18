<?php
/**
 * ITORIS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the ITORIS's Magento Extensions License Agreement
 * which is available through the world-wide-web at this URL:
 * http://www.itoris.com/magento-extensions-license.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to sales@itoris.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extensions to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to the license agreement or contact sales@itoris.com for more information.
 *
 * @category   ITORIS
 * @package    ITORIS_M2_DYNAMIC_PRODUCT_OPTIONS
 * @copyright  Copyright (c) 2016 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

namespace Itoris\DynamicProductOptions\Observers;

use Magento\Framework\Event\ObserverInterface;

class CheckQuoteItemQty implements ObserverInterface
{
    protected $isEnabledFlag = false;
    /**
     * @var \Magento\Framework\ObjectManagerInterface|null
     */
    protected $_objectManager = null;
    /**
     * @var \Magento\Framework\App\RequestInterface|null
     */
    protected $_request = null;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_objectManager = $objectManager;
        $this->_request = $request;
        try {
            $this->isEnabledFlag = $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Data')->getSettings(true)->getEnabled();
        } catch (\Exception $e) {/** save store model */}
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {

        if (!$this->isEnabledFlag) {
            return null;
        }
        /* @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $observer->getEvent()->getItem();
        if (!$quoteItem || !$quoteItem->getProductId() || !$quoteItem->getQuote()
            || $quoteItem->getQuote()->getIsSuperMode()) {
            return $this;
        }
        $product = $quoteItem->getProduct();
        if (!$product->getHasOptions() || !$product->getCustomOption('info_buyRequest')) {
            return null;
        }
        $buyRequest = $product->getCustomOption('info_buyRequest')->getValue();
        if ($buyRequest) {
            $post = json_decode($buyRequest, true); //in M2.2 json used for the buy request
            if (is_null($post)) $post = unserialize($buyRequest); //in M<2.2 the buy request is serialized
        }
        $options = false;
        if (isset($post['options'])) {
            $options = $post['options'];
        }


        if ($options) {
            $qty = $quoteItem->getQty();
            if (!$qty) {
                $qty = $this->_request->getParam('qty', 1);
            }

            foreach ($options as $optionId => $option) {
                /** @var  $productOption \Magento\Catalog\Model\Product\Option */
                $productOption = $this->_objectManager->create('Magento\Catalog\Model\Product\Option')->load($optionId);
                $optionType = $productOption->getType();
                if ($productOption->getGroupByType($optionType) != \Magento\Catalog\Model\Product\Option::OPTION_GROUP_SELECT) {
                    continue;
                }
                if (!is_array($option)) {
                    $option = [$option];
                }
                foreach ($option as $optionValueId) {
                    if (!$optionValueId) {
                        continue;
                    }

                    /** @var  $dynamicValue \Itoris\DynamicProductOptions\Model\Option\Value */
                    $dynamicValue = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option\Value')->load($optionValueId, 'orig_value_id');
                    $valueConfiguration = $dynamicValue->getConfiguration();
                    if ($valueConfiguration) {
                        $valueConfiguration = \Zend_Json::decode($valueConfiguration);
                        if (isset($valueConfiguration['sku_is_product_id']) && $valueConfiguration['sku_is_product_id']) {
                            /** @var  $valueModel \Magento\Catalog\Model\Product\Option\Value*/
                            $valueModel = $this->_objectManager->create('Magento\Catalog\Model\Product\Option\Value')->load($optionValueId);
                            /** @var $valueProduct \Magento\Catalog\Model\Product */
                            $valueProduct = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($valueModel->getSku());
                            /* @var $stockItem \Magento\CatalogInventory\Model\Stock\Item */
                            $stockItem = $this->_objectManager->create('Magento\CatalogInventory\Model\Stock\Item')->load($valueProduct->getId());

                            $buyRequest = $quoteItem->getBuyRequest();
                            $optionsQty = $buyRequest->getOptionsQty();
                            $optionQty = 1;
                            if (is_array($optionsQty)) {
                                if (in_array($productOption->getType(), ['radio', 'drop_down'])) {
                                    if (isset($optionsQty[$productOption->getId()])) {
                                        $optionQty = (int)$optionsQty[$productOption->getId()];
                                    }
                                } else {
                                    if (isset($optionsQty[$productOption->getId()][$optionValueId])) {
                                        $optionQty = (int)$optionsQty[$productOption->getId()][$optionValueId];
                                    }
                                }
                            }

                            if ($stockItem->getManageStock() && (!$stockItem->getIsInStock() || (int)$stockItem->getQty() < $qty * $optionQty)) {
                                $quoteItem->addErrorInfo(
                                    'cataloginventory',
                                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                                    __('This product is out of stock.')
                                );
                                $quoteItem->getQuote()->addErrorInfo(
                                    'stock',
                                    'cataloginventory',
                                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                                    __('Some of the products are out of stock.')
                                );
                                return;
                            }
                        }
                    }
                }
            }
        }
    }
}