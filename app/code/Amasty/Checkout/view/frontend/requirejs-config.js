/*jshint browser:true jquery:true*/
/*global alert*/
var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/model/place-order': {
                'Amasty_Checkout/js/model/place-order-mixin': true
            },
            'Magento_Checkout/js/action/select-shipping-address' : {
                'Amasty_Checkout/js/action/select-shipping-address-mixin': true
            },
            'Magento_Checkout/js/action/select-payment-method' : {
                'Amasty_Checkout/js/action/select-payment-method-mixin': true
            },
            'Magento_Checkout/js/action/get-payment-information': {
                'Amasty_Checkout/js/action/get-payment-information-mixin': true
            }
        }
    }
};
