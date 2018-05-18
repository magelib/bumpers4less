/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiRegistry',
    'Magento_Ui/js/form/element/ui-select',
], function (jQuery, uiRegistry, Select) {
    'use strict';
    return Select.extend({
        initialize: function () {
            this._super();
            var mainField = uiRegistry.get('index = block_page');
            if (mainField.initialValue === '3') {
                this.show();
            } else {
                this.hide();
            }
            
            return this;
        },
    });
});
