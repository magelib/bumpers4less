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

namespace Itoris\DynamicProductOptions\Model\Rewrite\Product\Option;

use Magento\Catalog\Api\Data\ProductInterface;

class Repository extends \Magento\Catalog\Model\Product\Option\Repository
{

    /*public function save(\Magento\Catalog\Api\Data\ProductCustomOptionInterface $option)
    {
        if (!$this->getItorisHelper()->getSettings(true)->getEnabled()) return parent::save($option);
        return $option;
    }*/
    
    public function duplicate(
        \Magento\Catalog\Api\Data\ProductInterface $product,
        \Magento\Catalog\Api\Data\ProductInterface $duplicate
    ) {
        if (!$this->getItorisHelper()->getSettings(true)->getEnabled()) return parent::duplicate($product, $duplicate);
        return;
    }
    
    public function getProductOptions(ProductInterface $product, $requiredOnly = false)
    {
        $options = parent::getProductOptions($product, $requiredOnly);
        
        //additional check for customer groups
        $customerGroupId = $this->getItorisHelper()->getCustomerGroupId();
        $res = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
        $con = $res->getConnection('read');
        
        foreach($options as $key => $option) {
            $_optionId = (int) $con->fetchOne("select `option_id` from {$res->getTableName('itoris_dynamicproductoptions_option')} where `orig_option_id`={$option->getId()}");
            if ($_optionId) {
                $_groups = $con->fetchCol("select `group_id` from {$res->getTableName('itoris_dynamicproductoptions_option_customergroup')} where `option_id`={$_optionId}");
                if (!empty($_groups) && !in_array($customerGroupId, $_groups)) {
                    unset($options[$key]);
                    continue;
                }
            }
            $values = $option->getValues();
            if (!empty($values)) {
                foreach($values as $key => $value) {
                    $_valueId = (int) $con->fetchOne("select `value_id` from {$res->getTableName('itoris_dynamicproductoptions_option_value')} where `orig_value_id`={$value->getId()}");
                    if ($_valueId) {
                        $_groups = $con->fetchCol("select `group_id` from {$res->getTableName('itoris_dynamicproductoptions_option_value_customergroup')} where `value_id`={$_valueId}");
                         if (!empty($_groups) && !in_array($customerGroupId, $_groups)) {
                            unset($values[$key]);
                            continue;
                        }
                    }
                }
                $option->setValues($values);
            }
        }
        return $options;
    }
    
    public function getItorisHelper(){
        return \Magento\Framework\App\ObjectManager::getInstance()->get('Itoris\DynamicProductOptions\Helper\Data');
    }
    
}
