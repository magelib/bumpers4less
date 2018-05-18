define(
    [
        'Magento_Checkout/js/action/set-shipping-information',
        'Magento_Checkout/js/view/shipping',
        'Magento_Checkout/js/model/quote'
    ],
    function (
        setShippingInformationAction,
        Shipping,
        quote
    ) {
        'use strict';

        var instance = null;

        // Fix js error in Magento 2.2
        function fixAddress(address) {
            if (!address) {
                return;
            }

            if (Array.isArray(address.street) && address.street.length == 0) {
                address.street = ['', ''];
            }
        }

        return Shipping.extend({
            setShippingInformation: function () {
                fixAddress(quote.shippingAddress());
                fixAddress(quote.billingAddress());

                setShippingInformationAction().done(
                    function () {
                        //stepNavigator.next();
                    }
                );
            },
            initialize: function () {
                this._super();
                instance = this;
            },

            selectShippingMethod: function (shippingMethod) {
                this._super();

                instance.setShippingInformation();

                return true;
            }
        });
    }
);
