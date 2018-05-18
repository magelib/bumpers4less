/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'Magento_Ui/js/form/element/checkbox-set'
    ],
    function (Element) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    valuesForOptions: [],
                    imports: {
                        toggleVisibility: '${$.ns}.${$.ns}.settings.entity:value'
                    },
                    isShown: false,
                    inverseVisibility: false,
                    visible: false,
                    listens: {
                        'value': 'onValueChange'
                    },

                },
                initialize: function () {
                    this._super();
                    return this;
                },
                toggleVisibility: function (selected) {
                    this.isShown = selected in this.valuesForOptions;
                    this.visible(this.inverseVisibility ? !this.isShown : this.isShown);
                },
                initConfig: function (config) {
                    this._super();
                },
                onValueChange: function (value) {
                    if (_.size(value) > 1) {
                        var lastValue = _.last(value);
                        var obj = this.seacrhEl(lastValue);
                        if (_.indexOf(value, obj['parent']) == -1
                            && !this.searchParent(lastValue, value)
                        ) {
                            value.pop();
                            this.value(value);
                        }
                    }
                },
                seacrhEl: function (val) {
                    var element;
                    _.each(
                        this.options,
                        function (obj) {
                            if (obj.value == val) {
                                element = obj;
                            }
                        }
                    );

                    return element;
                },
                searchParent: function (val, value) {
                    var parents = [];
                    var self = this;
                    _.each(
                        value,
                        function (item) {
                            if (item != value) {
                                var obj = self.seacrhEl(item);
                                parents.push(obj['parent']);
                            }
                        }
                    );
                    return (_.indexOf(parents, val) == -1) ? false : true;
                }
            }
        );
    }
);