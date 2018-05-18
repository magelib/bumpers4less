/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
 define([
    'jquery',
    'Magento_Catalog/js/price-utils',
    'underscore',
    'mage/template',
    'jquery/ui'
    ], function ($, utils, _, mageTemplate) {
        'use strict';

        var globalOptions = {
            productId: null,
            priceConfig: null,
            prices: {},
            
            priceTemplate: '<span class="price"><%- data.formatted %></span>'
        };

        $.widget('md.custompriceBox',  {
            options: globalOptions,
            cache: {},
            pricesArray: {},

        /**
         * Widget initialisation.
         * Every time when option changed prices also can be changed. So
         * changed options.prices -> changed cached prices -> recalculation -> redraw price box
         */
         _init: function initPriceBox() {
            var box = this.element;
            box.trigger('updatePrice');
            this.cache.displayPrices = utils.deepClone(this.options.prices);
            this.cache[this.options.productId] = utils.deepClone(this.options.prices);
        },

        /**
         * Widget creating.
         */
         _create: function createPriceBox() {
            var box = this.element;

            this._setDefaultsFromPriceConfig();
            this._setDefaultsFromDataSet();

            box.on('reloadPrice', this.reloadPrice.bind(this));
            box.on('updatePrice', this.onUpdatePrice.bind(this));
        },

        /**
         * Call on event updatePrice. Proxy to updatePrice method.
         * @param {Event} event
         * @param {Object} prices
         */
         onUpdatePrice: function onUpdatePrice(event, prices) {
            return this.updatePrice(prices);
        },

        /**
         * Updates price via new (or additional values).
         * It expects object like this:
         * -----
         *   "option-hash":
         *      "price-code":
         *         "amount": 999.99999,
         *         ...
         * -----
         * Empty option-hash object or empty price-code object treats as zero amount.
         * @param {Object} newPrices
         */
         updatePrice: function updatePrice(newPrices) {
var priceArray = {};
            if(newPrices){
                if(Object.keys(newPrices).length > 1){

                    for (var key in newPrices) {
                      this.pricesArray[this.options.productId+'_'+key] = newPrices;
                    }

                }else{
                    this.pricesArray[this.options.productId+'_'+Object.keys(newPrices)[0]] = newPrices;
                }
            }


            if(this.cache.displayPrices){

                var prices = this.cache[this.options.productId],
                additionalPrice = {},
                addPrice = {},
                productId = this.options.productId,
                pricesCode = [];
            }else{
                var prices = this.cache.displayPrices,
                additionalPrice = {},
                addPrice = {},
                productId = this.options.productId,
                pricesCode = [];
                
            }

            this.cache.additionalPriceObject = this.cache.additionalPriceObject || {};

            if (newPrices) {
                $.extend(this.cache.additionalPriceObject, newPrices);
            }

            if (!_.isEmpty(additionalPrice[this.options.productId])) {
                pricesCode = _.keys(additionalPrice[this.options.productId]);
            } else if (!_.isEmpty(prices)) {
                pricesCode = _.keys(prices);
            }

            _.each(this.cache.additionalPriceObject, function (additional,val) {

                if(this.pricesArray[this.options.productId+'_'+val]){



                    if (additional && !_.isEmpty(additional)) {
                        pricesCode = _.keys(additional);
                    }
                    _.each(pricesCode, function (priceCode) {
                        var priceValue = additional[priceCode] || {};
                        priceValue.amount = +priceValue.amount || 0;
                        priceValue.adjustments = priceValue.adjustments || {};

                        if(additionalPrice){

                            addPrice[productId] = additionalPrice;
                        }else{
                            addPrice = additionalPrice;
                        }

                        if(addPrice){
                            addPrice[productId][priceCode] = addPrice[productId][priceCode] || {
                                'amount': 0,
                                'adjustments': {}
                            };
                            addPrice[productId][priceCode].amount =  0 + (addPrice[productId][priceCode].amount || 0)
                            + priceValue.amount;
                            _.each(priceValue.adjustments, function (adValue, adCode) {
                                addPrice[productId][priceCode].adjustments[adCode] = 0
                                + (addPrice[productId][priceCode].adjustments[adCode] || 0) + adValue;
                            });
                        }else{
                           additionalPrice[priceCode] = additionalPrice[priceCode] || {
                            'amount': 0,
                            'adjustments': {}
                        };
                        additionalPrice[priceCode].amount =  0 + (additionalPrice[priceCode].amount || 0)
                        + priceValue.amount;
                        _.each(priceValue.adjustments, function (adValue, adCode) {
                            additionalPrice[priceCode].adjustments[adCode] = 0
                            + (additionalPrice[priceCode].adjustments[adCode] || 0) + adValue;
                        });
                    }
                });
}
}.bind(this));

if(addPrice){
    if (_.isEmpty(addPrice[this.options.productId])) {
        this.cache.displayPrices = utils.deepClone(this.options.prices);
                //this.cache.displayPrices[this.options.productId] = utils.deepClone(this.options.prices);
            } else {
                _.each(addPrice[this.options.productId], function (option, priceCode) {
                    var origin = this.options.prices[priceCode] || {},
                    final = prices[priceCode] || {};
                    option.amount = option.amount || 0;
                    origin.amount = origin.amount || 0;
                    origin.adjustments = origin.adjustments || {};
                    final.adjustments = final.adjustments || {};

                    final.amount = 0 + origin.amount + option.amount;
                    _.each(option.adjustments, function (pa, paCode) {
                        final.adjustments[paCode] = 0 + (origin.adjustments[paCode] || 0) + pa;
                    });
                }, this);
            }
        }else{
            if (_.isEmpty(additionalPrice[this.options.productId])) {
                this.cache.displayPrices = utils.deepClone(this.options.prices);
                //this.cache.displayPrices[this.options.productId] = utils.deepClone(this.options.prices);
            } else {
                _.each(additionalPrice[this.options.productId], function (option, priceCode) {
                    var origin = this.options.prices[priceCode] || {},
                    final = prices[priceCode] || {};
                    option.amount = option.amount || 0;
                    origin.amount = origin.amount || 0;
                    origin.adjustments = origin.adjustments || {};
                    final.adjustments = final.adjustments || {};

                    final.amount = 0 + origin.amount + option.amount;
                    _.each(option.adjustments, function (pa, paCode) {
                        final.adjustments[paCode] = 0 + (origin.adjustments[paCode] || 0) + pa;
                    });
                }, this);
            }
        }

        this.element.trigger('reloadPrice');
    },

        /**
         * Render price unit block.
         */
         reloadPrice: function reDrawPrices() {
            var priceFormat = (this.options.priceConfig && this.options.priceConfig.priceFormat) || {},
            priceTemplate = mageTemplate(this.options.priceTemplate);

            _.each(this.cache[this.options.productId], function (price, priceCode) {
                price.final = _.reduce(price.adjustments, function(memo, amount) {
                    return memo + amount;
                }, price.amount);

                price.formatted = utils.formatPrice(price.final, priceFormat);

                $('[data-price-type="' + priceCode + '"]', this.element).html(priceTemplate({data: price}));
            }, this);
        },

        /**
         * Overwrites initial (default) prices object.
         * @param {Object} prices
         */
         setDefault: function setDefaultPrices(prices) {
            this.cache[this.options.productId] = utils.deepClone(prices);
            this.options.prices = utils.deepClone(prices);
        },

        /**
         * Custom behavior on getting options:
         * now widget able to deep merge of accepted configuration.
         * @param  {Object} options
         * @return {mage.priceBox}
         */
         _setOptions: function setOptions(options) {
            $.extend(true, this.options, options);

            if ('disabled' in options) {
                this._setOption('disabled', options.disabled);
            }

            return this;
        },

        /**
         * setDefaultsFromDataSet
         */
         _setDefaultsFromDataSet: function _setDefaultsFromDataSet() {
            var box = this.element,
            priceHolders = $('[data-price-type]', box),
            prices = this.options.prices;
            this.options.productId = box.data('productId');

            if (_.isEmpty(prices)) {
                priceHolders.each(function (index, element) {
                    var type = $(element).data('priceType'),
                    amount = parseFloat($(element).data('priceAmount'));

                    if (type && amount) {
                        prices[type] = {
                            amount: amount
                        };
                    }
                });
            }
        },

         setDefaultsFromDataSet1: function setDefaultsFromDataSet1(prices) {
            var box = this.element,
            priceHolders = $('[data-price-type]', box),
            prices = this.options.prices;
            this.options.productId = box.data('productId');

            if (_.isEmpty(prices)) {
                priceHolders.each(function (index, element) {
                    var type = $(element).data('priceType'),
                    amount = parseFloat($(element).data('priceAmount'));

                    if (type && amount) {
                        prices[type] = {
                            amount: amount
                        };
                    }
                });
            }
        },

        /**
         * setDefaultsFromPriceConfig
         */
         _setDefaultsFromPriceConfig: function _setDefaultsFromPriceConfig() {
            var config = this.options.priceConfig;

            if (config) {
                if (+config.productId !== +this.options.productId) {
                    return;
                }
                this.options.prices = config.prices;
            }
        }
    });

return $.md.custompriceBox;
});
