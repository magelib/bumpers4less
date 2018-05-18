/**
 * Magedelight
 * Copyright (C) 2014 Magedelight <info@magedelight.com>
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @category Magedelight
 * @package Magedelight_Bundlediscount
 * @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 */
 if (typeof Productbundle == 'undefined') {
    Productbundle = {};
}
Productbundle.Option = Class.create();
Productbundle.Option.prototype = {
    initialize: function (template, fieldId, topContainer) {
        this.templateText = template;
        this.idLabel = fieldId;
        this.top = $(topContainer);
        this.topContainer = topContainer;
        this.templateSyntax = /(^|.|\r|\n)({{(\w+)}})/;
        this.itemsCount = 0;

    },
    add: function (data) {
        if (!data) {
            data = {qty: 1};
            this.top = $(this.topContainer);
        }
        data.index = this.itemsCount++;
        this.template = new Template(this.templateText, this.templateSyntax);
        Element.insert(this.top, {'after': this.template.evaluate(data)});
        this.top = $(this.idLabel + 'content__' + data.index);

        if (data)
        {
            var priceElement = this.idLabel + '_' + data.index + '_discount_price';
            Event.observe(this.idLabel + '_' + data.index + '_discount_type', 'change', function (event) {
                var typeElement = Event.findElement(event, 'select');

                if (typeElement.value == '1') {
                    $(priceElement).addClassName('validate-percents');
                } else {
                    if ($(priceElement).hasClassName('validate-percents')) {
                        $(priceElement).removeClassName('validate-percents');
                    }
                }
            });
            $(this.idLabel + '_' + data.index + '_status').select("option").each(function (node) {
                if (data.status == $(node).value)
                {
                    $(node).selected = true;
                }
            });
            $(this.idLabel + '_' + data.index + '_discount_type').select("option").each(function (node) {
                if (data.discount_type == $(node).value)
                {
                    $(node).selected = true;
                }
            });
            if (data.discount_type == '1') {
                $(this.idLabel + '_' + data.index + '_discount_price').addClassName('validate-percents');
            } else {
                if ($(this.idLabel + '_' + data.index + '_discount_price').hasClassName('validate-percents')) {
                    $(this.idLabel + '_' + data.index + '_discount_price').removeClassName('validate-percents');
                }
            }
            $(this.idLabel + '_' + data.index + '_exclude_base_product').select("option").each(function (node) {
                if (data.exclude_base_product == $(node).value)
                {
                    $(node).selected = true;
                }
            });
            if (data.store_ids) {
                var storeid = data.store_ids.split(",");
                $(this.idLabel + '_' + data.index + '_store_ids').select("option").each(function (node) {

                    if (storeid.indexOf($(node).value) != -1)
                    {
                        $(node).selected = true;
                    }
                });
            }

            if (data.customer_groups) {
                var customergroups = data.customer_groups.split(",");
                $(this.idLabel + '_' + data.index + '_customer_groups').select("option").each(function (node) {

                    if (customergroups.indexOf($(node).value) != -1)
                    {
                        $(node).selected = true;
                    }
                });
            }
        }
        observeMultiSelect();
        return data.index;
    },
    remove: function (event) {
        var element = $(Event.findElement(event, 'div')).parentNode.parentElement.parentElement.parentElement;
        if (element) {
            Element.select(element, '.delete').each(function (elem) {
                elem.value = '1'
            });
            Element.select(element, ['input', 'select']).each(function (elem) {
                elem.hide();
                elem.className = '';
            });
            element.up().hide();
            Element.hide(element);
        }
    }
}

Productbundle.Selection = Class.create();
Productbundle.Selection.prototype = {
    initialize: function (productbundleTemplateBox, productbundleTemplateRow, fieldId, searchUrl, optionObj) {
        this.idLabel = fieldId;
        this.templateSyntax = /(^|.|\r|\n)({{(\w+)}})/;
        this.templateBox = '<div class="grid tier form-list" id="' + this.idLabel + '_box_{{parentIndex}}">' + productbundleTemplateBox + '</div>';
        this.templateRow = '<tr class="selection" id="' + this.idLabel + '_row_{{index}}">' + productbundleTemplateRow + '</tr>';
        this.itemsCount = 0;
        this.row = null;
        this.gridSelection = new Hash();
        this.selectionSearchUrl = searchUrl;
        this.optionObject = optionObj;
    },
    showSearch: function (event) {
        var element = Event.findElement(event, 'div');
        var parts = element.id.split('_');

        var products = new Array();
        var inputs = $A($$('#' + element.id + ' tr.selection input.product'));
        for (i = 0; i < inputs.length; i++) {
            products.push(inputs[i].value);
        }
        this.gridSelection.set(parts[2], $H({}));
        new Ajax.Updater(this.optionObject.idLabel + '_search_' + parts[2], this.selectionSearchUrl, {
            method: 'post',
            parameters: {'index': parts[2], 'products[]': products, 'form_key': FORM_KEY},
            evalScripts: true
        });
        if (Event.element(event).tagName.toLowerCase() != 'button') {
            var button = Event.element(event).up('button');
        } else {
            var button = Event.element(event);
        }
        button.hide();
    },
    addRow: function (parentIndex, data) {
        var box = null;
        if (!(box = $(this.idLabel + '_box_' + parentIndex))) {
            this.addBox(parentIndex);
            box = $(this.idLabel + '_box_' + parentIndex);
        } else {
            box.show();
        }
        if (!data) {
            var data = {};
        }
        data.index = this.itemsCount++;
        data.parentIndex = parentIndex;
        this.template = new Template(this.templateRow, this.templateSyntax);
        var tbody = $$('#' + this.idLabel + '_box_' + parentIndex + ' tbody');
        Element.insert(tbody[0], {'bottom': this.template.evaluate(data)});
    },
    addBox: function (parentIndex) {
        var div = $(this.optionObject.idLabel + '_' + parentIndex);
        this.template = new Template(this.templateBox, this.templateSyntax);
        var data = {'parentIndex': parentIndex};
        Element.insert(div, {'bottom': this.template.evaluate(data)});
    },
    remove: function (event) {
        var element = Event.findElement(event, 'tr');
        var container = Event.findElement(event, 'div');

        if (element) {
            Element.select(element, '.delete').each(function (elem) {
                elem.value = '1'
            });
            Element.select(element, ['input', 'select']).each(function (elem) {
                elem.hide()
            });
            Element.removeClassName(element, 'selection');
            Element.hide(element);

            if (container) {
                if ($$('#' + container.id + ' tr.selection')) {
                    if (!$$('#' + container.id + ' tr.selection').length) {
                        container.hide();
                    }
                }
            }
        }
    },
    productGridAddSelected: function (event) {
        var rthis = this;
        var element = Event.findElement(event, 'button');
        var parts = element.id.split('_');

        $(this.optionObject.idLabel + '_search_' + parts[2]).innerHTML = '';
        $(this.optionObject.idLabel + '_' + parts[2] + '_add_button').show();

        this.gridSelection.get(parts[2]).each(
            function (pair) {
                var qty = pair.value.get('qty');
                var data = {
                    'name': pair.value.get('name'),
                    'qty': (qty == '' ? 1 : qty),
                    'sku': pair.value.get('sku'),
                    'position': 0,
                    'product_id': pair.key
                };
                rthis.addRow(parts[2], data);
            }
            );
    },
    productGridRowInit: function (grid, row) {
        var checkbox = $(row).getElementsByClassName('checkbox')[0];
        var inputs = $(row).getElementsByClassName('input-text');
        for (var i = 0; i < inputs.length; i++) {
            inputs[i].checkbox = checkbox;
            Event.observe(inputs[i], 'keyup', this.productGridRowInputChange.bind(this));
            Event.observe(inputs[i], 'change', this.productGridRowInputChange.bind(this));
        }
    },
    productGridCheckboxCheck: function (grid, element, checked) {
        var id = element.up('table').id.split('_')[4];
        if (element.value > 0) {
            if (element.checked) {
                var tr = element.parentNode.parentNode.parentNode;
                if (!this.gridSelection.get(id)) {
                    this.gridSelection.set(id, new Hash());
                }
                this.gridSelection.get(id).set(element.value, $H({}));
                this.gridSelection.get(id).get(element.value).set('name', tr.select('td.name')[0].innerHTML);
                this.gridSelection.get(id).get(element.value).set('qty', tr.select('input.qty')[0].value);
                this.gridSelection.get(id).get(element.value).set('sku', tr.select('td.sku')[0].innerHTML);
            } else {
                this.gridSelection.get(id).unset(element.value);
            }
        }
    },
    productGridRowClick: function (grid, event) {
        var trElement = Event.findElement(event, 'tr');
        var isInput = Event.element(event).tagName == 'INPUT';
        if (trElement) {
            var checkbox = Element.select(trElement, 'input');
            if (checkbox[0]) {
                var checked = isInput ? checkbox[0].checked : !checkbox[0].checked;
                grid.setCheckboxChecked(checkbox[0], checked);
            }
        }
    },
    productGridRowInputChange: function (event) {
        var element = Event.element(event);
        if (!element.checkbox.checked) {
            return;
        }
        var id = element.up('table').id.split('_')[4];
        this.gridSelection.get(id).get(element.checkbox.value).set('qty', element.value);
    }
}


function closeBundlePopup(){

   
jQuery('#bundle_product_save_form').validation();

     if (jQuery('#bundle_product_save_form').validation('isValid')) {

         jQuery("button[data-role='closeBtn']").trigger( "click" );

    }


}

function observeMultiSelect(){

    jQuery(".bundle_customer_group").each(function (index) {
        
        jQuery(this).change(function (e) {
            if (jQuery(e.target).val()) {
                var selected = jQuery(e.target).val().join();
                jQuery("#" + e.target.id + "_hid").val(selected);
            }
        });
    });


jQuery(".bundle_stores").each(function (index) {

        jQuery(this).change(function (e) {
            if (jQuery(e.target).val()) {
                var selected = jQuery(e.target).val().join();
                jQuery("#" + e.target.id + "_hid").val(selected);
            }
        });
    });
}



