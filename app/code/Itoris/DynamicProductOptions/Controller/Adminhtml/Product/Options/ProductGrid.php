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

namespace Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options;

class ProductGrid extends \Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $this->_view->loadLayout('dynamicproductoptions_product_grid');
        $str = $this->_view->getLayout()->getBlock('itorisdpogrid')->getHtml();
        $str .= '
            <script type="text/javascript">
                jQuery(\'#itoris_dynamicoptions_products_grid_popup input\').each(function(index, inp){
                    inp.on(\'keypress\', function(ev){
                        if (ev.keyCode == 13) ev.preventDefault();
                    });
                });
            </script>
        ';
        $this->getResponse()->setBody($str);
    }
}