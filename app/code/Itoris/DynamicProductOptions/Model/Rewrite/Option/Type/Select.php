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

namespace Itoris\DynamicProductOptions\Model\Rewrite\Option\Type;

class Select extends \Magento\Catalog\Model\Product\Option\Type\Select
{
    protected $isEnabledDynamicOptions = false;
    /** @var \Magento\Framework\ObjectManagerInterface|null  */
    protected $_objectManager = null;
    protected $_httpProtocol = '';
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Framework\Escaper $escaper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Escaper $escaper,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        $this->isEnabledDynamicOptions = $this->getItorisHelper()->isEnabledOnFrontend();
        $this->_backendConfig = $this->_objectManager->get('Magento\Backend\App\ConfigInterface');
        parent::__construct($checkoutSession, $scopeConfig, $string, $escaper, $data);
    }

    public function isCustomizedView() {
        if ($this->isEnabledDynamicOptions) {
            return true;
        }
        return parent::isCustomizedView();
    }

    public function getCustomizedView($optionInfo) {
        if ($this->isEnabledDynamicOptions) {
            return $optionInfo['value'];
        }
        return parent::getCustomizedView($optionInfo);
    }

    public function getFormattedOptionValue($optionValue) {
        if ($this->_formattedOptionValue === null) {
            if ($this->isEnabledDynamicOptions) {
                $this->_formattedOptionValue = $this->getEditableOptionValue($optionValue);
            } else {
                parent::getFormattedOptionValue($optionValue);
            }
        }
        return $this->_formattedOptionValue;
    }

    public function getEditableOptionValue($optionValue) {
        if (!$this->isEnabledDynamicOptions) {
            return parent::getEditableOptionValue($optionValue);
        }
        $optionQtys = null;
        try {
            $itemOption = $this->getConfigurationItemOption();
            if ($itemOption) {
                /** @var $item \Magento\Quote\Model\Quote\Item */
                $item = $itemOption->getItem();
                if ($item) {
                    $buyRequest = $item->getBuyRequest();
                    $optionQtys = $buyRequest->getOptionsQty();
                }
            }
        } catch (\Exception $e) {}
        $option = $this->getOption();
        $result = '';
        if (!$this->_isSingleSelection()) {
            $shouldCut = false;
            foreach (explode(',', $optionValue) as $_value) {
                if ($_result = $option->getValueById($_value)) {
                    $qty = isset($optionQtys[$option->getId()][$_value]) ? $optionQtys[$option->getId()][$_value] : null;
                    
                    $result .= ($qty ? $qty . ' x ' : '') . htmlspecialchars($_result->getTitle());
                    if ($_result->isLinkedProduct()) {
                        $result .= ' ('.__('SKU').': '.$_result->getSku().')';
                    }

                    if ($this->_canHasImage($option->getType())) {
                        $imageSrc = $this->_getValueImageSrc($_value);
                        if ($imageSrc) {
                            $result .= '<img src="' . $imageSrc . '" style="display:block"/>';
                        } else {
                            $result .= '<br/>';
                        }
                        $shouldCut = false;
                    } else {
                        $result .= ', ';
                        $shouldCut = true;
                    }
                } else {
                    if ($this->getListener()) {
                        $this->getListener()
                            ->setHasError(true)
                            ->setMessage(
                                $this->_getWrongConfigurationMessage()
                            );
                        $result = '';
                        break;
                    }
                }
            }
            if ($shouldCut) {
                $result = $this->string->substr($result, 0, -2);
            }
        } elseif ($this->_isSingleSelection()) {
            if ($_result = $option->getValueById($optionValue)) {
                $qty = isset($optionQtys[$option->getId()]) ? $optionQtys[$option->getId()] : null;
                
                $result = ($qty ? $qty . ' x ' : '') . htmlspecialchars($_result->getTitle());
                if ($_result->isLinkedProduct()) {
                    $result .= ' ('.__('SKU').': '.$_result->getSku().')';
                }
                
                if ($this->_canHasImage($option->getType())) {
                    $imageSrc = $this->_getValueImageSrc($optionValue);
                    if ($imageSrc) {
                        $result .= '<img src="' . $imageSrc . '" style="display:block"/>';
                    }
                }
            } else {
                if ($this->getListener()) {
                    $this->getListener()
                        ->setHasError(true)
                        ->setMessage(
                            $this->_getWrongConfigurationMessage()
                        );
                }
                $result = '';
            }
        } else {
            $result = $optionValue;
        }
        return $result;
    }

    protected function _canHasImage($type) {
        return in_array($type, ['radio', 'checkbox']);
    }

    protected function _getValueImageSrc($valueId) {
        $value = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option\Value')->load($valueId, 'orig_value_id');
        $img_src = $value->getImageSrc();
        //if ($this->getItorisHelper()->isAdmin()) return $img_src;
        if ($img_src) {
            if (!$this->_httpProtocol) {
                $is_frontend_use_secure = (int) $this->_backendConfig->getValue('web/secure/use_in_frontend');
                $is_admin_use_secure = (int) $this->_backendConfig->getValue('web/secure/use_in_adminhtml');
                $baseUrl = ($is_frontend_use_secure || $is_admin_use_secure) ? $this->_backendConfig->getValue('web/secure/base_url') : $this->_backendConfig->getValue('web/unsecure/base_url');
                $this->_httpProtocol = strpos($baseUrl, 'ttps:') !== false ? 'https://' : 'http://';
            }
            $img_src = str_ireplace(['http://', 'https://', '//'], $this->_httpProtocol, $img_src);
        }
        return $img_src;
    }

    public function validateUserValue($values) {
        if ($this->getItorisHelper()->isEnabledOnFrontend()) {
            try {
                return parent::validateUserValue($values);
            } catch (\Exception $e) {
                $this->getItorisHelper()->addOptionError($this->getOption(), $this->getProduct(), $e->getMessage());
                //    Mage::throwException($e->getMessage());
            }
        } else {
            return parent::validateUserValue($values);
        }
        return $this;
    }
    
    public function getOptionPrice($optionValue, $basePrice) {
        $price = $this->_getOptionPrice($optionValue, $basePrice); //relative price
        if ($this->getItorisHelper()->isEnabledOnFrontend()) {
            $product = $this->getOption()->getProduct();
            if ($product->getOptionsAbsolutePricing()) return $price;
            $dpoObj = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Options')->setStoreId($product->getStoreId())->load($product->getId(), 'product_id');
            if (!$dpoObj->getConfigId()) $dpoObj->setStoreId(0)->load($product->getId(), 'product_id');
            if ($dpoObj->getAbsolutePricing() == 1) { //absolute price
                $price -= $product->getFinalPrice();
                $product->setOptionsAbsolutePricing(1);
            } else if ($dpoObj->getAbsolutePricing() == 2) { //fixed price
                $price = 0;
            } else $product->setOptionsAbsolutePricing(2);
        }
        return $price;
    }
    
    public function _getOptionPrice($optionValue, $basePrice) {
        $optionQtys = null;
        $productQty = 0;
        $_optionTierPrice = 0;
        if ($this->isEnabledDynamicOptions) {
            try {
                $itemOption = $this->getConfigurationItemOption();
                if ($itemOption) {
                    /** @var $item \Magento\Quote\Model\Quote\Item */
                    $item = $itemOption->getItem();
                    if ($item) {
                        $buyRequest = $item->getBuyRequest();
                        $optionQtys = $buyRequest->getOptionsQty();
                        $productQty = (int) $item->getQty();
                        $_result = $this->getOption()->getValueById($optionValue);
                        if ($_result) $_optionTierPrice = (float)$this->getTierPriceByQty($optionValue, $productQty, $_result->getPrice(), $_result->getPriceType())[0];
                    }
                }
            } catch (\Exception $e) {}
        }
        $canUseQtys = false;
        if ($optionQtys && isset($optionQtys[$this->getOption()->getId()])) {
            if ($this->_isSingleSelection()) {
                $canUseQtys = (int)$optionQtys[$this->getOption()->getId()] > 1;
            } elseif (is_array($optionQtys[$this->getOption()->getId()])) {
                foreach ($optionQtys[$this->getOption()->getId()] as $valueQty) {
                    //if ((int)$valueQty > 1) {
                        $canUseQtys = true;
                        break;
                    //}
                }
            }
        }
        if ($canUseQtys || $_optionTierPrice) {
            $option = $this->getOption();
            $result = 0;
            $optionQty = isset($optionQtys[$option->getId()]) ? $optionQtys[$option->getId()] : $productQty;
            if (!$this->_isSingleSelection()) {
                foreach(explode(',', $optionValue) as $value) {
                    if ($_result = $option->getValueById($value)) {
                        $qty = isset($optionQty[$value]) ? (int)$optionQty[$value] : 1;
                        list($price, $price_type) = $this->getTierPriceByQty($value, !$canUseQtys ? $productQty : $qty, $_result->getPrice(), $_result->getPriceType());
                        $result += $this->_getChargableOptionPrice(
                            $price * (!$canUseQtys ? 1 : $qty),
                            $price_type == 'percent',
                            $basePrice
                        );
                    } else {
                        if ($this->getListener()) {
                            $this->getListener()
                                ->setHasError(true)
                                ->setMessage(
                                    $this->_getWrongConfigurationMessage()
                                );
                            break;
                        }
                    }
                }
            } elseif ($this->_isSingleSelection()) {
                if ($_result = $option->getValueById($optionValue)) {
                    list($price, $price_type) = $this->getTierPriceByQty($optionValue, !$canUseQtys ? $productQty : (int)$optionQty, $_result->getPrice(), $_result->getPriceType());
                    $result = $this->_getChargableOptionPrice(
                        $price * (!$canUseQtys ? 1 : (int)$optionQty),
                        $price_type == 'percent',
                        $basePrice
                    );
                } else {
                    if ($this->getListener()) {
                        $this->getListener()
                            ->setHasError(true)
                            ->setMessage(
                                $this->_getWrongConfigurationMessage()
                            );
                    }
                }
            }

            return $result;
        }

        return parent::getOptionPrice($optionValue, $basePrice);
    }

    public function getTierPriceByQty($valueId, $qty, $price, $price_type){
        /** @var \Magento\Framework\App\ResourceConnection $resource */
        $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection('read');
        $priceCurrency = $this->_objectManager->get('Magento\Framework\Pricing\PriceCurrencyInterface');
        $option_value_table = $resource->getTableName('itoris_dynamicproductoptions_option_value');
        $storeId = $this->_objectManager->create('\Magento\Store\Model\StoreManagerInterface')->getStore()->getStoreId();
        $config = $connection->fetchOne("select `configuration` from {$option_value_table} where `orig_value_id` = ".floatval($valueId)." and store_id = ".intval($storeId));
        if (!$config && intval($storeId) > 0) $config = $connection->fetchOne("select `configuration` from {$option_value_table} where `orig_value_id` = ".floatval($valueId)." and store_id = 0");
        //$value = Mage::getModel('itoris_dynamicproductoptions/option_value')->load($valueId, 'orig_value_id');
        //$config = $value->getConfiguration();
        if (!$config) return [$price, $price_type];
        $config = json_decode($config, true);
        if (isset($config['sku_is_product_id_linked']) && (int) $config['sku_is_product_id_linked'] && (int) $config['sku_is_product_id']) {
            $product = $this->_objectManager->create('Magento\Catalog\Model\Product')->load((int) $config['sku']);
            return [$product->getTierPrice($qty), 'fixed'];
        }

        if (!isset($config['tier_price'])) return [$price, $price_type];
        $tier_prices = (array) json_decode($config['tier_price']);
        foreach($tier_prices as $tier) {
            if ($qty >= $tier->qty) {
                $price = $tier->price;
                $price_type = $tier->price_type;
            }
        }
        return [$price, $price_type];
    }

    /**
     * @return \Itoris\DynamicProductOptions\Helper\Data
     */
    public function getItorisHelper(){
        return $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Data');
    }
}