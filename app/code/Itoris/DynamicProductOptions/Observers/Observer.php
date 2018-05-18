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

namespace Itoris\DynamicProductOptions\Model;

class Observer
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

    /**
     * save product options configuration after product save action
     *
     * @param $observer \Magento\Framework\Event\Observer
     */
    public function saveProductOptions(\Magento\Framework\Event\Observer $observer) {
        if (!$this->isEnabledFlag) {
            return null;
        }
        $optionsConfig = $this->_request->getParam('itoris_dynamicproductoptions');
        if (is_array($optionsConfig)) {
            $product = $observer->getProduct();
            $storeId = $this->_request->getParam('store', 0);
            /** @var \Itoris\DynamicProductOptions\Model\Options $options */
            $options = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Options')
                ->setStoreId($storeId)
                ->load($product->getId())
                ->addData($optionsConfig);

            $isUseGlobal = !!$this->_request->getPostValue('idpo_use_global');
            if ((int) $storeId == 0) $isUseGlobal = false;
            if (!$options->getId()) {
                if ($isUseGlobal) return;
                $options->setProductId($product->getId())
                    ->setStoreId($storeId);
            }
            if ($isUseGlobal) $options->delete();

            $options->save();
        }
    }


    /**
     * save product option or option value after save action
     *
     * @param $observer \Magento\Framework\Event\Observer
     */
    public function saveOptionOrOptionValue(\Magento\Framework\Event\Observer $observer) {
        if ($this->isEnabledFlag) {
            $object = $observer->getObject();
            if($object instanceof \Magento\Catalog\Model\Product\Option){
                /** @var \Itoris\DynamicProductOptions\Model\Option $optionsModel */
                $optionsModel = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option');
                $optionsModel->saveOption($object);
            } elseif ($object instanceof \Magento\Catalog\Model\Product\Option\Value) {
                /** @var \Itoris\DynamicProductOptions\Model\Option\Value $valueModel */
                $valueModel = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option\Value');
                $valueModel->saveValue($object);
            }
        }
    }

    public function unserializeParams() {
        $paramsStr = $this->_request->getParam('itoris_dynamicproductoptions_serialized');
        if ($paramsStr) {
            $params = [];
            parse_str($paramsStr, $params);

            if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() && isset($params['itoris_dynamicproductoptions']['configuration'])) {
                $params['itoris_dynamicproductoptions']['configuration'] = stripslashes($params['itoris_dynamicproductoptions']['configuration']);
                if (isset($params['product']['options'])) {
                    foreach ($params['product']['options'] as &$option) {
                        if (isset($option['static_text'])) {
                            $option['static_text'] = stripslashes($option['static_text']);
                        }
                    }
                }
            }

            $this->_addParams($params, $this->_request->getPost());
        }
    }

    protected function _addParams($params, &$toArray) {
        foreach ($params as $key => $value) {
            if (array_key_exists($key, $toArray)) {
                if (is_array($toArray[$key]) && is_array($value)) {
                    $this->_addParams($value, $toArray[$key]);
                }
            } else {
                $toArray[$key] = $value;
            }
        }
    }

    public function checkQuoteItemQty($observer) {
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
            $post = unserialize($buyRequest);
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
                            $stockItem = $valueProduct->getStockItem();

                            $stockItem->setIsChildItem(true);
                            /**
                             * don't check qty increments value for option product
                             */
                            $stockItem->setSuppressCheckQtyIncrements(true);

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

                            $result = $stockItem->checkQuoteItemQty($qty * $optionQty, $qty);


                            if ($result->getHasError()) {
                                //$option->setHasError(true);

                                $quoteItem->addErrorInfo(
                                    'cataloginventory',
                                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                                    $result->getQuoteMessage()
                                );

                                $quoteItem->getQuote()->addErrorInfo(
                                    $result->getQuoteMessageIndex(),
                                    'cataloginventory',
                                    \Magento\CatalogInventory\Helper\Data::ERROR_QTY,
                                    $result->getQuoteMessage()
                                );
                                return;
                            }
                        }
                    }
                }
            }
        }
    }

    public function changeItemWeight($observer) {
        if (!$this->isEnabledFlag) {
            return $this;
        }
        /* @var $quoteItem \Magento\Quote\Model\Quote\Item */
        $quoteItem = $observer->getQuoteItem();
        if (!$quoteItem || !$quoteItem->getProductId() || !$quoteItem->getQuote()) {
            return $this;
        }
        $product = $observer->getProduct();
        $buyRequest = $quoteItem->getBuyRequest();
        $options = $buyRequest->getOptions();

        if (is_array($options)) {
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
                    $dynamicValue = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option\Value')->load($optionValueId, 'orig_value_id');
                    $valueConfiguration = $dynamicValue->getConfiguration();
                    if ($valueConfiguration) {
                        $valueConfiguration = \Zend_Json::decode($valueConfiguration);
                        if (isset($valueConfiguration['sku_is_product_id']) && $valueConfiguration['sku_is_product_id']) {
                            /** @var  $valueModel \Magento\Catalog\Model\Product\Option\Value*/
                            $valueModel = $this->_objectManager->create('Magento\Catalog\Model\Product\Option\Value')->load($optionValueId);
                            /** @var $valueProduct \Magento\Catalog\Model\Product */
                            $valueProduct = $this->_objectManager->create('Magento\Catalog\Model\Product')->load($valueModel->getSku());

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
                            $optionProductWeight = $optionQty * $valueProduct->getWeight();
                            $quoteItem->setWeight($quoteItem->getWeight() + $optionProductWeight);
                        }
                        elseif(isset($valueConfiguration['weight']) && $valueConfiguration['weight']){
                            $quoteItem->setWeight($quoteItem->getWeight() + $valueConfiguration['weight']);
                        }
                    }
                }
            }
        }
    }

    public function quoteSubmitBefore($observer) {
        if (!$this->isEnabledFlag) {
            return $this;
        }
        $orderItems = $observer->getEvent()->getOrder()->getAllItems();

        foreach ($orderItems as &$orderItem) {
            $productOptions = &$orderItem->getProductOptions();
            if (!isset($productOptions['options'])) {
                continue;
            }

            foreach ($productOptions['options'] as &$option) {
                switch ($option['option_type']) {
                    case 'radio':
                    case 'checkbox':
                        $option['value'] = preg_replace("/<img[^>]+\>/i", "\n", $option['value']); ;
                        $option['print_value'] = preg_replace("/<img[^>]+\>/i", "\n", $option['print_value']); ;
                        break;
                }
            }

            $orderItem->setProductOptions($productOptions);
        }
        $observer->getEvent()->getOrder()->setItems($orderItems);

    }

    public function addMassactionToProductGrid($observer) {
        if ($this->isEnabledFlag
            && $observer->getBlock() instanceof \Magento\Catalog\Block\Adminhtml\Product\Grid
            && $observer->getBlock()->getMassactionBlock()
        ) {
            $observer->getBlock()->getMassactionBlock()->addItem('copy_dynamic_options', [
                'label'=> __('Copy Custom Options 1 to Many'),
                'url'  => $observer->getBlock()->getUrl('dynamicproductoptions/admin_product_options/massCopy', ['_current'=>true]),
                'additional' => [
                    'visibility' => [
                        'name' => 'from_product_id',
                        'type' => 'text',
                        'class' => 'required-entry',
                        'label' => __('Product Id'),
                    ]
                ]
            ]);
            /** @var  $templates \Itoris\DynamicProductOptions\Model\Template */
            $templates = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Template')->getCollection();
            $templates->getSelect()->order('name asc');
            $templateValues = [['value' => '', 'label' => __('Please select')]];
            if (count($templates)) {
                foreach ($templates as $template) {
                    $templateValues[] = [
                        'value' => $template->getId(),
                        'label' => $template->getName(),
                    ];
                }
            }
            $observer->getBlock()->getMassactionBlock()->addItem('load_dynamic_options', [
                'label'=> __('Load Options Template 1 to Many'),
                'url'  => $observer->getBlock()->getUrl('dynamicproductoptions/product_options/massLoad', ['_current'=>true]),
                'additional' => [
                    'visibility'=> [
                        'name'   => 'template_id',
                        'type'   => 'select',
                        'class'  => 'required-entry',
                        'label'  => __('Template'),
                        'values' => $templateValues,
                    ]
                ]
            ]);
        }
    }

    public function orderComposeProductOptions($observer) {
        if ($this->isEnabledFlag && $observer->getBlock() instanceof \Magento\Catalog\Block\Adminhtml\Product\Composite\Fieldset\Options) {
            $dpoBlock = $observer->getBlock()->getLayout()->createBlock('Itoris\DynamicProductOptions\Block\Options\Config');
            $dpoBlock->getConfig()->setAppearance('on_product_view');
            if ($dpoBlock->getProduct()->getTypeId() == 'grouped') return;
            $dpoBlock->setTemplate('/product/composite/fieldset/options.phtml');
            $transport = $observer->getTransport();
            $html = $transport->getHtml();
            $html .= $dpoBlock->toHtml();
            $transport->setHtml($html);
        }
    }

    public function updateOrderInventory($observer) {
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
                                        $item = $this->_objectManager->create('Magento\Cataloginventory\Model\Stock\Item')->loadByProduct($valueProduct);
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
                                                $item->addQty($qty * $optionQty);
                                            } else if ($eventName == 'sales_order_creditmemo_refund') {
                                                $qtyToRefund = intval($post['creditmemo']['items'][$orderItem->getId()]['qty']);
                                                if ($qtyToRefund > 0) $item->addQty($qtyToRefund * $optionQty);
                                            } else {
                                                $item->subtractQty($qty * $optionQty);
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