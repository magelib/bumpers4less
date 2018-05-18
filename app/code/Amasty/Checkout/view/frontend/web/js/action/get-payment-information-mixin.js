define([
    'uiRegistry',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'mage/utils/wrapper'
], function (registry, quote, rateRegistry, wrapper) {
    'use strict';

    return function (getPaymentInformationAction) {
        return wrapper.wrap(getPaymentInformationAction, function (originalAction, deferred, messageContainer) {
            var address = quote.shippingAddress(),
                reload  = registry.get('checkoutProvider').amdiscount.isNeedToReloadShipping;
            if (reload && address) {
                rateRegistry.set(address.getKey(), null);
                rateRegistry.set(address.getCacheKey(), null);

                address.trigger_reload = new Date().getTime();
                quote.shippingAddress(address);
            }

            return originalAction(deferred, messageContainer);
        });
    };
});