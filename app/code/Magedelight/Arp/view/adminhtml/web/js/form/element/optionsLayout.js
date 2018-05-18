define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/select',
    'Magento_Ui/js/modal/modal'
], function (_, uiRegistry, select, modal) {
    'use strict';

    return select.extend({
        
        initialize: function () {
            this._super();
            this.onUpdate(this.initialValue);
            return this;
        },
        
        /**
         * On value change handler.
         *
         * @param {String} value
         */
        onUpdate: function (value) {
            var field6 =  uiRegistry.get('index = default_number_of_rows_group');
            if (value == 1 ) {
                field6.visible(true);
            } else {
                field6.visible(false);
            }
            return this._super();
        },
    });
});