/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
        'jquery',
        'underscore',
        'Magento_Ui/js/form/element/select',
        'Firebear_ImportExport/js/form/element/general',
        'uiRegistry',
        'moment',
        'mageUtils',
        'Magento_Ui/js/lib/validation/validator'
    ],
    function ($, _, Acstract, general, reg, moment, utils, validator) {
        'use strict';

        String.prototype.firstLetterCaps = function () {
            return this.charAt(0).toUpperCase() + this.slice(1);
        };

        return Acstract.extend(general).extend(
            {
                defaults: {
                    valueUpdate: 'afterkeydown',
                    sourceExt: null,
                    sourceOptions: null,
                    typeText: false,
                    typeSelect: false,
                    typeDate: false,
                    typeNot: false,
                    typeInt: false,
                    types: ['text', 'select', 'date', 'not', 'int'],
                    genType: 'text',
                    timeOffset: 0,
                    checked: false,
                    inputDateFormat: 'y-MM-dd',
                    outputDateFormat: 'MM/dd/y',
                    pickerDateTimeFormat: '',
                    pickerDefaultDateFormat: 'MM/dd/y', // ICU Date Format
                    pickerDefaultTimeFormat: 'h:mm a',
                    elementName: '',
                    number: '',
                    validationParams: {
                        dateFormat: '${ $.outputDateFormat }'
                    },
                    shiftedValue: '',
                    secondShiftedValue: '',
                    fromValue: '',
                    toValue: '',
                    textValue: '',
                    selectValue: '',
                    entity: '${$.parentName}.source_filter_entity',
                    imports: {
                        changeSource: '${$.parentName}.source_filter_field:value'
                    },
                    listens: {
                        'textValue': 'onTextValueChange',
                        'shiftedValue': 'onShiftedValueChange',
                        'secondShiftedValue': 'onSecondShiftedValueChange',
                        'fromValue': 'onFromValueChange',
                        'toValue': 'onToValueChange',
                        'selectValue': 'onSelectValueChange'
                    }
                },
                initialize: function () {
                    this._super();
                    return this;
                },
                initConfig: function (config) {
                    this._super();
                    this.sourceOptions = $.parseJSON(this.sourceOptions);
                    var scope = this.dataScope;
                    var name = scope.split('.').slice(1);

                    this.elementName = name[0];
                    this.number = _.last(name);
                    return this;
                },
                initObservable: function () {
                    var type = '';
                    var count = 0;
                    _.each(this.types, function (index) {
                        if (count > 0) {
                            type += " ";
                        }
                        type += "type" + index.firstLetterCaps();
                        count++;
                    });
                    this._super()
                        .observe(type)
                        .observe(['textValue'])
                        .observe(['shiftedValue'])
                        .observe(['secondShiftedValue'])
                        .observe(['fromValue'])
                        .observe(['toValue'])
                        .observe(['selectValue']);

                    return this;
                },
                changeTypes: function (el) {
                    var self = this;
                    _.each(this.types, function (index) {
                        var bool = false;
                        if (index == el) {
                            self.genType = index;
                            bool = true;
                        }
                        var type = "type" + index.firstLetterCaps();
                        self[type](bool);
                    })
                },
                changeSource: function (value) {

                    var oldValue = this.value();
                    var self = this;
                    var finded = 0;

                    var entity = reg.get(this.entity);
                    var types = ['catalog_category','catalog_product', 'advanced_pricing', 'customer', 'customer_address','catalog_price_rule'];

                    var type = entity.value();
                    if (_.indexOf(types, entity.value()) != -1) {
                        type = 'attr';
                    }
                    if (type == 'order') {
                        type = 'orders';
                    }
                    var data = JSON.parse(localStorage.getItem('list_filtres'));
                    var exists = 0;
                    if (data !== null && typeof data === 'object') {
                        if (value in data) {
                            exists = 1;
                            var array = data[value];
                            if (array.field == value) {
                                finded = 1;
                                self.changeTypes(array.type);
                                if (array.type == 'select') {
                                    self.setOptions(array.select);
                                } else {
                                    self.setOptions([]);
                                }
                            }
                        }
                        if (!finded) {
                            self.changeTypes('not');
                            self.setOptions([]);
                        }
                        if (oldValue) {
                            self.getToType(oldValue);
                        }
          
                        self.value(oldValue);
                    }
    
                    if (exists == 0 && typeof value != 'undefined') {
                        var parent = reg.get(this.ns +'.' + this.ns + '.source_data_filter_container.source_filter_map');
                        parent.showSpinner(true);

                        $.ajax({
                            type: "POST",
                            url: this.ajaxUrl,
                            data: {entity: value, type: type},
                            success: function (array) {
                                var newData = JSON.parse(localStorage.getItem('list_filtres'));
                                if (newData === null) {
                                    newData = {};
                                }
                                newData[value] = array;
                                localStorage.setItem('list_filtres', JSON.stringify(newData));
                                if (array.field == value) {
                                    finded = 1;
                                    self.changeTypes(array.type);
                                    if (array.type == 'select') {
                                        self.caption('Select');
                                
                                        self.setOptions(array.select);
                                    } else {
                                        self.setOptions([]);
                                    }
                                }
                                if (!finded) {
                                    self.changeTypes('not');
                                    self.setOptions([]);
                                }
                                if (oldValue) {
                                    self.getToType(oldValue);
                                }
                  
                                self.value(oldValue);
                                  parent.showSpinner(false);
                            }
                        });
                    
                    } 
                },
                setInitialValue: function () {
                    this.initialValue = this.getInitialValue();
                    this.on('value', this.onUpdate.bind(this));
                    this.isUseDefault(this.disabled());

                    return this;
                },
                getToType: function (value) {
                    switch (this.genType) {
                        case 'select':
                            this.selectValue(value);
                            break;
                        case 'date':
                            var array = value.split(":");
                            this.shiftedValue(array[0]);
                            this.secondShiftedValue(array[1]);
                            break;
                        case 'int':
                            var array = value.split(":");
                            this.fromValue(array[0]);
                            this.toValue(array[1]);
                            break;
                        case 'text':
                        default:
                            this.textValue(value);
                    }
                },
                onTextValueChange: function (value) {
                    this.value(value);
                },
                onSelectValueChange: function (value) {
                    this.value(value);
                },
                /**
                 * Prepares and sets date/time value that will be sent
                 * to the server.
                 *
                 * @param {String} shiftedValue
                 */
                onShiftedValueChange: function (shiftedValue) {
                    var value;

                    if (shiftedValue) {
                        value = moment(shiftedValue, this.pickerDateTimeFormat);
                        value = value.format(this.outputDateFormat);
                    } else {
                        value = '';
                    }

                    this.value(value + ':' + this.secondShiftedValue());
                },
                onSecondShiftedValueChange: function (shiftedValue) {
                    var value;

                    if (shiftedValue) {
                        value = moment(shiftedValue, this.pickerDateTimeFormat);
                        value = value.format(this.outputDateFormat);
                    } else {
                        value = '';
                    }

                    this.value(self.shiftedValue() + ':' + value);
                },
                onFromValueChange: function (value) {
                    var self = this;
                    if (!this.validateNumber(value).passed) {
                        value = value.slice(0, -1);
                        this.fromValue(value);
                    }
                    var text = {
                        from: value,
                        to: self.toValue()
                    };

                    this.value(value + ':' + this.toValue());
                },
                onToValueChange: function (value) {
                    var self = this;
                    if (!this.validateNumber(value).passed) {
                        value = value.slice(0, -1);
                        this.fromValue(value);
                    }
                    this.value(self.fromValue() + ':' + value);

                },

                /**
                 * Prepares and converts all date/time formats to be compatible
                 * with moment.js library.
                 */
                prepareDateTimeFormats: function () {
                    this.pickerDateTimeFormat = this.options.dateFormat;

                    if (this.options.showsTime) {
                        this.pickerDateTimeFormat += ' ' + this.options.timeFormat;
                    }

                    this.pickerDateTimeFormat = utils.normalizeDate(this.pickerDateTimeFormat);

                    if (this.dateFormat) {
                        this.inputDateFormat = this.dateFormat;
                    }

                    this.inputDateFormat = utils.normalizeDate(this.inputDateFormat);
                    this.outputDateFormat = utils.normalizeDate(this.outputDateFormat);

                    this.validationParams.dateFormat = this.outputDateFormat;
                },
                validateNumber: function (value) {
                    return validator('validate-number', value);
                },
            }
        )
    }
);
