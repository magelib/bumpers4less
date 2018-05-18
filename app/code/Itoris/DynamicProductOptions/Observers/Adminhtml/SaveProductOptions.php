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

class SaveProductOptions implements ObserverInterface
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

        if (!$this->isEnabledFlag) return null;
        
        $optionsConfig = $this->_request->getParam('itoris_dynamicproductoptions');
        $productId = $observer->getProduct()->getId();

        if (!$productId) return;
        
        //additional check for configurable attributes, we must skip such products
        $product = $this->_request->getParam('product');        
        $associated_product_ids = (array)$this->_request->getParam('associated_product_ids');
        if (isset($product['associated_product_ids_serialized'])) {
            $associated_product_ids_serialized = trim($product['associated_product_ids_serialized']);
            if (!empty($associated_product_ids_serialized)) {
                $associated_product_ids = json_decode($associated_product_ids_serialized);
            }
        }
        if (in_array($productId, $associated_product_ids)) return;
        
        if (is_array($optionsConfig)) {
            $storeId = $this->_request->getParam('store', 0);
            /** @var \Itoris\DynamicProductOptions\Model\Options $options */
            $options = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Options')
                ->setStoreId($storeId)
                ->load($productId)
                ->addData($optionsConfig);

            $isUseGlobal = !!$this->_request->getPostValue('idpo_use_global');
            if ((int) $storeId == 0) $isUseGlobal = false;
            
            if (!$options->getId() && !$isUseGlobal) $options->setProductId($productId)->setStoreId($storeId);
            
            if ($isUseGlobal) $options->delete(); else $options->save();
        }
        
        //fixing Magento bug of not supporting options on the store view level, recreating all options from scratch
        $this->_objectManager->get('Itoris\DynamicProductOptions\Model\Rewrite\Option')->duplicate($productId, $productId);
        
    }
}