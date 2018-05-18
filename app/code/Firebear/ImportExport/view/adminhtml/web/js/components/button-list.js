/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

define(
    [
    'Magento_Ui/js/form/components/button',
    'uiRegistry',
    'uiLayout',
    'mageUtils',
    'jquery',
    'underscore',
    'mage/translate'
    ],
    function (Element, registry, layout, utils, jQuery, _, $t) {
        'use strict';

        return Element.extend(
            {
                defaults: {
                    elementTmpl: 'Firebear_ImportExport/form/element/button-list',
                    options: [],
                    defaultOptions: null,
                    typeFile:'',
                    platform: '',
                    imports: {
                        setOptions: '${$.parentName}.platforms:value',
                        setSource: '${$.ns}.${$.ns}.source.type_file:value',
                    }
                },

                initConfig: function (config) {
                    this._super();
                    this.defaultOptions = jQuery.parseJSON(config.options);
                    this.options = '';
                    return this;
                },
                setOptions: function (value) {
                    this.platform = value;
                    var self = this;
                    var newList = [];
                    var data = this.defaultOptions;
                    _.each(
                        data,
                        function (element) {
                            if (element.type == value) {
                            	var newObject = {
                            			href:element.href + "source/" + self.typeFile,
                            			label: element.label,
                            			type:element.type
                            	}
                            
                            	newList.push(newObject);
                            }
                        }
                    );
                    this.options(newList);
                },
                setSource: function (value) {
                   this.options([]);
                   this.typeFile = value;
                   var self = this;
                    var newList = [];
                    if (this.platform != '') {
                        var data = this.defaultOptions;
                        _.each(
                            data,
                            function (element) {
                                if (element.type == self.platform) {
                                	var newObject = {
                                			href:element.href + "source/" + self.typeFile,
                                			label: element.label,
                                			type:element.type
                                	};
                                
                                    newList.push(newObject);
                                }
                               
                            }
                        );

                        this.options(newList);
                    }

                },
                initObservable: function () {
                    this._super()
                    .observe({options:[]});
                    return this;
                },
            }
        );
    }
);
