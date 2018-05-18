/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'jquery',
    'underscore',
    'mageUtils',
    'uiRegistry',
    'Firebear_ImportExport/js/form/element/additional-select',
    'uiLayout'
    ],
    function ($,_, utils, registry, Abstract, layout) {
        'use strict';

        return Abstract.extend(
            {
                defaults: {
                    code: '',
                },

                initialize    : function () {
                    this._super();
                    var elements = this.getOption(this.value());
                    if (elements != undefined) {
                        this.setCode(elements.code);
                    }
		  
                    return this;
                },
                initObservable: function () {
                    this._super();

                    this.observe('code');

                    return this;
                },
                setCode: function (value) {
                    this.code(value);
            /* Hidding extra fields when entity is CART PRICE RULE (POSTBACK) */
		    var entityValue=value;
		    setTimeout(function(){
		    $('.admin__field').each(function(){
			var dataIndex=$(this).data('index');
			if(dataIndex=='category_levels_separator') {
			  if(entityValue=='cart_price_rule_behavior') 
			    $(this).css('display','none');
			  else
			    $(this).css('display','block');
			}
			if(dataIndex=='categories_separator') {
			  if(entityValue=='cart_price_rule_behavior') 
			    $(this).css('display','none');
			  else
			    $(this).css('display','block');
			}
		    });
		    },3000);
                },

                onUpdate: function () {
                    this._super();
                    var map = registry.get(this.ns + '.' + this.ns + '.source_data_map_container.source_data_map');
                    var mapCategory = registry.get(this.ns + '.' + this.ns + '.source_data_map_container_category.source_data_categories_map');
                    map.deleteRecords();
                    map._updateCollection();
                    mapCategory.deleteRecords();
                    mapCategory._updateCollection();
                    registry.get(this.ns + '.' + this.ns + '.source.check_button').showMap(0);
                    if (this.value() == undefined) {
                       this.setCode('');
                    } else {
                        var elements = this.getOption(this.value());
                        this.setCode(elements.code);
                    }
            /* Hidding extra fields when entity CART PRICE RULE is selected*/
		    var entityValue=this.value();
		    $('.admin__field').each(function(){
			var dataIndex=$(this).data('index');
			if(dataIndex=='category_levels_separator') {
			  if(entityValue=='cart_price_rule') 
			    $(this).css('display','none');
			  else
			    $(this).css('display','block');
			}
			if(dataIndex=='categories_separator') {
			  if(entityValue=='cart_price_rule') 
			    $(this).css('display','none');
			  else
			    $(this).css('display','block');
			}
		    });
                },
            }
        );
    }
);
