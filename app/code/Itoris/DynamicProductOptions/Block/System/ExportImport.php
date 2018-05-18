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
namespace Itoris\DynamicProductOptions\Block\System;

class ExportImport extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $html = $this->_getHeaderHtml($element);
		
        $html .= '<div><b>Export</b>
                 <p>You can export dynamic options for all existing products here. You can then use this file to upload dynamic product options to another site, or use it as a backup. Note, product options are associated to products by SKU. If product SKU has not been found options will not be imported.</p>
                 <p><input type="button" value="Download File" class="action-default" onclick="window.dpoFileExport()" /></p>
                 </div>';
                 
		$html .= '<div style="margin-top:20px;"><b>Import</b>
                 <p>You can import dynamic product options for multiple products here. Upload a file in valid format. Only options for existing SKUs will be imported.</p>
                 <p><input type="file" name="dynamic_product_options_import" id="dynamic_product_options_import" /><input type="button" value="Upload File" style="margin-left:20px;" class="action-default" onclick="window.dpoFileImport(this)" /></p>
                 </div>';    
                 
        $html .= '<script type="text/javascript">
                window.dpoFileImport = function(btn){
                    var file = jQuery(\'#dynamic_product_options_import\');
                    if (!file.val()) {alert(\'Please select a file\'); return;}
                    btn.disabled = true;
                    jQuery("#dynamic_product_options_import").before("<i>Uploading... Please wait.</i>");
                    jQuery(\'<form action="'.$this->getUrl('dynamicproductoptions/product/importAll').'" method="post" enctype="multipart/form-data">\').append(jQuery("#dynamic_product_options_import")).appendTo(document.body).submit();
                }
                window.dpoFileExport = function(){
                    document.location.href = \''.$this->getUrl('dynamicproductoptions/product/exportAll').'\';
                }
                </script>';
		
		$html .= $this->_getFooterHtml($element);
		
		return $html;
    }
}