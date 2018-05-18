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
 
namespace Itoris\DynamicProductOptions\Block\Adminhtml\Product\Composite\Fieldset;

class Options extends \Magento\Catalog\Block\Adminhtml\Product\Composite\Fieldset\Options
{
    public function getDpoBlock(){
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $block = $objectManager->create('\Itoris\DynamicProductOptions\Block\Options\Config');
        return $block;
    }
    
    public function setTemplate($template) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        if ($objectManager->create('Itoris\DynamicProductOptions\Helper\Data')->isEnabledOnFrontend()) {
            $template = 'Itoris_DynamicProductOptions::catalog/product/composite/fieldset/options.phtml';
        }
        return parent::setTemplate($template);
    }
}
