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

namespace Itoris\DynamicProductOptions\Observers\Adminhtml;

use Magento\Framework\Event\ObserverInterface;

class SaveOptionOrOptionValue implements ObserverInterface
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
}