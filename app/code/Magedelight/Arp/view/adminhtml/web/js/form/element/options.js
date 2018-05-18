define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'Magento_Ui/js/modal/modal'
], function (jQuery,_, uiRegistry, select, modal) {
    'use strict';

    return select.extend({
        
        initialize: function () {
            this._super();
            this.onMainLoad(this.index);
            return this;
        },
        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onMainLoad: function (value) {
            var mainField = uiRegistry.get('index = block_page');
            var field1 = uiRegistry.get('index = block_position_product');
            
            if (field1.visibleValue == mainField.initialValue) {
                field1.show();
            } else {
                field1.hide();
            }
            var field2 = uiRegistry.get('index = block_position_shoppingcart');
            if (field2.visibleValue == mainField.initialValue) {
                field2.show();
            } else {
                field2.hide();
            }
            
            var field3 = uiRegistry.get('index = block_position_category');
            if (field3.visibleValue == mainField.initialValue) {
                field3.show();
            } else {
                field3.hide();
            }
            
            var field4 = uiRegistry.get('index = block_position_cms');
            if (field4.visibleValue == mainField.initialValue) {
                field4.show();
            } else {
                field4.hide();
            }
            var field5 = uiRegistry.get('index = html_content');
            
            if (mainField.initialValue == 3  || mainField.initialValue == 5 ) {
                field5.visible(false);
            } else {
                field5.visible(true);
            }
            /*var mainFieldtwo = uiRegistry.get('index = block_layout');
            console.log(mainFieldtwo);
            var field6 =  uiRegistry.get('index = default_number_of_rows_group');
            if (mainFieldtwo.initialValue == 1 ) {
                field6.visible(true);
            } else {
                field6.visible(false);
            } */
            return this._super();
        },
    });
});