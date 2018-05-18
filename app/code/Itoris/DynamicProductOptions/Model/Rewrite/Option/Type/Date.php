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

class Date extends \Magento\Catalog\Model\Product\Option\Type\Date
{
    /** @var \Magento\Framework\ObjectManagerInterface|null  */
    protected $_objectManager = null;
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        parent::__construct($checkoutSession, $scopeConfig, $localeDate, $data);
    }

    public function validateUserValue($values) {
        if ($this->getItorisHelper()->isEnabledOnFrontend()) {
            try {
                return parent::validateUserValue($values);
            } catch (\Exception $e) {
                $this->getItorisHelper()->addOptionError($this->getOption(), $this->getProduct(), $e->getMessage());
                //Mage::throwException($e->getMessage());
            }
        } else {
            return parent::validateUserValue($values);
        }
        return $this;
    }

    /**
     * @return \Itoris\DynamicProductOptions\Helper\Data
     */
    public function getItorisHelper(){
        return $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Data');
    }
}