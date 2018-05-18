define([
    'jquery',
    'uiComponent',
    'uiRegistry',
    'mage/translate'
], function ($, Component, registry, $t) {
    return Component.extend({
        initialize: function () {
            var hasFinderParams = /find=/.test(window.location.href);
            var hasFinderBlocks = $('input[type=hidden][name=finder_id]').length > 0;

            if (hasFinderParams && !hasFinderBlocks) {
                this.addErrorMessage();
            }
        },
        addErrorMessage: function () {
            registry.get("messages", function (component) {
                if (!Array.isArray(component.cookieMessages)) {
                    component.cookieMessages = [];
                }
                component.cookieMessages.push({
                    type: 'error',
                    text: $t('Finder block should be present on the page for filtering to work. Please add finder block on this page. Check <a href="https://amasty.com/docs/doku.php?id=magento_2:product_parts_finder#how_to_add_finder_block_to_a_category">User Guide</a> for instructions.</a>')
                });
            })
        }
    });
});
