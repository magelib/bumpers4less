define(
    [
        'Amasty_Checkout/js/model/resource-url-manager',
        'Magento_Checkout/js/model/totals',
        'Magento_Checkout/js/model/quote',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'uiRegistry',
        'Magento_Checkout/js/model/shipping-service',
        'Magento_Checkout/js/model/shipping-rate-registry',
        'Magento_Checkout/js/model/payment-service',
        'Magento_Checkout/js/model/payment/method-converter'
    ],
    function (
        resourceUrlManager,
        totals,
        quote,
        storage,
        errorProcessor,
        registry,
        shippingService,
        rateRegistry,
        paymentService,
        methodConverter
    ) {
        "use strict";
        return function (itemId, formData) {
            if (totals.isLoading())
                return;

            totals.isLoading(true);
            shippingService.isLoading(true);
            var serviceUrl = resourceUrlManager.getUrlForUpdateItem(quote);

            storage.post(
                serviceUrl, JSON.stringify({
                    itemId: itemId,
                    formData: formData,
                    address: quote.shippingAddress()
                }), false
            ).done(
                function (result) {
                    if (!result) {
                        window.location.reload();
                    }
                    registry.get('checkout.sidebar.summary.cart_items.details.thumbnail').imageData
                        = JSON.parse(result.image_data);

                    var options = JSON.parse(result.options_data);

                    result.totals.items.forEach(function (item) {
                        item.amcheckout = options[item.item_id];
                    });

                    shippingService.setShippingRates(result.shipping);
                    rateRegistry.set(quote.shippingAddress().getKey(), result.shipping);
                    quote.setTotals(result.totals);

                    paymentService.setPaymentMethods(methodConverter(result.payment));
                }
            ).fail(
                function (response) {
                    errorProcessor.process(response);
                }
            ).always(
                function () {
                    shippingService.isLoading(false);
                    totals.isLoading(false);
                }
            );
        }
    }
);
