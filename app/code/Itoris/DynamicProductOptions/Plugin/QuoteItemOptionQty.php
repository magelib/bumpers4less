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
 * @copyright  Copyright (c) 2017 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */
 
namespace Itoris\DynamicProductOptions\Plugin;

class QuoteItemOptionQty
{
    public function afterRepresentProduct($subject, $result)
    {
        if ($result) {
            //if options' quantites are different create a new cart item
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $requestOptionsQty = (array)$objectManager->get('Magento\Framework\App\Request\Http')->getParam('options_qty');
            $itemOptionsQty = (array)$subject->getBuyRequest()->getOptionsQty();
            foreach($requestOptionsQty as $key => $value) {
                if (!isset($itemOptionsQty[$key])) return false;
                if (is_array($value)) {
                    foreach($value as $key2 => $value2) {
                        if (!isset($itemOptionsQty[$key][$key2]) || $itemOptionsQty[$key][$key2] != $value2) return false;
                    }
                } else if ($itemOptionsQty[$key] != $value) return false;
            }
            foreach($itemOptionsQty as $key => $value) {
                if (!isset($requestOptionsQty[$key])) return false;
                if (is_array($value)) {
                    foreach($value as $key2 => $value2) {
                        if (!isset($requestOptionsQty[$key][$key2]) || $requestOptionsQty[$key][$key2] != $value2) return false;
                    }
                } else if ($requestOptionsQty[$key] != $value) return false;
            }
        }
        return $result;
    }
}