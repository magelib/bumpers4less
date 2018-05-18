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

class UpdateOrderInventory implements ObserverInterface
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

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\App\RequestInterface $request
     */
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
            return $this;
        }

        $eventName = $observer->getEvent()->getName();
        $post = $this->_request->getParams();

        if ($eventName == 'sales_order_creditmemo_refund') {
            $orderItems = $observer->getEvent()->getCreditmemo()->getOrder()->getAllItems();
        } else {
            $orderItems = $observer->getEvent()->getOrder()->getAllItems();
        }

        foreach ($orderItems as $orderItem) {
            $productOptions = $orderItem->getProductOptions();
            if (!isset($productOptions['options'])) {
                continue;
            }

            $qty = $orderItem->getQtyOrdered();
            foreach ($productOptions['options'] as $option) {
                switch ($option['option_type']) {
                    case 'drop_down':
                    case 'radio':
                    case 'checkbox':
                    case 'multiple':
                        $optionTypeIds = explode(',', $option['option_value']);

                        foreach ($optionTypeIds as $optionTypeId) {
                            /** @var  $dynamicValue \Itoris\DynamicProductOptions\Model\Option\Value */
                            $dynamicValue = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option\Value')->load($optionTypeId, 'orig_value_id');
                            $valueConfiguration = $dynamicValue->getConfiguration();
                            if ($valueConfiguration) {
                                $valueConfiguration = \Zend_Json::decode($valueConfiguration);
                                if (isset($valueConfiguration['sku_is_product_id']) && $valueConfiguration['sku_is_product_id']) {
                                    /** @var  $valueModel \Magento\Catalog\Model\Product\Option\Value */
                                    $valueModel = $this->_objectManager->create('Magento\Catalog\Model\Product\Option\Value')->load($optionTypeId);
                                    /** @var $valueProduct \Magento\Catalog\Model\Product */
                                    $valueProduct = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($valueModel->getSku());
                                    if ($valueProduct->getId()) {
                                        $item = $this->_objectManager->create('Magento\CatalogInventory\Model\Stock\Item')->load($valueProduct->getId());;
                                        if ($item->getManageStock()) {
                                            $buyRequest = $orderItem->getBuyRequest();
                                            $optionsQty = $buyRequest->getOptionsQty();
                                            $optionQty = 1;
                                            if (is_array($optionsQty)) {
                                                if (in_array($option['option_type'], ['radio', 'drop_down'])) {
                                                    if (isset($optionsQty[$option['option_id']])) {
                                                        $optionQty = (int)$optionsQty[$option['option_id']];
                                                    }
                                                } else {
                                                    if (isset($optionsQty[$option['option_id']][$optionTypeId])) {
                                                        $optionQty = (int)$optionsQty[$option['option_id']][$optionTypeId];
                                                    }
                                                }
                                            }
                                            if ($eventName == 'order_cancel_after') {
                                                $item->setQty($item->getQty() + $qty * $optionQty);
                                            } else if ($eventName == 'sales_order_creditmemo_refund') {
                                                $qtyToRefund = intval($post['creditmemo']['items'][$orderItem->getId()]['qty']);
                                                if ($qtyToRefund > 0) $item->setQty($item->getQty() + $qtyToRefund * $optionQty);
                                            } else {
                                                $item->setQty($item->getQty() - $qty * $optionQty);
                                            }
                                            $item->save();
                                        }
                                    }
                                }
                            }
                        }
                        break;
                }
            }
        }

        return $this;
    }
}