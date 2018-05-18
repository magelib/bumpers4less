/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'uiRegistry',
    'Magento_Ui/js/form/element/ui-select',
], function (uiRegistry, Select) {
    'use strict';

    return Select.extend({
        initialize: function () {
            this._super();
            var mainField = uiRegistry.get('index = block_page');
            if (mainField.initialValue === '5') {
                this.show();
            } else {
                this.hide();
            }
            //var testnew = uiRegistry.get('index = html_content');
            //testnew.hide();
            return this;
        },
    });
});
