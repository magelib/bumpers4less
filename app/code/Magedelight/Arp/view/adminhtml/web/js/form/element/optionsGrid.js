define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'Magento_Ui/js/modal/modal'
], function (_, uiRegistry, select, modal) {
    'use strict';

    return select.extend({
        
        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {
            var field1 = uiRegistry.get('index = block_position_product');
            
            if (field1.visibleValue == value) {
                field1.show();
            } else {
                field1.hide();
            }
            var field2 = uiRegistry.get('index = block_position_shoppingcart');
            if (field2.visibleValue == value) {
                field2.show();
            } else {
                field2.hide();
            }
            
            var field3 = uiRegistry.get('index = block_position_category');
            if (field3.visibleValue == value) {
                field3.show();
            } else {
                field3.hide();
            }
            
            var field4 = uiRegistry.get('index = block_position_cms');
            if (field4.visibleValue == value) {
                field4.show();
            } else {
                field4.hide();
            }
            var field5 = uiRegistry.get('index = html_content');
            
            if (value == 1  || value == 2 ) {
                field5.visible(true);
            } else {
                field5.visible(false);
            }
            var field6 = uiRegistry.get('index = products_category');
            
            if (value == 3) {
                field6.visible(true);
            } else {
                field6.visible(false);
            }
            var field7 = uiRegistry.get('index = cms_page');
            
            if (value == 5) {
                field7.visible(true);
            } else {
                field7.visible(false);
            }
            return this._super();
        },
    });
});