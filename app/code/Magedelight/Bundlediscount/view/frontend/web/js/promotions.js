/**
* Magedelight
* Copyright (C) 2014 Magedelight <info@magedelight.com>
*
* NOTICE OF LICENSE
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
*
* @category Magedelight
* @package Magedelight_Bundlediscount
* @copyright Copyright (c) 2014 Mage Delight (http://www.magedelight.com/)
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
* @author Magedelight <info@magedelight.com>
*/
function configureOptions(bundle_id,ajaxUrl)
{
    
    if(bundle_id !== 'undefined'){
        $$('div[id^="configure_bundle_"]').each(function(configBundle)
        {
            $(configBundle).update('');
            $(configBundle).removeAttribute("style");
        });
        jQuery.ajax({

           url: ajaxUrl,
           type: 'post',
           data:{bundle_id:bundle_id},
           showLoader: true,
           success:function(transport)
           {
            var response = transport.html;
            if($('configure_bundle_'+bundle_id) !== 'undefined')
            {

              if($('configure_bundle_content'+bundle_id)){
                 $('configure_bundle_content'+bundle_id).update('');
             }
                   //$('configure_bundle_'+bundle_id).update(response);

                   require([
                    'jquery',
                    'jquery/ui',
                    'Magento_Ui/js/modal/modal'
                    ], function($){
                      $('#configure_bundle_content'+bundle_id)
                      .modal({
                        title: 'Configure Bundle Promotions',
                        autoOpen: true,
                        modalClass: 'items-wrapper',
                        innerScroll :true,
                        responsive :true,
                        buttons: false,
                        clickableOverlay : true,
                        height :'auto',
                        width :'auto',

                    });
                  });

                   var block =  jQuery('#configure_bundle_'+bundle_id)
                   block.html(response);
                   block.trigger('contentUpdated');


                   require([
                    'jquery',
                    'mage/mage',
                    'Magento_Catalog/product/view/validation',
                    'Magento_Catalog/js/catalog-add-to-cart'

                    ], function($){
                       'use strict';

                       $('#bundle_product_addtocart_form').mage('validation', {
                        radioCheckboxClosest: '.nested',
                        submitHandler: function (form) {
                           //form.submit();
                           jQuery.ajax({
                                url        : form.action,
                                type       : 'POST',
                                dataType   : 'json',
                                data: form.serialize(),
                                showLoader     : true
                            }).done(function(data) {
                                window.location.href = data.url;
                                //return true;
                            });
                           
                       }
                   });


                   });
               }

           },
           onComplete:function(transport){

           }
       });

}
}

function addToCart(url)
{
    //window.location.href = url;
    jQuery.ajax({
            url        : url,
            type       : 'POST',
            dataType   : 'json',
            showLoader     : true
        }).done(function(data) {
            window.location.href = data.url;
            //return true;
        });
}

