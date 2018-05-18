/**
* Copyright © 2016 ITORIS INC. All rights reserved.
* See license agreement for details
*/
define([
    'jquery',
	'uiRegistry',
    'prototype'
], function (jQuery, registry) {

    if (!Itoris) {
        var Itoris = {};
    }

    Itoris.OptionManager = Class.create();
    Itoris.OptionManager.prototype = {
        config : {},
        sections : [],
        sectionsHiddenInputs : [],
        sectionsBlocks : [],
        fieldTypes : {},
        translates : {},
        validationTypes : {},
        captchaTypes : {},
        isIE7 : false,
        isIE8 : false,
        fieldsCount : 0,
        fieldsItemsCount : 0,
        deletedFieldItems : [],
        formStyle : 'table_sections',
        optionsToDelete : [],
        fieldInternalId : 1,
        usedFieldInternalIds : [],
        conditionsObj : [],
        replaceJsonUndefined: false,
        initialize : function(config, sections) {
			var _this = this;
            this.config = config;
            this.configBox = this.getElementById('configs');
            this.configField = this.getElementById('config');
            this.configFieldInternal = this.getElementById('config_internal');
            this.fieldTypes = this.config.field_types;
            this.translates = this.config.translates;
            this.validationTypes = this.config.validation_types;
            this.calendarUrl = this.config.calendar_url;
            this.block = this.getElementById('field-manager-area');
            this.formStyleElm = $('itoris-dynamicproductoptions-form-style');
            this.formStyle = this.formStyleElm.value;
            var block1 = jQuery('<div class="setting_block1">')
                .append(jQuery('.field-itoris-dynamicproductoptions-form-style'))
                .append(jQuery('.field-itoris-dynamicproductoptions-appearance'));
            jQuery('.field-itoris-dynamicproductoptions-weight').after(block1);
            var block2 = jQuery('<div class="setting_block2">')
                .append(jQuery('.field-itoris-dynamicproductoptions-pricing'))
                .append(jQuery('.field-itoris-dynamicproductoptions-sku'))
                .append(jQuery('.field-itoris-dynamicproductoptions-weight'));
            jQuery(block1).after(block2);
            this.replaceJsonUndefined = Object.toJSON([undefined]) === '[]';
            if (sections.length) {
                this.createSections(sections.compact());
            } else {
                this.createSections([this.getDefaultSection(this.getFormStyle())], false);
            }
            Event.observe(this.getElementById('add-new-section'), 'click', this.addSection.bind(this, false));
            Event.observe(this.getElementById('remove-all-fields'), 'click', this.removeAllFieldsConfirmation.bind(this));
            Event.observe(this.formStyleElm, 'change', this.changeSectionsStyle.bind(this));
			
			jQuery('[data-form=edit-product], #edit_form').on('submit', function(){_this.prepareDataBeforeSubmit();});

			//since M2.1
			var form = registry.get("product_form.product_form");
			if (form) {
				form.prevSave = form.save;
				form.save = function(){
					_this.prepareDataBeforeSubmit();
					form.prevSave(arguments[0], arguments[1], arguments[2], arguments[3]);
                    _this.restoreDataAfterSubmit();
				}
			}
	
            this.createPopup();
            Event.observe(document, 'mouseup', this.unregisterActiveField.bind(this));
            Event.observe(document, 'mousemove', this.moveActiveField.bind(this));
            if (Prototype.Browser.IE) {
                var ieVersion = parseInt(navigator.userAgent.substring(navigator.userAgent.indexOf("MSIE")+5));
                this.isIE7 = ieVersion == 7;
                this.isIE8 = ieVersion == 8;
            }
            this.initCustomerGroupsPopup();
            this.addUseGlobal();
        },
		prepareDataBeforeSubmit: function(){
			itorisDynamicOptions.updateConfigField();
			//itorisDynamicOptions.serializeConfigElements();
			$('itoris-dynamicoptions-configs').up().insert({before: $('itoris-dynamicoptions-config')});
            $('itoris-dynamicoptions-config').setAttribute('data-form-part', 'product_form');
			//itorisDynamicOptions.compressPost();
			var allDynamicOptionInputs = $$('#itoris-dynamicoptions-configs input');
			allDynamicOptionInputs.each(function(elm){elm._name = elm.name; elm.removeAttribute('name'); elm.disabled = true;});
		},
        restoreDataAfterSubmit: function() {
            var allDynamicOptionInputs = $$('#itoris-dynamicoptions-configs input');
            allDynamicOptionInputs.each(function(elm){elm.setAttribute('name', elm._name); elm.disabled = false;});
        },
        addUseGlobal: function(){
            if (this.config.store_id > 0) {
                var container = $('itoris-dynamicoptions-add-new-section').up(), use_global = new Element('span'), obj = this;
                use_global.update('<input type="checkbox" id="idpo_use_global" name="idpo_use_global" data-form-part="product_form" /><label for="idpo_use_global" style="margin-left:5px;">'+this.translates.use_global+'</label>');
                use_global.setStyle({marginLeft: '20px'});
                container.insert({bottom: use_global});
                var ch = container.select('input')[0];
                if (this.config.use_global) ch.checked = true;
                Event.observe(ch, 'click', function(){obj.toggleUseGlobal()});
                this.toggleUseGlobal();
            }
        },
        toggleUseGlobal: function(){
            var ch = $('idpo_use_global');
            $('itoris-dynamicoptions-add-new-section').style.display = ch.checked ? 'none' : 'inline';
            $('itoris-dynamicoptions-remove-all-fields').style.display = ch.checked ? 'none' : 'inline';
            $('itoris-dynamicoptions-field-manager-area').style.display = ch.checked ? 'none' : 'block';
            $('css_adjustments_div').style.display = ch.checked ? 'none' : 'block';
            $('extra_js_div').style.display = ch.checked ? 'none' : 'block';
            $('options_settings_div').style.display = ch.checked ? 'none' : 'block';
            if ($('options_templates_div')) $('options_templates_div').style.display = this.config.store_id > 0 ? 'none' : 'block';
            $('default_config_msg').style.display = ch.checked ? 'block' : 'none';
        },
        createSections : function(sections, isStyleChanged) {
            if (sections.length) {
                sections.each(function(section){
                    if (section !== null && section.fields) section.fields.each(function(field){
                        if (field !== null && field.visibility == 'visible' && !field.visibility_action) field.visibility_action = 'hidden';
                    });
                });
                if (this.getFormStyle() == 'table_sections') {
                    this.getElementById('add-new-section').show();
                } else {
                    this.getElementById('add-new-section').hide();
                }
                if (sections.length == 1) {
                    sections[0].order = 1;
                }
                sections = this.orderItems(sections);
                var maxCols = 1;
                var totalRows = 0;
                for (var i = 0; i < sections.length; i++) {
                    if (sections[i]) {
                        totalRows += sections[i].rows;
                        if (sections[i].cols > maxCols) {
                            maxCols = sections[i].cols;
                        }
                    }
                }
                this.sections = sections;
                if (this.getFormStyle() != 'table_sections' && isStyleChanged) {
                    var allFields = [];
                    var fieldOrder = 1;
                    var prevSectionOrder = 0;
                    for (var i = 0; i < sections.length; i++) {
                        if (sections[i] && sections[i].fields) {
                            if (this.getFormStyle() == 'list_div') {
                                var fields = this.orderItems(sections[i].fields);
                                for (var j = 0; j < fields.length; j++) {
                                    if (fields[j]) {
                                        fields[j].order = fieldOrder++;
                                        allFields.push(fields[j]);
                                    }
                                }
                            } else {
                                var fields = this.orderItemsForTable(sections[i].fields, sections[i].cols, maxCols);
                                for (var j = 0; j <= sections[i].rows * maxCols; j++) {
                                    if (fields[j]) {
                                        fields[j].order += prevSectionOrder;
                                        allFields.push(fields[j]);
                                    }
                                }
                                prevSectionOrder += sections[i].rows * maxCols;
                            }
                        }
                    }
                }
                for (var i = 0; i < sections.length; i++) {
                    if (sections[i]) {
                        if (this.getFormStyle() != 'table_sections' && isStyleChanged) {
                            this.sections = [];
                            sections[i].fields = allFields;
                            sections[i].title = null;
                            if (this.getFormStyle() == 'list_div') {
                                sections[i].cols = 1;
                                sections[i].rows = allFields.length || 3;
                            } else {
                                sections[i].cols = maxCols;
                                sections[i].rows = totalRows;
                            }
                        }
                        this.addSection(sections[i]);
                        if (this.getFormStyle() != 'table_sections') {
                            return;
                        }
                    }
                }
            }
        },
        addSection : function(section, changePosition) {
            if (this.getFormStyle() != 'table_sections' && this.sections.length) {
                if (!section || section.order != 1) {
                    return;
                }
            }
            if (!section) {
                section = this.getDefaultSection(this.getFormStyle());
            } else if (!section.fields) {
                section.fields = [];
            }
            this.sections[section.order] = section;
            var sectionBlock = this.createElement('div', 'section');
            this.sectionsBlocks[section.order] = sectionBlock;
            sectionBlock.appendChild(this.createSectionInfoBlock(section));
            if (section.template_id && section.template_id > 0) {
                sectionBlock.select('input, select, textarea').each(function(item){item.disabled = true;});
            }
            sectionBlock.appendChild(this.createSectionFieldsTable(section));
            if (section.template_id && section.template_id > 0) {
                sectionBlock.addClassName('dpo_from_template');
                var comment = new Element('span');
                comment.addClassName('section_template_comment');
                comment.innerHTML = 'This section is not editable and is inherited from template with ID: '+section.template_id+'. <a style="color:#fff;text-decoration:underline;" target="_blank" href="'+window.dpoDefaultTemplateURL.replace('/id/9999999/', '/id/'+section.template_id+'/')+'">Go to template</a>';
                sectionBlock.select('table')[0].insert({before: comment});
                sectionBlock.select('td').each(function(item){item.addClassName('readonly-cell')});
            }
            if (changePosition) {
                if (this.sections[section.order - 1]) {
                    this.sectionsBlocks[section.order - 1].insert({'after' : sectionBlock});
                } else {
                    this.block.insert({'top' : sectionBlock});
                }
            } else {
                this.block.appendChild(sectionBlock);
            }
            this.updateConfigField();
        },
        getAllFields : function() {
            var fields = [];
            for (var i = 0; i < this.sections.length; i++) {
                if (this.sections[i]) {
                    for (var j = 0; j < this.sections[i]['fields'].length; j++) {
                        if (this.sections[i]['fields'][j]) {
                            fields.push(this.sections[i]['fields'][j]);
                        }
                    }
                }
            }
            return fields;
        },
        /**
         * update element value of current template configuration (json)
         */
        updateConfigField : function() {
            var resetParams = ['is_delete', 'previous_type', 'previous_group', 'temp_id', 'itoris_is_delete'];
            var tempSections = this.objectToJson(this.sections).evalJSON();
            this.configFieldInternal.value = this.objectToJson(tempSections);
            for (var i = 0; i < tempSections.length; i++) {
                if (tempSections[i]) {
                    for (var j = 0; j < tempSections[i].fields.length; j++) {
                        if (tempSections[i].fields[j]) {
                            if(tempSections[i].fields[j].is_delete == 1) var deleteId = tempSections[i].fields[j]['internal_id'] ;
                            this.deleteParamsFromObject(tempSections[i].fields[j], resetParams);
                            tempSections[i].fields[j]['id'] = 0;
                            if (!tempSections[i].fields[j]['option_id']) tempSections[i].fields[j]['option_id'] = 0;
                            tempSections[i].fields[j]['itoris_option_id'] = 0;
                            if (tempSections[i].fields[j].items) {
                                for (var z = 0; z < tempSections[i].fields[j].items.length; z++) {
                                    if (tempSections[i].fields[j].items[z]) {
                                        this.deleteParamsFromObject(tempSections[i].fields[j].items[z], resetParams);
                                        //tempSections[i].fields[j].items[z]['option_type_id'] = -1;
                                    }
                                }
                            }
                        }
                    }
                }
            }
            this.configField.value = this.objectToJson(tempSections);
            return this.configField.value;
        },
        deleteParamsFromObject : function(obj, params) {
            if (obj) {
                for (var i = 0; i < params.length; i++) {
                    if (typeof obj[params[i]] != 'undefined') {
                        delete obj[params[i]];
                    }
                }
            }
        },
        /**
         * update inputs values (that will be transfer to server) for field
         *
         * @param field
         */
        updateFieldConfigFields : function(field) {
            this.updateFieldSortOrderValues();
            var defaultParams = [
                'id', 'option_id', 'is_delete', 'previous_type', 'previous_group', 'itoris_option_id', 'itoris_is_delete',
                'internal_id', 'visibility_condition', 'visibility_action', 'visibility', 'customer_group',
                'title', 'type', 'is_require', 'sort_order', 'price', 'price_type', 'sku', 'max_characters', 'file_extension',
                'image_size_x', 'image_size_y', 'validation', 'default_value', 'hide_on_focus', 'comment', 'css_class', 'html_args',
                'img_src', 'img_alt', 'img_title', 'static_text', 'order', 'section_order','default_select_title', 'tier_price', 'weight'
            ];
            var systemParams = ['id', 'option_id', 'is_delete', 'previous_type', 'previous_group',
                'title', 'type', 'is_require', 'sort_order', 'price', 'price_type', 'sku', 'max_characters', 'file_extension',
                'image_size_x', 'image_size_y', 'option_type_id'
            ];
            var priceParams = [{code: 'price', value: 0}, {code: 'price_type', value: 'fixed'}];
            if (typeof field['is_delete'] == 'undefined') {
                field.is_delete = 0;
            }
            if (typeof field['itoris_is_delete'] == 'undefined') {
                field.itoris_is_delete = 0;
            }
            if (this.isCustomFieldType(field)) {
                field.is_require = 0;
                if (typeof field['title'] == 'undefined') {
                    field['title'] = '';
                }
            } else if (!this.isOptionsFieldType(field)) {
                for (var i = 0; i < priceParams.length; i++) {
                    if (typeof field[priceParams[i].code] == 'undefined') {
                        field[priceParams[i].code] = priceParams[i].value;
                    }
                }
            }
            for (var i = 0; i < defaultParams.length; i++) {
                if (defaultParams[i] in field) {
                    var value = field[defaultParams[i]];
                    var elementId = 'itoris-dynamicoptions-option-' + field.temp_id + '-' + defaultParams[i];
                    var element = $(elementId);
                    if (!element) {
                        element = this.addFieldConfigElm(elementId, field.temp_id, defaultParams[i]);
                        element.isSaved = field.option_id || field.itoris_option_id;
                        element.isSystem = systemParams.indexOf(defaultParams[i]) != -1;
                    }
                    element.value = value;
                    if ((field.id  && defaultParams[i] == 'is_delete') || (field.itoris_option_id && defaultParams[i] == 'itoris_is_delete')) {
                        this.optionsToDelete.push(element);
                    }
                }
            }
            if (field.items) {
                var itemsParams = [
                    'title', 'price', 'price_type', 'sku', 'image_src', 'color', 'swatch', 'use_qty', 'is_selected', 'is_disabled', 'carriage_return','sort_order',
                    'css_class', 'order','option_type_id', 'is_delete', 'visibility_condition', 'visibility_action', 'visibility', 'customer_group',
                    'sku_is_product_id', 'tier_price', 'weight'
                ];
                for (var i = 0; i < field.items.length; i++) {
                    if (field.items[i]) {
                        if (typeof field.items[i].is_delete == 'undefined') {
                            field.items[i].is_delete = 0;
                        }
                        if (typeof field.items[i].title == 'undefined') {
                            field.items[i].title = '';
                        }
                        for (var p = 0; p < priceParams.length; p++) {
                            if (typeof field.items[i][priceParams[p].code] == 'undefined') {
                                field.items[i][priceParams[p].code] = priceParams[p].value;
                            }
                        }

                        field.items[i].sort_order = field.items[i].order;
                        for (var j = 0; j < itemsParams.length; j++) {
                            if (itemsParams[j] in field.items[i]) {
                                var value = field.items[i][itemsParams[j]];
                                var elementId = 'itoris-dynamicoptions-option-' + field.temp_id + '-values-' + field.items[i].temp_id + '-' + itemsParams[j];
                                var element = $(elementId);
                                if (!element) {
                                    element = this.addFieldConfigElm(elementId, field.temp_id, itemsParams[j], true, field.items[i].temp_id);
                                    element.isSaved = field.option_id || field.itoris_option_id;
                                    element.isSystem = systemParams.indexOf(itemsParams[j]) != -1;
                                }
                                element.value = (value === true ? 1 : (value === false ? 0 : value));
                                if (field.items[i].option_type_id && itemsParams[j] == 'is_delete') {
                                    this.optionsToDelete.push(element);
                                }
                            }
                        }
                    }
                }
                //ids of deleted items
                for (var i = 0; i < this.deletedFieldItems.length; i++) {
                    var elementId = 'itoris-dynamicoptions-option-' + field.temp_id + '-values-' + this.deletedFieldItems[i] + '-is_delete';
                    var element = $(elementId);
                    if (!element) {
                        element = this.addFieldConfigElm(elementId, field.temp_id, 'is_delete', true, this.deletedFieldItems[i]);
                    }
                    element.value = 1;
                }
                this.deletedFieldItems = [];
            }
            this.updateConfigField();
        },
        serializeConfigElements : function() {
            var serializedField = $('itoris-dynamicoptions-configs-serialized');
            var configsBlock = $('itoris-dynamicoptions-configs');
            /* max_input_vars by default is 1000 */
            if (serializedField && configsBlock && configsBlock.select('input').length > 800) {
                var elms = [];
                configsBlock.select('input').each(function(elm){
                    if (typeof elm.isSystem == 'undefined' || !elm.isSystem) {
                        elms.push(elm);
                    }
                });

                serializedField.value = Form.serializeElements(elms);
                serializedField.disabled = false;
                elms.each(function(elm){elm.disabled=true;});
            }
        },
        compressPost: function() {
            var elements = $$('input[name^="product[options"]'), obj = {};
            elements.each(function(elm){
                var name = elm.name, value = elm.value;
                name = name.replace(/\]/g, '');
                var nameParts = name.split('[');
                for(var i=0, tmp = obj; i<nameParts.length - 1; i++) {
                    if (!tmp[nameParts[i]]) tmp[nameParts[i]] = {};
                    tmp = tmp[nameParts[i]];
                }
                tmp[nameParts[i]] = value;
            });
            var fld = new Element('input', {type: 'hidden', name:'itoris_dynamicproductoptions[postcompressed]', 'data-form-part': 'product_form'});
            fld.value = Object.toJSON(obj);
			$('itoris-dynamicoptions-configs').up().insert({before: fld});
            //jQuery('[data-form=edit-product]').append(fld);
        },
        updateFieldSortOrderValues: function() {
            var sortOrderCorrection = 0;
            for (var i = 0; i < this.sections.length; i++) {
                if (this.sections[i]) {
                    for (var j = 0; j < this.sections[i].fields.length; j++) {
                        var field = this.sections[i].fields[j];
                        if (field) {
                            field.sort_order = field.order + sortOrderCorrection;
                        }
                    }
                    sortOrderCorrection += (this.sections[i].cols * this.sections[i].rows);
                }
            }
        },
        addFieldConfigElm : function(elementId, optionId, paramCode, isValues, valueId) {
            var element = this.createElement('input');
            element.type = 'hidden';
            element.id = elementId;
            var elmName = 'product[options][' + optionId + ']';
            if (isValues) {
                elmName += '[values][' + valueId + ']';
            }
            element.name = elmName + '[' + paramCode + ']';
            this.configBox.appendChild(element);
            return element;
        },
        changeSectionsStyle : function() {
            if (this.getFormStyle() != this.formStyleElm.value) {
                if (confirm(this.translates.change_option_style.replace('%s', this.config.form_styles[this.formStyleElm.value]))) {
                    this.formStyle = this.formStyleElm.value;
                    var sections = this.getElementById('config_internal').value.isJSON() ? this.getElementById('config_internal').value.evalJSON() : [this.getDefaultSection(this.getFormStyle())];
                    this.removeAllSections(true);
                    this.createSections(sections, true);
                } else {
                    this.formStyleElm.value = this.getFormStyle();
                }
            }
        },
        getDefaultSection : function(formStyle) {
            var section = {
                order     : this.sections.length,
                cols      : 3,
                rows      : 3,
                removable : 1,
                title     : '',
                fields    : []
            };
            if (formStyle == 'list_div') {
                section.cols = 1;
            }

            return section;
        },
        getFormStyle : function() {
            return this.formStyle;
        },
        createSectionInfoBlock : function(section) {
            var infoBlock = this.createElement('div', 'info');
            var colsRowsSelects = this.createElement('div', 'cols-rows-selects');
            if (this.getFormStyle() != 'list_div') {
                var colsLabel = this.createElement('span');
                colsLabel.update(this.translates.columns + ':');
                colsRowsSelects.appendChild(colsLabel);
                var colsSelect = this.createElement('select');
                Event.observe(colsSelect, 'change', this.changeSectionTable.bind(this, section.order, 'cols', colsSelect));
                var selectOption = null;
                for (var i = 1; i <= 10; i++) {
                    selectOption = this.createElement('option');
                    selectOption.value = i;
                    selectOption.update(i);
                    if (i == section.cols) {
                        selectOption.selected = true;
                    }
                    colsSelect.appendChild(selectOption);
                }
                colsRowsSelects.appendChild(colsSelect);
            }
            var rowsLabel = this.createElement('span');
            rowsLabel.update(this.translates.rows + ':');
            colsRowsSelects.appendChild(rowsLabel);
            var rowsSelect = this.createElement('select');
            Event.observe(rowsSelect, 'change', this.changeSectionTable.bind(this, section.order, 'rows', rowsSelect));
            var selectOption = null;
            for (var i = 1; i <= 100; i++) {
                selectOption = this.createElement('option');
                selectOption.value = i;
                selectOption.update(i);
                if (i == section.rows) {
                    selectOption.selected = true;
                }
                rowsSelect.appendChild(selectOption);
            }
            colsRowsSelects.appendChild(rowsSelect);
            infoBlock.appendChild(colsRowsSelects);
            if (this.getFormStyle() == 'table_sections') {
                var sectionLabel = this.createElement('span');
                sectionLabel.setStyle({float: 'left', paddingTop: '3px'});
                sectionLabel.update(this.translates.sectionLabel + ': ');
                infoBlock.appendChild(sectionLabel);
                var sectionLabelInput = this.createElement('input', 'label-input');
                Event.observe(sectionLabelInput, 'change', this.changeData.bind(this,section.order, 'title', 'section', sectionLabelInput));
                if (section.title) {
                    sectionLabelInput.value = section.title;
                }
                //sectionLabelInput.name = 'section_' + parseInt(Math.random() * 100);
                sectionLabelInput.style.width = '100%';
                var sectionLabelInputBox = this.createElement('div');
                sectionLabelInputBox.setStyle({float:'left', width: '230px', marginLeft: '5px', marginRight: '10px'});
                infoBlock.appendChild(sectionLabelInputBox);
                sectionLabelInputBox.appendChild(sectionLabelInput);
                var moveUpLink = this.createElement('span', 'action-link');
                Event.observe(moveUpLink, 'click', this.moveSection.bind(this, section.order, true));
                infoBlock.appendChild(moveUpLink);
                var delimiter = this.createElement('span');
                infoBlock.appendChild(delimiter);
                if ((section.order - 1 >= 0) && this.sections[section.order - 1]) {
                    moveUpLink.update(this.translates.moveUp);
                    if (this.sections[section.order + 1]) {
                        delimiter.update(' | ');
                    }
                } else {
                    moveUpLink.removeClassName('action-link');
                }
                var moveDownLink = this.createElement('span', 'action-link');
                moveDownLink.update(this.translates.moveDown);
                Event.observe(moveDownLink, 'click', this.moveSection.bind(this, section.order, false));
                infoBlock.appendChild(moveDownLink);
                if (!this.sections[parseInt(section.order) + 1]) {
                    delimiter.update();
                    moveDownLink.update();
                }
                this.sections[section.order].moveUp = moveUpLink;
                this.sections[section.order].delimiter = delimiter;
                this.sections[section.order].moveDown = moveDownLink;
                if (!this.sections[section.order + 1] && this.sections[section.order - 1]) {
                    this.sections[section.order - 1].moveUp.show();
                    if (this.sections[section.order - 2]) {
                        this.sections[section.order - 1].delimiter.update(' | ');
                        this.sections[section.order - 1].moveDown.update(this.translates.moveDown);
                    }
                }
                if (this.isSectionRemovable(section)) {
                    var removeLink = this.createElement('span', 'action-link');
                    removeLink.update(this.translates.remove);
                    infoBlock.appendChild(removeLink);
                    Event.observe(removeLink, 'click', this.removeSection.bind(this, section.order));
                }

                var visibilityBlock = $('itoris-dynamicoptions-sections-visibility-fields').cloneNode(true);
                infoBlock.appendChild(visibilityBlock);
                visibilityBlock.show();
                visibilityBlock.id = '';

                var visibilityElm = visibilityBlock.select('.dynamicoptions-visibility')[0];

                var visibilityActionElm = visibilityBlock.select('.dynamicoptions-visibility_action')[0];
                Event.observe(visibilityElm, 'change', this.changeOptionVisibility.bind(this, visibilityElm, visibilityActionElm, this.sections[section.order]));

                Event.observe(visibilityElm, 'change', this.changeData.bind(this,section.order, 'visibility', 'section', visibilityElm));
                Event.observe(visibilityActionElm, 'change', this.changeData.bind(this,section.order, 'visibility_action', 'section', visibilityActionElm));
                //visibilityBlock.select('.dynamicoptions-visibility_condition')[0].name = 'section_visibility' + parseInt(Math.random() * 100);
                if (section.visibility) {
                    visibilityElm.value = section.visibility;
                    visibilityActionElm.value = section.visibility_action;
                }
                this.changeOptionVisibility(visibilityElm, visibilityActionElm, this.sections[section.order]);

                var conditionIcon = visibilityBlock.select('.edit-field-condition')[0];
                if (conditionIcon) {
                    setTimeout(function(){
                        /** object is not created yet **/
                        this.prepareConditionIcon(conditionIcon, this.sections[section.order], visibilityBlock.select('.dynamicoptions-visibility_condition')[0], true);
					}.bind(this), 100);
                    Event.observe(conditionIcon, 'click', function() {
                        this.prepareConditionIcon(conditionIcon, this.sections[section.order], visibilityBlock.select('.dynamicoptions-visibility_condition')[0], false, true);
                    }.bind(this));
                }
            }
            return infoBlock;
        },
        isSectionRemovable : function(section) {
            return true;
        },
        changeSectionTable : function(sectionOrder, type, selectBox) {
            var section = this.sections[sectionOrder];
            var newValue = parseInt(selectBox.value);
            var tempField = null;
            var tempFields = [];
            if (type == 'cols') {
                if (parseInt(newValue) < parseInt(section.cols)) {
                    for (var i = 1; i <= section.rows; i++) {
                        for (var j = 1; j <= section.cols; j++) {
                            var num = (i-1) * section.cols + j;
                            var correction = section.cols * (i - 1);
                            if ((num - correction) > newValue && section['fields'][num]) {
                                alert(this.translates.cannotResizeTable);
                                selectBox.value = section[type];
                                return;
                            }
                        }
                    }
                }
                for (var i = 1; i <= section.rows; i++) {
                    for (var j = 1; j <= section.cols; j++) {
                        var num = (i-1) * section.cols + j;
                        var correction = (newValue - section.cols) * (i - 1);
                        if (section['fields'][num]) {
                            tempField = section['fields'][num];
                            tempField.order = parseInt(tempField.order) + correction;
                            tempFields[tempField.order] = tempField;
                        }
                    }
                }
                section['fields'] = tempFields;
            }
            if (type == 'rows') {
                for (var i = parseInt(newValue) + 1; i <= section.rows; i++) {
                    for (var j = 1; j <= section.cols; j++) {
                        var num = (i-1) * section.cols + j;
                        if (section['fields'][num]) {
                            alert(this.translates.cannotResizeTable);
                            selectBox.value = section[type];
                            return;
                        }
                    }
                }
            }
            section[type] = newValue;
            this.sectionsBlocks[sectionOrder].remove();

            this.addSection(section, true);
        },
        removeSection : function(sectionOrder) {
            if (this.config.store_id > 0) {
                for (var j = 0, f = 0; j < this.sections[sectionOrder].fields.length; j++) {
                    if (this.sections[sectionOrder].fields[j]) f++;
                }
                if (f > 0) {
                    alert(this.translates.cantRemoveSection);
                    return;
                }
            }

            if (confirm(this.translates.removeSection)) {
                var sectionsAfter = [];
                var section = null;
                for (var i = sectionOrder; i < this.sections.length; i++) {
                    if (this.sections[i]) {
                        if (i != sectionOrder) {
                            section = this.sections[i];
                            section.order--;
                            sectionsAfter.push(section);
                        }
                        for (var j = 0; j < this.sections[i].fields.length; j++) {
                            var field = this.sections[i].fields[j];
                            if (field) {
                                if (i == sectionOrder) {
									field.itoris_is_delete = 1;
									field.is_delete = 1;
                                }
                                this.updateFieldConfigFields(field);
                            }
                        }
                        this.sectionsBlocks[i].remove();
                        delete this.sectionsBlocks[i];
                        delete this.sections[i];
                    }
                }
                this.sections.length--;
                this.updateConfigField();
                for (i = 0; i < sectionsAfter.length; i++) {
                    this.addSection(sectionsAfter[i]);
                }
            }
        },
        removeAllSections : function(removeConfigs) {
            for (var i = 0; i < this.sections.length; i++) {
                if (this.sections[i]) {
                    this.sectionsBlocks[i].remove();
                    delete this.sectionsBlocks[i];
                    delete this.sections[i];
                }
            }
            this.sections = [];

            var configs = this.getElementById('configs').select('input');
            var permanentConfigsBox = $('itoris-dynamicoptions-permanent-configs');
            for (var i = 0; i < configs.length; i++) {
                if (!(configs[i].id == 'itoris-dynamicoptions-config' || configs[i].id == 'itoris-dynamicoptions-config_internal')) {
                    if (removeConfigs || !configs[i].isSaved) {
                        configs[i].remove();
                    } else {
                        permanentConfigsBox.appendChild(configs[i]);
                    }
                }
            }
        },
        removeAllFieldsConfirmation: function() {
            if (confirm(this.translates.remove_all_fields)) {
                this.removeAllSections(false);
                this.setIsDeleteFlagToAllOptions();
                this.createSections([this.getDefaultSection(this.getFormStyle())], false);
            }
        },
        setIsDeleteFlagToAllOptions : function() {
            for (var i = 0; i < this.optionsToDelete.length; i++) {
                this.optionsToDelete[i].value = 1;
            }
        },
        /**
         * Move selected section up or down
         *
         * @param sectionOrder
         * @param moveTo = true (move section up), false (move section down)
         */
        moveSection : function(sectionOrder, moveTo) {
            var nextPos = sectionOrder;
            (moveTo) ? --nextPos : ++nextPos;
            var currentPosSection = this.sections[sectionOrder];
            this.sectionsBlocks[sectionOrder].remove();
            var nextPosSection = this.sections[nextPos];
            this.sectionsBlocks[nextPos].remove();
            currentPosSection.order = nextPos;
            nextPosSection.order = sectionOrder;
            if (moveTo) {
                this.addSection(currentPosSection, true);
                this.addSection(nextPosSection, true);
            } else {
                this.addSection(nextPosSection, true);
                this.addSection(currentPosSection, true);
            }
        },
        changeData : function(sectionOrder, key, type, inputField) {
            switch (type) {
                case 'section' :
                    this.sections[sectionOrder][key] = inputField.value;
                    this.updateConfigField();
                    break;
            }
        },
        createSectionFieldsTable : function(section) {
            var fieldsTable = this.createElement('table');
            var row = null;
            var col = null;
            var box = null;
            var fieldBlock = null;
            var num = 0;
            if (section.fields) {
                section.fields = this.orderItems(section.fields);
            }
            var tableTBody = this.createElement('tbody');
            fieldsTable.appendChild(tableTBody);
            for (var i = 1; i <= section.rows; i++) {
                row = this.createElement('tr');
                for (var j = 1; j <= section.cols; j++) {
                    col = this.createElement('td');
                    num = (i-1) * section.cols + j;
                    var editButton = this.createElement('div', 'edit-button');
                    editButton.hide();

                    var reorderBox = null;
                    Event.observe(editButton, 'mouseover', this.showElm.bind(this, editButton));
                    Event.observe(editButton, 'mousedown', this.editField.bind(this, section.order, num, col));
                    if (section.fields && section.fields[num]) {
                        fieldBlock = this.createField(section.fields[num], section.order, col);
                        fieldBlock.insert({top : editButton});
                        reorderBox = $('itoris-dynamicoptions-reorder-arrows-template').cloneNode(true);
                        reorderBox.id = '';
                        this.prepareReorderArrows(reorderBox, section.fields[num]);
                        Event.observe(reorderBox, 'mouseover', this.showElm.bind(this, reorderBox));
                        fieldBlock.insert({top: reorderBox});
                        col.appendChild(fieldBlock);
                        var dragDrop = this.createElement('div', 'drag-n-drop');
                        fieldBlock.appendChild(dragDrop);
                    } else {
                        col.addClassName('empty');
                        fieldBlock = this.createElement('div', 'field-container');
                        fieldBlock.appendChild(editButton);
                        col.appendChild(fieldBlock);
                        //box.appendChild(this.createEmptyCeil());
                    }
                    col.addClassName('field-ceil');
                    col.section = section.order;
                    col.form = num;

                    Event.observe(col, 'mouseover', this.showElm.bind(this, [editButton, reorderBox]));
                    Event.observe(col, 'mouseout', this.hideElm.bind(this, [editButton, reorderBox]));
                    row.appendChild(col);
                }
                tableTBody.appendChild(row);
            }
            return fieldsTable;
        },
        showElm : function(elm) {
            this.changeElmDisplay(elm, true);
        },
        hideElm : function(elm) {
            this.changeElmDisplay(elm, false);
        },
        changeElmDisplay: function(elms, showElm) {
            if (elms instanceof Array) {
                elms.each(function(elm){
                    if (elm) {
                        if (showElm) {
                            elm.show();
                        } else {
                            elm.hide();
                        }
                    }
                });
            } else {
                if (showElm) {
                    elms.show();
                } else {
                    elms.hide();
                }
            }
        },
        prepareReorderArrows: function(arrows, field) {
            arrows.select('.reorder-arrow-bottom')[0].observe('mousedown', this.reorderField.bind(this, field, 'down'));
            arrows.select('.reorder-arrow-top')[0].observe('mousedown', this.reorderField.bind(this, field, 'up'));
            arrows.select('.reorder-arrow-left')[0].observe('mousedown', this.reorderField.bind(this, field, 'left'));
            arrows.select('.reorder-arrow-right')[0].observe('mousedown', this.reorderField.bind(this, field, 'right'));
        },
        reorderField : function(field, direction) {
            this.doNotMoveField = true;
            var targetFieldOrder = null;
            var section = this.sections[field.section_order];
            switch (direction){
                case 'up':
                    targetFieldOrder = field.order - section.cols;
                    if (targetFieldOrder < 1) {
                        targetFieldOrder = null;
                    }
                    break;
                case 'down':
                    targetFieldOrder = field.order + section.cols;
                    if (targetFieldOrder > section.cols * section.rows) {
                        targetFieldOrder = null;
                    }
                    break;
                case 'left':
                    if (section.cols != 1 && field.order % section.cols != 1) {
                        targetFieldOrder = field.order - 1;
                    }
                    break;
                case 'right':
                    if (section.cols != 1 && field.order % section.cols) {
                        targetFieldOrder = field.order + 1;
                    }
                    break;
            }

            if (!targetFieldOrder) {
                setTimeout(function(){this.doNotMoveField = false}.bind(this), 100);
                return;
            }
            this.activeField = null;
            var targetCeil = $$('#itoris-dynamicoptions-field-manager-area .section')[section.order - 1].select('.field-ceil')[targetFieldOrder - 1];
            if (targetCeil.hasClassName('empty')) {
                delete this.sections[section.order]['fields'][field.order];
                field.order = targetFieldOrder;
                field.sort_order = targetFieldOrder;
                this.sections[section.order]['fields'][targetFieldOrder] = field;
                this.sectionsBlocks[section.order].remove();
                this.addSection(this.sections[section.order], true);
            } else {
                this.sections[section.order]['fields'][field.order] = this.sections[section.order]['fields'][targetFieldOrder];
                this.sections[section.order]['fields'][field.order].order = field.order;
                this.sections[section.order]['fields'][field.order].sort_order = field.order;
                field.order = targetFieldOrder;
                field.sort_order = targetFieldOrder;
                this.sections[section.order]['fields'][targetFieldOrder] = field;
                this.sectionsBlocks[section.order].remove();
                this.addSection(this.sections[section.order], true);
            }
            setTimeout(function(){this.doNotMoveField = false}.bind(this), 100);
        },
        editField : function(sectionOrder, fieldOrder, ceil) {
            this.doNotMoveField = true;
            this.showPopup();
            this.activeField = null;
            var fieldConfig = this.sections[sectionOrder]['fields'] && this.sections[sectionOrder]['fields'][fieldOrder];
            this.tempFieldConfig = fieldConfig ? this.objectToJson(fieldConfig).evalJSON() : {order : fieldOrder, removable : true, option_id: 0, id:1, is_require: 0, hide_on_focus: 1};
            if (fieldConfig && typeof fieldConfig.items != 'undefined') {
                this.tempFieldConfig.items = [];
                for (var i = 0; i < fieldConfig.items.length; i++) {
                    if (typeof fieldConfig.items[i] != 'undefined') {
                        this.tempFieldConfig.items[i] = this.objectToJson(fieldConfig.items[i]).evalJSON()
                    }
                }
            }
            this.loadFieldTemplate();
            this.prepareFieldConfig(sectionOrder, this.tempFieldConfig);
            this.createFiledConfigButtons(sectionOrder, this.tempFieldConfig, ceil, fieldOrder);			
			this.updatePopupPosition();
        },
		updatePopupPosition: function() {
			var t = jQuery(window).scrollTop() + (jQuery(window).height() - jQuery(this.popup.window).height()) / 2;
			var l = jQuery(window).scrollLeft() + (jQuery(window).width() - jQuery(this.popup.window).width()) / 2;
			t = t < jQuery(window).scrollTop() + 5 ? jQuery(window).scrollTop() + 5 : t;
			l = l < jQuery(window).scrollLeft() + 5 ? jQuery(window).scrollLeft() + 5 : l;
			jQuery(this.popup.window).css({top: t + 'px', left: l + 'px'});			
		},
        loadFieldTemplate : function() {
            this.popup.config.update();
            var configTable = this.createElement('table');
            configTable.update($('itoris-dynamicoptions-field-configuration').innerHTML);
            this.popup.config.appendChild(configTable);
        },
        createFiledConfigButtons : function(sectionOrder, fieldConfig, ceil, fieldOrder) {
            var box = this.popup.buttons;
            box.update();
            if (fieldConfig) {
                var removeButton = this.createButton(this.translates.remove, 'float-right'), isAllowDelete = "image,html".indexOf(fieldConfig.type) > -1 || this.config.store_id == 0;
                if (isAllowDelete) removeButton.removeClassName('disabled'); else removeButton.addClassName('disabled');
                removeButton.disabled = !isAllowDelete;
                Event.observe(removeButton, 'click', this.removeField.bind(this, sectionOrder, fieldOrder, ceil));
                box.appendChild(removeButton);
            }
            var applyButton = this.createButton(this.translates.apply, 'float-left');
            Event.observe(applyButton, 'click', this.changeFieldData.bind(this, sectionOrder, fieldOrder, ceil));
            box.appendChild(applyButton);
            var cancelButton = this.createButton(this.translates.cancel, 'float-left');
            Event.observe(cancelButton, 'click', this.hidePopup.bind(this));
            box.appendChild(cancelButton);
            return box;
        },
        removeField : function(sectionOrder, fieldOrder, ceil) {
            if (confirm(this.translates.removeField)) {
                this.activeField = null;
				this.sections[sectionOrder]['fields'][fieldOrder].itoris_is_delete = 1;
				this.sections[sectionOrder]['fields'][fieldOrder].is_delete = 1;
                this.updateFieldConfigFields(this.sections[sectionOrder]['fields'][fieldOrder]);
                delete this.sections[sectionOrder]['fields'][fieldOrder];
				this.updateConfigField();
                this.tempFieldConfig = null;
                this.hidePopup();
                var editButton = this.createElement('div', 'edit-button');
                editButton.hide();
                Event.observe(editButton, 'mouseover', this.showElm.bind(this, editButton));
                Event.observe(editButton, 'mousedown', this.editField.bind(this, sectionOrder, fieldOrder, ceil));
                var fieldBlock = this.createElement('div', 'field-container');
                fieldBlock.appendChild(editButton);
                ceil.update();
                ceil.addClassName('empty');
                ceil.appendChild(fieldBlock);
                Event.observe(ceil, 'mouseover', this.showElm.bind(this, editButton));
                Event.observe(ceil, 'mouseout', this.hideElm.bind(this, editButton));
            }
        },
        isCustomFieldType : function(field) {
            return field.type == 'image' || field.type == 'html';
        },
        isOptionsFieldType : function(field) {
            return field.type == 'multiple'
            || field.type == 'drop_down'
            || field.type == 'checkbox'
            || field.type == 'radio';
        },
        changeFieldData : function(sectionOrder, fieldOrder, ceil) {
            if (this.fieldConfigValid()) {
                switch (this.tempFieldConfig.type) {
                    case 'multiple':
                    case 'drop_down':
                    case 'checkbox':
                    case 'radio':
                        if (!this.tempFieldConfig.items.length) {
                            alert(this.translates.require_options);
                            return;
                        }
                }
                this.activeField = null;
                ceil.update();
                if (!this.sections[sectionOrder]['fields']) {
                    this.sections[sectionOrder]['fields'] = [];
                }
                if (this.popup.config.select('.dynamicoptions-hide_on_focus')[0]) {
                    this.tempFieldConfig.hide_on_focus = this.popup.config.select('.dynamicoptions-hide_on_focus')[0].checked ? 1 : 0;
                }
                this.sections[sectionOrder]['fields'][fieldOrder] = this.tempFieldConfig;
                var fieldBlock = this.createField(this.tempFieldConfig, sectionOrder, ceil);
                var dragDrop = this.createElement('div', 'drag-n-drop');
                fieldBlock.appendChild(dragDrop);
                var reorderBox = $('itoris-dynamicoptions-reorder-arrows-template').cloneNode(true);
                reorderBox.id = '';
                this.prepareReorderArrows(reorderBox, this.sections[sectionOrder]['fields'][fieldOrder]);
                Event.observe(reorderBox, 'mouseover', this.showElm.bind(this, reorderBox));
                fieldBlock.insert({top: reorderBox});
                var editButton = this.createElement('div', 'edit-button');
                editButton.hide();
                Event.observe(editButton, 'mouseover', this.showElm.bind(this, editButton));
                Event.observe(editButton, 'mousedown', this.editField.bind(this, sectionOrder, fieldOrder, ceil));
                fieldBlock.appendChild(editButton);
                Event.observe(ceil, 'mouseover', this.showElm.bind(this, [editButton, reorderBox]));
                Event.observe(ceil, 'mouseout', this.hideElm.bind(this, [editButton, reorderBox]));
                ceil.appendChild(fieldBlock);
                ceil.removeClassName('empty');
                this.tempFieldConfig = null;
                this.hidePopup();
            }
        },
        createButton : function(label, className) {
            var button = this.createElement('button', 'scalable ' + className);
            button.writeAttribute('type', 'button');
            var spanLabel = this.createElement('span');
            spanLabel.update(label);
            button.appendChild(spanLabel);
            return button;
        },
        fieldConfigValid : function() {
            var valid = true;
            valid = this.validateElements(this.popup.config.select('.required-entry'), valid);
            valid = this.validateElements(this.popup.config.select('.validate-one-required-by-name'), valid);
            valid = this.validateElements(this.popup.config.select('.validate-digits'), valid);
            valid = this.validateElements(this.popup.config.select('.validate-number'), valid);
            valid = this.validateElements(this.popup.config.select('.validate-alphanum'), valid);
            valid = this.validateElements(this.popup.config.select('.validate-dbname'), valid);
            if (this.popup.config.select('.condition-error').length) {
                valid = false;
                alert(this.translates.condition_error);
            }
            return valid;
        },
        validateElements : function(elms, valid) {
            var elmValid = true;
            for (var i = 0; i < elms.length; i++) {
                if (elms[i]) {
                    elmValid = Validation.validate(elms[i]);
                    valid = !valid ? valid : elmValid;
                }
            }
            return valid;
        },
        isSelectType : function(type) {
            return ['drop_down', 'multiple', 'radio', 'checkbox'].indexOf(type) != -1;
        },
        editFieldData : function(key, valueElm, isInt) {
            var value = valueElm.value;
            if (isInt) {
                value = parseInt(value);
                if (key == 'min_required') {
                    var itemsCount = this.countTempItems();
                    if (value > itemsCount) {
                        value = itemsCount;
                        valueElm.value = value;
                        alert(this.translates.minRequiredCheckboxes);
                    }
                }
            }
            this.tempFieldConfig[key] = value;
        },
        countTempItems : function() {
            var count = 0;
            for (var i = 0; i < this.tempFieldConfig.items.length; i++) {
                if (this.tempFieldConfig.items[i]) {
                    count++;
                }
            }
            return count;
        },
        getTypeGroup : function(type) {
            switch (type) {
                case 'field':
                case 'area':
                    return 'text';
                case 'date':
                case 'date_time':
                case 'time':
                    return 'date';
                case 'file':
                    return 'file';
                case 'drop_down':
                case 'multiple':
                case 'radio':
                case 'checkbox':
                    return 'select';
            }
            return null;
        },
        changeFieldType : function(sectionOrder, typeElm) {
            this.closeAdditionalPopups();
            this.tempFieldConfig.previous_type = this.tempFieldConfig.type;
            this.tempFieldConfig.previous_group = this.getTypeGroup(this.tempFieldConfig.type);
            this.tempFieldConfig.type = typeElm.value;

            this.loadFieldTemplate();
            this.prepareFieldConfig(sectionOrder, this.tempFieldConfig);
			this.updatePopupPosition();
        },
        createFieldTypesSelect : function() {
            var typeRow = this.createElement('div', 'float-right');

            var typeValue = this.createElement('div', 'value float-right');
            typeRow.appendChild(typeValue);

            var typeValueSelect = this.createElement('select');
            typeValueSelect.id = 'itoris_dynamicoptions_type_dropdown';
            typeValue.appendChild(typeValueSelect);
            var typeValueSelectOption = null;
            for (var key in this.fieldTypes) {
                if (this.fieldTypes[key]) {
                    typeValueSelectOption = this.createElement('option');
                    typeValueSelectOption.value = key;
                    typeValueSelectOption.update(this.fieldTypes[key].title);
                    typeValueSelect.appendChild(typeValueSelectOption);
                    //typeValueSelectOption.selected = (config && config.type == key);
                }
            }
            Event.observe(typeValueSelect, 'change', this.changeFieldType.bind(this, null, typeValueSelect));

            var typeLabel = this.createElement('span', 'label');
            typeRow.appendChild(typeLabel);

            typeLabel.update(this.translates.fieldType + ':');

            return typeRow;
        },
        createFieldCopySelect : function() {
            var typeRow = this.createElement('div', 'float-right');
            var typeValue = this.createElement('div', 'value float-right');
            typeRow.appendChild(typeValue);
            typeRow.style.marginRight = '10px';
            var typeValueSelect = this.createElement('select');
            typeValueSelect.id = 'itoris_dynamicoptions_copy_dropdown';
            typeValue.appendChild(typeValueSelect);

            Event.observe(typeValueSelect, 'change', this.copyField.bind(this, typeValueSelect));

            var typeLabel = this.createElement('span', 'label');
            typeRow.appendChild(typeLabel);

            typeLabel.update(this.translates.copy_field + ':');
            this.updateFieldCopyOptions(null, typeValueSelect);

            if (this.config.store_id > 0) typeValueSelect.disabled = true;

            return typeRow;
        },
        updateFieldCopyOptions: function(excludeId, dropdown) {
            var dropdown = dropdown || $('itoris_dynamicoptions_copy_dropdown');
            var optionElm = null;
            var fields = this.getAllFields();
            fields.unshift({internal_id: 0, title: this.translates.please_select });
            dropdown.select('option').each(function(elm){elm.remove();});
            for (var i = 0; i < fields.length; i++) {
                if (excludeId && excludeId == fields[i].internal_id) {
                    continue;
                }
                optionElm = this.createElement('option');
                optionElm.value = fields[i].internal_id;
                optionElm.update((fields[i].internal_id ? ('F' + fields[i].internal_id + ' - ') : '') + fields[i].title);
                dropdown.appendChild(optionElm);
            }
        },
        copyField: function(elm) {
            if (elm.value && confirm(this.translates.copy_field_confirm)) {
                this.closeAdditionalPopups();
                for (var i = 0; i < this.sections.length; i++) {
                    if (this.sections[i] && this.sections[i].fields) {
                        for (var j = 0; j < this.sections[i].fields.length; j++) {
                            if (this.sections[i].fields[j] && elm.value == this.sections[i].fields[j].internal_id) {
                                var useCurrentInternalId = this.tempFieldConfig ? this.tempFieldConfig.internal_id : false;
                                var fieldOrder = this.tempFieldConfig.order;
                                //this.tempFieldConfig = Object.clone(this.sections[i].fields[j]);
                                this.tempFieldConfig = JSON.parse(JSON.stringify(this.sections[i].fields[j])); //deep clone
                                this.tempFieldConfig.id = 0;
                                this.tempFieldConfig.option_id = 0;
                                this.tempFieldConfig.itoris_option_id = 0;
                                this.tempFieldConfig.order = fieldOrder;
                                this.tempFieldConfig.sort_order = fieldOrder;
                                this.tempFieldConfig.temp_id = 0;
                                if (typeof this.tempFieldConfig.items != 'undefined') {
                                    for (var z = 0; z < this.tempFieldConfig.items.length; z++) {
                                        if (this.tempFieldConfig.items[z]) {
                                            this.tempFieldConfig.items[z].option_id = 0;
                                            this.tempFieldConfig.items[z].option_type_id = -1;
                                        }
                                    }
                                }
                                if (useCurrentInternalId) {
                                    this.tempFieldConfig.internal_id = useCurrentInternalId;
                                } else {
                                    this.tempFieldConfig.internal_id = this.fieldInternalId;
                                }
                                if (this.tempFieldConfig.title) {
                                    this.tempFieldConfig.title += ' - Copy';
                                }
                                this.prepareFieldConfig(i, this.tempFieldConfig);
                                return;
                            }
                        }
                    }
                }
            }
        },
        getFieldByInternalId: function(internalId) {
            for (var i = 0; i < this.sections.length; i++) {
                if (this.sections[i] && this.sections[i].fields) {
                    for (var j = 0; j < this.sections[i].fields.length; j++) {
                        if (this.sections[i].fields[j] && internalId == this.sections[i].fields[j].internal_id) {
                            return this.sections[i].fields[j];
                        }
                    }
                }
            }
            return null;
        },
        prepareInputField : function(code, fieldConfig, isRequired) {
            var elm = this.popup.config.select('.dynamicoptions-' + code)[0];
            if (elm) {
                //for validation
                elm.name = Math.random();
                elm.value = typeof fieldConfig[code] != 'undefined' ? fieldConfig[code] : null;
                if (isRequired) {
                    elm.addClassName('required-entry');
                }
                Event.observe(elm, 'change', this.editFieldData.bind(this, code, elm, false));
                if (code == 'img_src') {
                    this.initImageUploader(elm, code, fieldConfig);
                }
            }
        },
        prepareSelectField : function(code, fieldConfig, numericValue) {
            var elm = this.popup.config.select('select.dynamicoptions-' + code)[0];
            if (elm) {
                //for validation
                elm.name = Math.random();
                elm.value = typeof fieldConfig[code] != 'undefined' ? fieldConfig[code] : null;
                Event.observe(elm, 'change', this.editFieldData.bind(this, code, elm, numericValue));
            }
        },
        createItemsOptions : function(config) {
            this.popup.config.select('.dynamicoptions-values-table tfoot tr.template')[0].hide();
            if (config && !config.items) {
                config.items = [];
            }
            var itemsCount = config.items.length;
            if (!itemsCount) {
                this.popup.config.select('.dynamicoptions-values-table thead tr')[0].hide();
            }
            var itemConfig = null;
            var tableTBody = this.popup.config.select('.dynamicoptions-values-table tbody')[0];
            if (config) {
                if (config.type == 'drop_down') {
                    this.popup.config.select('.dynamicoptions-values-table thead tr')[0].show();
                    this.popup.config.select('.dynamicoptions-values-table tbody tr')[0].show();
                    if (typeof config.default_select_title == 'undefined' || !config.default_select_title) {
                        config.default_select_title =  this.config.translates.please_select;
                    }
                    this.prepareInputField('default_select_title', config, true);
                }
                var items = config.items;
                this.tempFieldConfig.items = [];
                for (var i = 0; i < itemsCount; i++) {
                    if (items[i]) {
                        //	config.items[i] = {order: i, price_type: 'fixed', is_selected: 0, is_disabled: 0};
                        itemConfig = items[i];
                        this.createItemRow(itemConfig, tableTBody);
                    }
                }
            }
            this.updateItemsOrders();
            Event.observe(this.popup.config.select('.dynamicoptions-values-table tfoot .action-link')[0], 'click', this.changeItemCount.bind(this, config));
        },
        createItemRow : function(config, parentBlock) {
            this.popup.config.select('.dynamicoptions-values-table thead tr')[0].show();
            this.tempFieldConfig.items.push(config);
            var row = this.createElement('tr');
            row.update(this.popup.config.select('.dynamicoptions-values-table tfoot tr.template')[0].innerHTML);
            if (parentBlock) {
                parentBlock.appendChild(row);
            }
            config.row = row;
            this.prepareItemFieldCol(row, config, 'title', true, false);
            this.prepareItemFieldCol(row, config, 'image_src', false, false);
            this.prepareItemFieldCol(row, config, 'color', false, false);
            this.prepareItemCheckCol(row, config, 'swatch', true);
            this.prepareItemFieldCol(row, config, 'price', false, true);
            this.prepareItemDropdownCol(row, config, 'price_type', false);
            this.prepareItemFieldCol(row, config, 'sku', false, false);
            this.prepareProductLinkAction(row, config);
            this.toggleWeightField(row, config);
            this.prepareTierAction(row, config);

            this.prepareItemCheckCol(row, config, 'use_qty', true);
            this.prepareItemCheckCol(row, config, 'is_selected', true);
            this.prepareItemDropdownCol(row, config, 'visibility', false);
            this.prepareItemDropdownCol(row, config, 'visibility_action', false);
            this.prepareItemFieldCol(row, config, 'visibility_condition', false, false);
            this.prepareItemFieldCol(row, config, 'groups', false, false);
            this.prepareItemCheckCol(row, config, 'carriage_return', true);
            this.prepareItemFieldCol(row, config, 'css_class', false, false);
            this.prepareItemFieldCol(row, config, 'order', true, true);
            this.prepareItemFieldCol(row, config, 'weight', false, true);
            var visibilityElm = row.select('select.dynamicoptions-value-visibility')[0];
            var elmOptions =  row.select('select.dynamicoptions-value-visibility.option');

            if (visibilityElm) {
                var visibilityActionElm = row.select('select.dynamicoptions-value-visibility_action')[0];
                Event.observe(visibilityElm, 'change', this.changeOptionVisibility.bind(this, visibilityElm, visibilityActionElm, config));
                this.changeOptionVisibility(visibilityElm, visibilityActionElm, config);
            }
            var conditionIcon = row.select('.edit-value-condition')[0];
            if (conditionIcon) {
                this.prepareConditionIcon(conditionIcon, config, row.select('.dynamicoptions-value-visibility_condition')[0]);
            }
            var customerGroupsSelect = row.select('.edit-value-groups')[0];
            if (customerGroupsSelect) {
                var textElm = row.select('.text-dynamicoptions-value-customer_group')[0];
                Event.observe(customerGroupsSelect, 'click', this.showCustomerGroupsPopup.bind(this, customerGroupsSelect, config, textElm, false));
                this.showCustomerGroupsPopup(customerGroupsSelect, config, textElm, true);
                this.applyCustomerGroupsPopup(config);
            }
            Event.observe(row.select('.sort-arrow-down')[0], 'click', this.moveItemRowDown.bind(this, config));
            Event.observe(row.select('.sort-arrow-up')[0], 'click', this.moveItemRowUp.bind(this, config));
            Event.observe(row.select('.remove-icon')[0], 'click', this.removeItemRow.bind(this, config));
            return row;
        },
        prepareProductLinkAction: function(row, config) {
            var addLinkElm = row.select('.add-product-link')[0];
            var removeLinkElm = row.select('.remove-product-link')[0];
            var editLinkElm = row.select('.edit-linked-item')[0];
            var pinLinkElm = row.select('.pin-linked-item')[0];
            if (addLinkElm && removeLinkElm) {
                addLinkElm.observe('click', this.showProductsGridPopup.bind(this, row, config));
                removeLinkElm.observe('click', this.removeItemSkuProductId.bind(this, row, config));
                if (editLinkElm) editLinkElm.observe('click', this.editItemSkuProductId.bind(this, row, config));
                if (pinLinkElm) pinLinkElm.observe('click', this.pinItemSkuProductId.bind(this, row, config));
            }
            this.updateItemSkuField(row, config);
        },
        toggleWeightField: function(row, config){return; //weight should always be editable
            var weightElm = row.select('.dynamicoptions-value-weight')[0];
            var skuElm = row.select('.dynamicoptions-value-sku')[0];
            if(skuElm.value != ''){
                weightElm.disabled = true;
            } else{
                weightElm.disabled = false;
            }
        },
        prepareTierAction: function(row, config) {
            var addTierElm = row.select('.edit-value-tier')[0];
            var hasTierElm = row.select('.dynamicoptions-has-tier')[0];
            var isHasTier = config.tier_price && config.tier_price.evalJSON().length > 0;
            hasTierElm.update(isHasTier ? '&#10004;' : '');
            addTierElm.observe('click', this.showTierPopup.bind(this, row, config));
        },
        addProductLinkToItem : function(productData) {
            this.productGridItemRow.select('input.dynamicoptions-value-sku')[0].value = productData.productId;
            this.productGridItemRow.select('input.dynamicoptions-value-title')[0].value = productData.name;
            this.productGridItemRow.select('input.dynamicoptions-value-price')[0].value = productData.price;
            this.productGridItemRow.select('select.dynamicoptions-value-price_type')[0].selectedIndex = 0;
            this.productGridItemConfig.sku = productData.productId;
            this.productGridItemConfig.sku_is_product_id = 1;
            this.productGridItemConfig.sku_is_product_id_linked = 1;
            this.productGridItemConfig.title = productData.name;
            this.productGridItemConfig.price = productData.price;
            this.productGridItemConfig.price_type = productData.price_type;
            delete this.productGridItemConfig.tier_price;
            this.hideProductsGridPopup();
            this.updateItemSkuField(this.productGridItemRow, this.productGridItemConfig);
        },
        removeItemSkuProductId : function(row, config) {
            var skuElm = row.select('input.dynamicoptions-value-sku')[0];
            skuElm.value = '';
            config.sku = '';
            config.sku_is_product_id = 0;
            config.sku_is_product_id_linked = 0;
            this.updateItemSkuField(row, config);
            this.toggleWeightField(row, config);
        },
        editItemSkuProductId : function(row, config) {
            config.sku_is_product_id_linked = 0;
            this.updateItemSkuField(row, config);
            this.toggleWeightField(row, config);
        },
        pinItemSkuProductId : function(row, config) {
            config.sku_is_product_id_linked = 1;
            this.updateItemSkuField(row, config);
            this.toggleWeightField(row, config);
        },
        updateItemSkuField: function(row, config) {
            var skuElm = row.select('input.dynamicoptions-value-sku')[0];
            if (skuElm) {
                var isProductId = config.sku_is_product_id || false;
                var tdElm = skuElm.up('td');
                if (isProductId) {
                    if (!tdElm.hasClassName('dynamicoptions-item-sku-is-product-id')) {
                        tdElm.addClassName('dynamicoptions-item-sku-is-product-id');
                        skuElm.disabled = true;
                    }
                } else if (tdElm.hasClassName('dynamicoptions-item-sku-is-product-id')) {
                    tdElm.removeClassName('dynamicoptions-item-sku-is-product-id');
                    skuElm.disabled = false;
                }
                if (config.sku_is_product_id_linked) {
                    row.addClassName('item-sku-is-product-id');
                    row.select('input.dynamicoptions-value-title')[0].disabled = true;
                    row.select('input.dynamicoptions-value-price')[0].disabled = true;
                    row.select('select.dynamicoptions-value-price_type')[0].disabled = true;
                } else {
                    row.removeClassName('item-sku-is-product-id');
                    row.select('input.dynamicoptions-value-title')[0].disabled = false;
                    row.select('input.dynamicoptions-value-price')[0].disabled = false;
                    row.select('select.dynamicoptions-value-price_type')[0].disabled = false;
                }

            }
            this.toggleWeightField(row, config);
        },
        prepareConditionIcon : function(conditionIcon, config, updateElm, onlyUpdateEm, openPopup) {
            var conditionObj = new ItorisDynamicOptionsCondition((config.visibility_condition ? config.visibility_condition.evalJSON() : null) || {type: 'all',	value: 1,conditions: []}, Math.random());
            this.conditionsObj.push(conditionObj);
            var offsets = conditionIcon.cumulativeOffset();
            conditionObj.setPopupPosition(offsets.top, offsets.left);
            var applyButton = this.createButton('Apply', 'save');
            Event.observe(applyButton, 'click', this.saveCondition.bind(this, config, conditionObj, updateElm));
            var cancelButton = this.createButton('Cancel', 'delete');
            Event.observe(cancelButton, 'click', function(){conditionObj.closePopup();}.bind(this));
            conditionObj.popup.appendChild(applyButton);
            conditionObj.popup.appendChild(cancelButton);
            conditionObj.applyButton = applyButton;
            conditionObj.cancelButton = cancelButton;
            if (updateElm) {
                updateElm.value = conditionObj.convertConditionToStr(conditionObj.conditions);
                updateElm.observe('change', this.updateConditions.bind(this, config, updateElm, conditionObj));
            }
            if (onlyUpdateEm) {
                return;
            }
            if (openPopup) {
                this.closeAdditionalPopups();
                var offsets = conditionIcon.cumulativeOffset();
                conditionObj.setPopupPosition(offsets.top, offsets.left);
                conditionObj.openPopup();
            } else {
                Event.observe(conditionIcon, 'click', function(){
                    this.closeAdditionalPopups();
                    var offsets = conditionIcon.cumulativeOffset();
                    conditionObj.setPopupPosition(offsets.top, offsets.left);
                    conditionObj.openPopup();
                }.bind(this));
            }
        },
        prepareItemFieldCol : function(row, config, optionCode, isRequired, isNum) {
            var input = row.select('input.dynamicoptions-value-' + optionCode)[0];
            if (input) {
                //for validation
                input.name = Math.random();
                if (isRequired) {
                    input.addClassName('required-entry');
                }
                if (isNum) {
                    input.addClassName('validate-number');
                }

                if (config && typeof config[optionCode] != 'undefined') {
                    input.value = config[optionCode];
                }
                Event.observe(input, 'change', this.editItemData.bind(this, config, optionCode, input, isNum, false));
                if (optionCode == 'image_src') {
                    input.dependentElm = row.select('input.dynamicoptions-value-title')[0];
                    this.initImageUploader(input, optionCode, config);
                }
                if (optionCode == 'color') {
                    input.dependentElm = row.select('input.dynamicoptions-value-title')[0];
                    jQuery(input).on('change', this.editItemData.bind(this, config, optionCode, input, isNum, false));
                    this.initColorPicker(input, optionCode, config);
                }
                return input;
            }
            return null;
        },
        prepareItemDropdownCol : function(row, config, optionCode, isNum) {
            var elm = row.select('select.dynamicoptions-value-' + optionCode)[0];
            if (elm) {
                //for validation
                elm.name = Math.random();
                elm.value = typeof config[optionCode] != 'undefined' ? config[optionCode] : null;
				if (elm.selectedIndex == -1) elm.selectedIndex = 0;
                Event.observe(elm, 'change', this.editItemData.bind(this, config, optionCode, elm, isNum, false));
                return elm;
            }
            return null;
        },
        prepareItemCheckCol : function(row, config, optionCode, isUnique) {
            var input = row.select('input.dynamicoptions-value-' + optionCode)[0];
            if (input) {
                //for validation
                input.name = Math.random();
                if (config && typeof config[optionCode] != 'undefined') {
                    input.checked = !!(config[optionCode] - 0);
                }
                Event.observe(input, 'change', this.editItemData.bind(this, config, optionCode, input, true, true));
                return input;
            }
            return null;
        },
        removeItemRow : function(config) {
            var lowerItem = null;
            var startOrder = config.order;
            while (lowerItem = this.getItemByOrder(this.tempFieldConfig.items, startOrder++)) {
                lowerItem.order -= 1;
            }
            config.row.remove();
            if (config.temp_id) {
                this.deletedFieldItems.push(config.temp_id);
            }
            this.tempFieldConfig.items = this.tempFieldConfig.items.without(config);
            if (!this.popup.config.select('.dynamicoptions-values-table tbody tr').length) {
                this.popup.config.select('.dynamicoptions-values-table thead tr')[0].hide();
            }
            this.updateItemsOrders();
        },
        moveItemRowUp : function(config) {
            var upperItem = this.getItemByOrder(this.tempFieldConfig.items, config.order - 1);
            if (upperItem) {
                config.order -= 1;
                upperItem.order += 1;
                upperItem.row.insert({before: config.row});
            }
            this.updateItemsOrders();
        },
        moveItemRowDown : function(config) {
            var lowerItem = this.getItemByOrder(this.tempFieldConfig.items, config.order + 1);
            if (lowerItem) {
                config.order += 1;
                lowerItem.order -= 1;
                lowerItem.row.insert({after: config.row});
            }
            this.updateItemsOrders();
        },
        getItemByOrder : function(items, order) {
            for (var i = 0; i < items.length; i++) {
                if (items[i] && items[i].order == order) {
                    return items[i];
                }
            }
            return null;
        },
        updateItemsOrders : function() {
            var itemsByOrder = [];
            var minOrder = 0;
            var maxOrder = 0;
            for (var i = 0; i < this.tempFieldConfig.items.length; i++) {
                if (this.tempFieldConfig.items[i]) {
                    var itemOrder = this.tempFieldConfig.items[i].order;
                    if (!itemsByOrder.length) {
                        minOrder = maxOrder = itemOrder;
                    }
                    itemsByOrder[itemOrder] = this.tempFieldConfig.items[i];
                    if (minOrder > itemOrder) {
                        minOrder = itemOrder;
                    }
                    if (maxOrder < itemOrder) {
                        maxOrder = itemOrder;
                    }
                }
            }

            for (var i = 0; i < itemsByOrder.length; i++) {
                if (itemsByOrder[i]) {
                    if (minOrder == itemsByOrder[i].order) {
                        itemsByOrder[i].row.select('.sort-arrow-up')[0].hide();
                    } else {
                        itemsByOrder[i].row.select('.sort-arrow-up')[0].show();
                    }
                    if (maxOrder == itemsByOrder[i].order) {
                        itemsByOrder[i].row.select('.sort-arrow-down')[0].hide();
                    } else {
                        itemsByOrder[i].row.select('.sort-arrow-down')[0].show();
                    }
                    itemsByOrder[i].row.select('.dynamicoptions-value-order')[0].value = itemsByOrder[i].order;
                }
            }
        },
        changeItemCount : function(config) {
            var table = this.popup.config.select('.dynamicoptions-values-table')[0], st = $$('body')[0].scrollTop;
            table.show();
            table.select('thead tr')[0].show();
            var rowsExist = table.select('tbody tr').length - 1;
            var newTotalRows = rowsExist + 1;
            var item = this.createItemRow({order : newTotalRows, price_type: 'fixed', is_selected: 0, is_disabled: 0}, null);
            $$('body')[0].scrollTop = st;
            table.select('tbody')[0].appendChild(item);
            this.updateItemsOrders();
        },
        editItemData : function(config, key, valueElm, isNum, isCheck) {
            var value = isCheck ? (valueElm.checked ? 1 : 0) : valueElm.value;
            if (isNum) {
                value = parseNumber(value);
            }
            if (key == 'is_selected' && this.isOneCheckField() && value) {
                this.uncheckItems(valueElm);
            }
            /*if (key == 'order') {
             var item = this.tempFieldConfig.items[itemOrder];
             delete this.tempFieldConfig.items[itemOrder];
             this.tempFieldConfig.items[value] = item;
             itemOrder = value;
             }*/
            config[key] = value;
        },
        isOneCheckField : function() {
            if (this.tempFieldConfig.type == 'radio'
                || this.tempFieldConfig.type == 'drop_down'
            ) {
                return true;
            }
            return false;
        },
        uncheckItems : function(allowElm) {
            for (var i = 0; i < this.tempFieldConfig.items.length; i++) {
                if (typeof this.tempFieldConfig.items[i] != 'undefined') {
                    this.tempFieldConfig.items[i]['is_selected'] = 0;
                }
            }
            var elms = this.popup.config.select('.dynamicoptions-value-is_selected');
            for (var i = 0; i < elms.length; i++) {
                if (elms[i].checked && elms[i] != allowElm) {
                    elms[i].checked = false;
                }
            }
        },
        hasCheckedItems : function() {
            var elms = this.popup.config.select('.dynamicoptions-value-is_selected');
            var counts = 0;
            for (var i = 0; i < elms.length; i++) {
                if (elms[i].checked) {
                    counts++;
                    if (counts > 1) {
                        return true;
                    }
                }
            }
            return false;
        },
        initColorPicker: function(elm, code, config) {
            jQuery(elm).spectrum({
                flat: false,
                showInput: true,
                allowEmpty:true,
                preferredFormat: "hex",
                showPalette: true,
                palette: [
                    ["#000","#444","#666","#999","#ccc","#eee","#f3f3f3","#fff"],
                    ["#f00","#f90","#ff0","#0f0","#0ff","#00f","#90f","#f0f"],
                    ["#f4cccc","#fce5cd","#fff2cc","#d9ead3","#d0e0e3","#cfe2f3","#d9d2e9","#ead1dc"],
                    ["#ea9999","#f9cb9c","#ffe599","#b6d7a8","#a2c4c9","#9fc5e8","#b4a7d6","#d5a6bd"],
                    ["#e06666","#f6b26b","#ffd966","#93c47d","#76a5af","#6fa8dc","#8e7cc3","#c27ba0"],
                    ["#c00","#e69138","#f1c232","#6aa84f","#45818e","#3d85c6","#674ea7","#a64d79"],
                    ["#900","#b45f06","#bf9000","#38761d","#134f5c","#0b5394","#351c75","#741b47"],
                    ["#600","#783f04","#7f6000","#274e13","#0c343d","#073763","#20124d","#4c1130"]
                ]
            });
        },
        initImageUploader: function(elm, code, config) {
            var iframeName = Math.random() + '_option';
            if (this.isIE7) {
                var iframe = document.createElement('<iframe name="' + iframeName + '"></iframe>');
            } else {
                var iframe = document.createElement('iframe');
                iframe.name = iframeName;
            }
            Element.extend(iframe);
            iframe.setStyle({'width':'0','height':'0','border':'0px solid #fff'});
            elm.up().appendChild(iframe);
            if (!Prototype.Browser.IE) {
                var form = this.createElement('form');
                form.enctype = 'multipart/form-data';
                form.action = encodeURI(this.config.upload_image_url);
                form.method = 'post';
                form.target = iframeName;
            } else {
                if (this.isIE7) {
                    var form = document.createElement('<form enctype="multipart/form-data" action="'+ encodeURI(this.config.upload_image_url) + '" method="post" target="'+iframeName+'">');
                } else {
                    var form = document.createElement('form');
                    form.setAttribute("enctype", "multipart/form-data");
                    form.setAttribute("action", encodeURI(this.config.upload_image_url));
                    form.setAttribute("method", "post");
                    form.setAttribute("target", iframeName);
                }
                Element.extend(form);
            }
            elm.up().appendChild(form);
            form.hide();
            var formKeyField = this.createElement('input');
            formKeyField.name = 'form_key';
            formKeyField.value = FORM_KEY;
            form.appendChild(formKeyField);
            var uploadField = this.createElement('input');
            uploadField.type = 'file';
            uploadField.name = 'image';
            form.appendChild(uploadField);
            var uploadButton = elm.up().select('button')[0];
            Event.observe(uploadButton, 'click', function(){
                uploadField.click();
            });
            Event.observe(uploadField, 'change', function(){
                this.popup.loadingMask.show();
                form.submit();
            }.bind(this));
            Event.observe(iframe, 'load', function(){
                var iframeDocument = iframe.contentDocument ? iframe.contentDocument : iframe.contentWindow.document;
                if (iframeDocument.getElementById('image_src')) {
                    this.createThumbnail(iframeDocument.getElementById('image_src').innerHTML, elm, code, config);
                } else if (iframeDocument.getElementById('error')) {
                    alert(iframeDocument.getElementById('error').innerHTML)
                }
                this.popup.loadingMask.hide();
            }.bind(this));
            if (elm.value) {
                this.createThumbnail(elm.value, elm, code, config);
            }
            if (elm.up().select('.remove-img-icon')[0]) {
                Event.observe(elm.up().select('.remove-img-icon')[0], 'click', function(){
                    elm.up().removeClassName('no-color');
                    elm.value = '';
                    config[code] = null;
                    elm.up().select('img').each(function(imgElm){imgElm.remove();});
                    elm.up().select('.remove-img-icon')[0].hide();
                    elm.up().select('button')[0].update(this.config.translates.upload_image);
                    if (elm.dependentElm) {
                        elm.dependentElm.toggleClassName('required-entry');
                    }
                }.bind(this));
            }
        },
        createThumbnail : function(src, valueElm, code, config) {
            var block = valueElm.up(), baseImg = block.select('.dynamicoptions-value-base_img')[0];
            block.select('img').each(function(elm){elm.remove();});
            block.addClassName('no-color');
            if (baseImg) {
                baseImg.checked = config.base_img ? true : false;
                Event.observe(baseImg, 'click', function(){config.base_img = baseImg.checked});
            }
            var image = this.createElement('img');
            image.src = src;
            image.height = '19';
            image.addClassName('dynamicoptions-image-thumbnail');
            block.insert({top:image});
            valueElm.value = src;
            config[code] = src;            
            block.select('button')[0].update(this.config.translates.reupload);
            Event.observe(image, 'click', this.showFullSizePreview.bind(this, src));
            if (block.select('.remove-img-icon')[0]) {
                block.select('.remove-img-icon')[0].show();
            }
            if (valueElm.dependentElm) {
                valueElm.dependentElm.toggleClassName('required-entry');
            }
        },
        showFullSizePreview : function(src) {
            this.popup.imageMask.show();
            var image = this.createElement('img');
            image.src = src;
            image.addClassName('dynamicoptions-image-full-preview');
            $$('body')[0].appendChild(image);
            var marginLeft = image.getWidth() > document.viewport.getWidth() ? (10 - document.viewport.getWidth() / 2) : (- image.getWidth() / 2);
            var marginTop = image.getHeight() > document.viewport.getHeight() ? (10 - document.viewport.getHeight() / 2) : (- image.getHeight() / 2);
            image.setStyle({
                marginLeft: (marginLeft + document.viewport.getScrollOffsets().left) + 'px',
                marginTop: (marginTop + document.viewport.getScrollOffsets().top) + 'px'
            });
            Event.observe(image, 'click', this.hideFullSizePreviews.bind(this));
        },
        hideFullSizePreviews : function() {
            $$('.dynamicoptions-image-full-preview').each(function(elm){elm.remove();});
            this.popup.imageMask.hide();
        },
        prepareFieldConfig : function(sectionOrder, config) {
            this.tempFieldConfig.section_order = sectionOrder;
            this.removeClassName(this.popup.window, 'select-window');
            if (config) {
                if (typeof config.type == 'undefined') {
                    this.popup.window.select('#itoris_dynamicoptions_type_dropdown')[0].disabled = false;
                    if (this.config.store_id > 0) config.type = 'html'; else config.type = 'field';
                    var obj = this;
                }
                if (this.config.store_id > 0) {
                    var isEditDisallowed = "image,html".indexOf(config.type) == -1;
                    this.popup.window.select('#itoris_dynamicoptions_type_dropdown')[0].disabled = "image,html".indexOf(config.type) == -1;
                    this.popup.window.select('#itoris_dynamicoptions_type_dropdown option').each(function(option){ option.disabled = "image,html".indexOf(option.value) == -1; });
                }
                $('itoris_dynamicoptions_type_dropdown').value = config.type;
                if (config.type != 'image' && config.type != 'html' && config.type != 'file') {
                    if (!config.internal_id) {
                        config.internal_id = this.getInternalFieldId(false);
                    } else {
                        this.usedFieldInternalIds.push(parseInt(config.internal_id));
                    }
                }
                /*if (config.type == 'html' || config.type == 'image' || config.type == 'file') {
                 this.popup.addClassName('')
                 } else */
                if (this.isSelectType(config.type)) {
                    if (!this.popup.window.hasClassName('wide'))
                        this.popup.window.addClassName('wide')
                } else {
                    if (this.popup.window.hasClassName('wide'))
                        this.popup.window.removeClassName('wide')
                }

                switch (config.type) {
                    case 'field':
                        this.prepareDefaultOptions(config);
                        break;
                    case 'area':
                        this.prepareDefaultOptions(config, ['validation']);
                        break;
                    case 'date':
                    case 'date_time':
                    case 'time':
                        this.prepareDefaultOptions(config, ['validation', 'max_characters']);
                        break;
                    case 'file':
                        this.createFileOptions(config);
                        break;
                    case 'drop_down':
                    case 'multiple':
                        this.createSelectBoxOptions(config);
                        break;
                    case 'radio':
                    case 'checkbox':
                        this.createRadioOptions(config);
                        break;
                    case 'image':
                        this.createImageOptions(config);
                        break;
                    case 'html':
                        this.createStaticTextOptions(config);
                        break;
                }
                if (this.popup.config.select('.dynamicoptions-internal_id')[0]) {
                    this.popup.config.select('.dynamicoptions-internal_id')[0].update('F' + config.internal_id);
                }
                this.updateFieldCopyOptions(config.internal_id);
            } else {
                $('itoris_dynamicoptions_type_dropdown').value = 'field';
                //this.addClassName(this.popup.window, 'wide');
                this.prepareDefaultOptions({hide_on_focus: 1});
                this.tempFieldConfig.type = 'field';
                this.popup.config.select('.dynamicoptions-internal_id')[0].update('F' + this.getInternalFieldId(true));
            }
            var width = jQuery(window).width();
            this.popup.window.setStyle({left: (width/6) + 'px'});
        },
        prepareDefaultOptions : function(config, withoutFields) {
            var rows = this.popup.config.select('tbody tr');
            for (var i = 0; i < rows.length; i++) {
                if (!rows[i].visible()) {
                    //	rows[i].remove();
                }
                if (config.type == 'date' || config.type == 'date_time' || config.type == 'time') {
                    if (!rows[i].hasClassName('tr-dynamicoptions-all') && rows[i].select('td')[0]) {
                        if (rows[i].select('td')[0].hasClassName('td-dynamicoptions-default_value')) {
                            rows[i].remove();
                        }
                    }
                }
            }
            if (withoutFields) {
                for (var i = 0; i < withoutFields.length; i++) {
                    this.removeFieldCol(withoutFields[i]);
                }
            }
            this.prepareInputField('title', config, true);
            this.prepareInputField('price', config, false);
            this.prepareInputField('sku', config, false);
            this.prepareInputField('max_characters', config, false);
            this.prepareInputField('default_value', config, false);
            this.prepareInputField('comment', config, false);
            this.prepareInputField('css_class', config, false);
            this.prepareInputField('html_args', config, false);
            this.prepareInputField('weight', config, false);
            this.prepareSelectField('is_require', config, false);
            this.prepareSelectField('price_type', config, false);
            this.prepareSelectField('validation', config, false);
            if (this.popup.config.select('.dynamicoptions-hide_on_focus')[0]) {
                this.popup.config.select('.dynamicoptions-hide_on_focus')[0].checked = parseInt(config.hide_on_focus);
            }
            var visibilityElm = this.popup.config.select('.dynamicoptions-visibility')[0];
            if (visibilityElm) {
                var visibilityActionElm = this.popup.config.select('.dynamicoptions-visibility_action')[0];
                Event.observe(visibilityElm, 'change', this.changeOptionVisibility.bind(this, visibilityElm, visibilityActionElm, null));
            }
            this.prepareSelectField('visibility', config, false);
            this.prepareSelectField('visibility_action', config, false);
            if (visibilityElm) {
                this.changeOptionVisibility(visibilityElm, visibilityActionElm, null);
            }
            var conditionIcon = this.popup.config.select('.edit-field-condition')[0];
            if (conditionIcon) {
                this.prepareConditionIcon(conditionIcon, this.tempFieldConfig, this.popup.config.select('.dynamicoptions-visibility_condition')[0]);
            }
            var customerGroupsSelect = this.popup.config.select('.td-dynamicoptions-customer_group .action-link')[0];
            if (customerGroupsSelect) {
                var textElm = this.popup.config.select('.text-dynamicoptions-customer_group')[0];
                Event.observe(customerGroupsSelect, 'click', this.showCustomerGroupsPopup.bind(this, customerGroupsSelect, config, textElm, false));
                this.showCustomerGroupsPopup(customerGroupsSelect, config, textElm, true);
                this.applyCustomerGroupsPopup(config);
            }
        },
        removeFieldCol : function(code) {
            var cols = this.popup.config.select('.td-dynamicoptions-' + code);
            for (var i = 0; i < cols.length; i++) {
                var row = cols[i].up('tr');
                cols[i].update();
                row.insert({bottom: cols[i]});
            }
        },
        changeOptionVisibility : function(visibilityElm, visibilityActionElm, itemConfig) {
            var hiddenValues = [];
            switch (visibilityElm.value) {
                case 'visible':
                    hiddenValues = ['visible'];
                    if (visibilityActionElm.value == 'visible') {
                        visibilityActionElm.value = 'hidden';
                    }
                    break;
                case 'hidden':
                case 'disabled':
                    hiddenValues = ['hidden', 'disabled'];
                    if (visibilityActionElm.value != 'visible') {
                        visibilityActionElm.value = 'visible';
                    }
                    break;
            }
            var options = visibilityActionElm.select('option');
            options.each(function(option){
                if (hiddenValues.indexOf(option.value) == -1) {
                    option.show();
                } else {
                    option.hide();
                }
            });
            if (itemConfig) {
                this.editItemData(itemConfig, 'visibility_action', visibilityActionElm, false, false)
                this.editItemData(itemConfig, 'visibility', visibilityElm, false, false)
            } else {
                this.editFieldData('visibility_action', visibilityActionElm, false);
                this.editFieldData('visibility', visibilityElm, false);
            }
        },
        saveCondition : function(config, conditionObj, updateElm) {
            if (updateElm) {
                updateElm.value = conditionObj.getConditionsStr();
                if (updateElm.hasClassName('condition-error')) {
                    updateElm.removeClassName('condition-error');
                    updateElm.removeClassName('mage-error');
                }
            }
            config.visibility_condition = this.objectToJson(conditionObj.conditions);
            conditionObj.closePopup();
        },
        updateConditions : function(config, elm, conditionObj) {
            var conditions = conditionObj.convertStrToConditionObj(elm.value)
            if (conditions) {
                this.block.appendChild(conditionObj.applyButton);
                this.block.appendChild(conditionObj.cancelButton);
                config.visibility_condition = this.objectToJson(conditions);
                conditionObj.conditions = conditions;
                conditionObj.initConditions();
                conditionObj.popup.appendChild(conditionObj.applyButton);
                conditionObj.popup.appendChild(conditionObj.cancelButton);
                if (elm.hasClassName('condition-error')) {
                    elm.removeClassName('condition-error');
                    elm.removeClassName('mage-error');
                    if (elm.up().select('.validation-advice').length) {
                        elm.up().select('.validation-advice')[0].remove();
                    }
                }
            } else {
                if (!elm.hasClassName('condition-error')) {
                    elm.addClassName('condition-error');
                    elm.addClassName('mage-error');
                }
            }
        },
        createRadioOptions : function(config) {
            this.removeOptionsRows('values');
            this.prepareDefaultOptions(config);
            this.createItemsOptions(config);
        },
        createSelectBoxOptions : function(config) {
            this.removeOptionsRows('values');
            this.popup.config.select('.dynamicoptions-values-table th.only-radio, .dynamicoptions-values-table td.only-radio').each(function(elm){elm.remove();});
            if (config.type == 'multiple') {
                this.popup.config.select('.dynamicoptions-values-table th.not-multiple-dropdown, .dynamicoptions-values-table td.not-multiple-dropdown').each(function(elm){elm.remove();});
            }
            this.prepareDefaultOptions(config);
            this.createItemsOptions(config);
        },
        createFileOptions : function(config) {
            this.prepareDefaultOptions(config, ['validation', 'max_characters', 'default_value', 'internal_id']);
            var rows = this.popup.config.select('.tr-dynamicoption-file');
            rows.each(function(elm){elm.show();});
            this.prepareInputField('file_extension', config, false);
            if (!parseInt(config.image_size_x)) {
                config.image_size_x = '';
            }
            this.prepareInputField('image_size_x', config, false);
            if (!parseInt(config.image_size_y)) {
                config.image_size_y = '';
            }
            this.prepareInputField('image_size_y', config, false);
        },
        createStaticTextOptions : function(config) {
            this.removeOptionsRows('html');
            this.popup.config.select('.dynamicoptions-visibility option[value=disabled], .dynamicoptions-visibility_action option[value=disabled]').each(function(elm){elm.remove();});
            this.prepareDefaultOptions(config, ['internal_id']);
            this.prepareInputField('static_text', config, true);
        },
        createImageOptions : function(config) {
            this.removeOptionsRows('image');
            this.popup.config.select('.dynamicoptions-visibility option[value=disabled], .dynamicoptions-visibility_action option[value=disabled]').each(function(elm){elm.remove();});
            this.prepareDefaultOptions(config, ['internal_id']);
            this.prepareInputField('img_src', config, true);
            this.prepareInputField('img_alt', config, false);
            this.prepareInputField('img_title', config, false);
        },
        removeOptionsRows : function(expect) {
            var rows = this.popup.config.select('tbody tr');
            for (var i = 0; i < rows.length; i++) {
                if (rows[i].hasClassName('tr-dynamicoptions-all') || rows[i].hasClassName('tr-dynamicoptions-' + expect)) {
                    rows[i].show();
                } else {
                    if (!rows[i].up('.dynamicoptions-values-table')) {
                        rows[i].remove();
                    }
                }
            }
        },
        initCustomerGroupsPopup : function() {
            var popup = this.getCustomerGroupsPopup();
            Event.observe(popup.select('button.save')[0], 'click', this.applyCustomerGroupsPopup.bind(this));
            Event.observe(popup.select('button.delete')[0], 'click', this.closeCustomerGroupsPopup.bind(this));
        },
        showCustomerGroupsPopup : function(elm, config, textElm, thisnotclick) {
            this.closeAdditionalPopups();
            var popup = this.getCustomerGroupsPopup();
            var offset = elm.cumulativeOffset();
            var isOpera = !!window.opera || navigator.userAgent.indexOf(' OPR/') >= 0;
            var isChrome = !!window.chrome && !isOpera;
            if (isChrome && thisnotclick) {

            } else {
                popup.setStyle({
                    top: offset.top + 'px',
                    left: offset.left + 'px'
                });
            }
            popup.show();
            if (typeof config.customer_group == 'undefined' || !config.customer_group) {
                config.customer_group = '';
            }
            var groups = config.customer_group.split(',');
            var options = popup.select('option');
            for (var i = 0; i < options.length; i++) {
                options[i].selected = false;
            }

            if (groups.length) {
                for (var i = 0; i < options.length; i++) {
                    if (groups.indexOf(options[i].value) != -1) {
                        options[i].selected = true;
                    }
                }
            } else {
                options[0].selected = true;
            }
            this.currenEditCustomerGroupConfig = config;
            this.currentTextCustomerGroup = textElm;
        },
        applyCustomerGroupsPopup : function() {
            var popup = this.getCustomerGroupsPopup();
            var options = popup.select('option');
            var config = this.currenEditCustomerGroupConfig;
            if (options[0].selected) {
                config.customer_group = '';
            } else {
                var customerGroups = [];
                for (var i = 1; i < options.length; i++) {
                    if (options[i].selected) {
                        customerGroups.push(options[i].value);
                    }
                }
                config.customer_group = customerGroups.join(',');
            }
            if (this.currentTextCustomerGroup) {
                this.currentTextCustomerGroup.update(this.prepareCustomerGroupsStr(config.customer_group.split(',')));
            }
            this.closeCustomerGroupsPopup();
        },
        prepareCustomerGroupsStr : function(groups) {
            var strParts = [];
            var options = this.getCustomerGroupsPopup().select('option');
            for (var i = 0; i < options.length; i++) {
                if (groups.indexOf(options[i].value) != -1) {
                    strParts.push(options[i].innerHTML);
                }
            }
            return strParts.join(', ');
        },
        closeCustomerGroupsPopup : function() {
            this.getCustomerGroupsPopup().hide();
        },
        getCustomerGroupsPopup : function() {
            return $('itoris-dynamicproductoptions-customergroups-popup');
        },

        /** fields **/
        createField : function(field, sectionOrder, ceil) {
            var fieldBlock = null;
            switch (field.type) {
                case 'field':
                    fieldBlock = this.createInputBox(field);
                    break;
                case 'area':
                    fieldBlock = this.createTextareaBox(field);
                    break;
                case 'file':
                    fieldBlock = this.createFileBox(field);
                    break;
                case 'drop_down':
                    fieldBlock = this.createSelectBox(field);
                    break;
                case 'radio':
                    fieldBlock = this.createRadioBox(field);
                    break;
                case 'checkbox':
                    fieldBlock = this.createCheckbox(field);
                    break;
                case 'multiple':
                    fieldBlock = this.createMultiSelectBox(field);
                    break;
                case 'date':
                    fieldBlock = this.createDateBox(field);
                    break;
                case 'date_time':
                    fieldBlock = this.createDateBox(field);
                    break;
                case 'time':
                    fieldBlock = this.createDateBox(field);
                    break;
                case 'image':
                    fieldBlock = this.createImageBox(field);
                    break;
                case 'html':
                    fieldBlock = this.createStaticTextBox(field);
                    break;

                default: fieldBlock = this.createElement('div');
            }
            this.addFieldCommentToRow(fieldBlock, field);
            fieldBlock.addClassName('field-container');
            if (field) {
                fieldBlock.id = 'section_' + sectionOrder + '_field' + field.order;
                Event.observe(fieldBlock, 'mousedown', this.registerActiveField.bind(this, sectionOrder, field.order, field, fieldBlock, ceil));
                if (!field.temp_id) {
                    field.temp_id = ++this.fieldsCount;
                }
                if (field.items) {
                    for (var i = 0; i < field.items.length; i++) {
                        if (field.items[i] && !field.items[i].temp_id) {
                            field.items[i].temp_id = ++this.fieldsItemsCount;
                        }
                    }
                }
                field.section_order = sectionOrder;
                //if (field.type != 'image' && field.type != 'html'/* && field.type != 'file'*/) {
                if (!field.internal_id) {
                    field.internal_id = this.getInternalFieldId(false);
                } else {
                    this.usedFieldInternalIds.push(parseInt(field.internal_id));
                }
                //}
                var internalIdBlock = this.createElement('div', 'dynamicoptions-internal-id-box');
                internalIdBlock.appendChild(this.createElement('span').update('ID: <span class="internal-id">F'+ field.internal_id +'</span><br/>'));
                if (!field.visibility) {
                    field.visibility = 'visible';
                }
                internalIdBlock.appendChild(this.createElement('span', 'visibility-' + field.visibility).update(field.visibility));
                fieldBlock.appendChild(internalIdBlock);
                this.updateFieldConfigFields(field);
            }

            return fieldBlock;
        },
        createInputBox : function(field) {
            var fieldContainer = this.createFieldContainer(field);
            var input = this.createElement('input');
            input.type = 'text';
            input.readonly = true;
            input.value = field.default_value || '';
            fieldContainer.appendChild(input);
            return fieldContainer;
        },
        createCheckbox : function(field) {
            var checkboxes = this.createFieldContainer(field);
            if (field.items) {
                field.items = this.orderItems(field.items);
                for (var i = 0; i < field.items.length; i++) {
                    if (field.items[i]) {
                        this.createCheckboxRow(field.items[i], checkboxes, 'checkbox');
                    }
                }
            }
            return checkboxes;
        },
        createRadioBox : function(field) {
            var checkboxes = this.createFieldContainer(field);
            var itemElm = null;
            if (field.items) {
                field.items = this.orderItems(field.items);
                for (var i = 0; i < field.items.length; i++) {
                    if (field.items[i]) {
                        itemElm = this.createCheckboxRow(field.items[i], checkboxes, 'radio');
                        itemElm.name = field.name;
                    }
                }
            }
            return checkboxes;
        },
        createSelectBox : function(field) {
            var fieldContainer = this.createFieldContainer(field);
            if (field.items) {
                field.items = this.orderItems(field.items);
                var selectContainer = this.createElement('select');
                fieldContainer.appendChild(selectContainer);
                var selectOption = this.createElement('option');
                selectContainer.appendChild(selectOption);
                selectOption.update(field.default_select_title || this.config.translates.please_select);
                for (var i = 0; i < field.items.length; i++) {
                    if (field.items[i]) {
                        selectOption = this.createElement('option');
                        selectContainer.appendChild(selectOption);
                        selectOption.update(field.items[i].title);
                        if (parseInt(field.items[i].is_selected)) {
                            selectOption.selected = true;
                        }
                    }
                }
            }
            return fieldContainer;
        },
        createMultiSelectBox : function(field) {
            var fieldContainer = this.createFieldContainer(field);
            if (field.items) {
                field.items = this.orderItems(field.items);
                var selectContainer = this.createElement('select');
                selectContainer.size = field.size;
                selectContainer.multiple = true;
                fieldContainer.appendChild(selectContainer);
                var selectOption = null;
                for (var i = 0; i < field.items.length; i++) {
                    if (field.items[i]) {
                        selectOption = this.createElement('option');
                        selectContainer.appendChild(selectOption);
                        selectOption.update(field.items[i].title);
                        if (parseInt(field.items[i].is_selected)) {
                            selectOption.selected = true;
                        }
                    }
                }
            }
            return fieldContainer;
        },
        createTextareaBox : function(field) {
            var fieldContainer = this.createFieldContainer(field);
            var textarea = this.createElement('textarea');
            textarea.readonly = true;
            //textarea.rows = field.rows;
            //if (field.cols) {
            //	textarea.cols = field.cols;
            //}
            textarea.update(field.default_value || '');
            fieldContainer.appendChild(textarea);
            return fieldContainer;
        },
        createFileBox : function(field) {
            var fieldContainer = this.createFieldContainer(field);
            var input = this.createElement('input');
            input.type = 'file';
            fieldContainer.appendChild(input);
            var fieldComment = this.createElement('span', 'comment');
            var note = '';
            if (field.file_extension) {
                note += '<br/>' + this.translates.file_extension + ': ' + field.file_extension;
            }
            if (field.max_file_size) {
                note += '<br/>' + this.translates.max_file_size + ': ' + field.max_file_size;
            }
            fieldComment.update(note);
            fieldContainer.appendChild(fieldComment);
            return fieldContainer;
        },
        createStaticTextBox : function(field) {
            var fieldContainer = this.createFieldContainer(field);
            fieldContainer.update(field.static_text);
            return fieldContainer;
        },
        createImageBox : function(field) {
            var fieldContainer = this.createFieldContainer(field);
            var img = document.createElement('img');
            img.src = field.img_src;
            img.alt = field.img_alt;
            img.title = field.img_title;
            img.height = 30;
            fieldContainer.appendChild(img);
            return fieldContainer;
        },
        createDateBox : function(field) {
            var fieldContainer = this.createFieldContainer(field);
            var input = this.createElement('input');
            input.type = 'text';
            input.readonly = true;
            //input.value = field.default_value || '';
            fieldContainer.appendChild(input);
            var img = this.createElement('img');
            img.src = this.calendarUrl;
            fieldContainer.appendChild(img);
            return fieldContainer;
        },
        createFieldContainer : function(field) {
            var container = this.createElement('div');
            if (field.title) {
                var label = this.createElement('span');
                label.update(field.title + ':');
                container.appendChild(label);
                if (parseInt(field.is_require) || parseInt(field.min_required)) {
                    var required = this.createElement('span', 'required');
                    required.update('*');
                    container.appendChild(required);
                }
                container.appendChild(document.createElement('br'));
            }
            return container;
        },
        createCheckboxRow : function(option, parentBlock, type) {
            var checkbox = this.createElement('input');
            checkbox.type = type;
            if (parseInt(option.is_selected)) {
                checkbox.checked = true;
            }
            parentBlock.appendChild(checkbox);
            if (option.image_src) {
                var img = this.createElement('img');
                img.src = option.image_src;
                img.width = 30;
                parentBlock.appendChild(img);
            }
            var label = this.createElement('span');
            label.update(option.title);
            parentBlock.appendChild(label);
            label.setStyle({paddingRight: '5px'});
            if (parseInt(option.carriage_return)) {
                parentBlock.appendChild(this.createElement('br'));
            }
            return checkbox;
        },
        addFieldCommentToRow : function(container, config) {
            if (config.comment) {
                var comment = this.createElement('span', 'comment');
                comment.update('<br/>' + config.comment);
                container.appendChild(comment);
            }
        },
        orderItems : function(items) {
            var outputItems = [];
            for (var i = 0; i < items.length; i++) {
                if (items[i]) {
                    outputItems[items[i].order] = items[i];
                }
            }
            return outputItems;
        },
        orderItemsForTable : function(items, oldCols, newCols) {
            var outputItems = [];
            var correction = newCols - oldCols;
            for (var i = 0; i < items.length; i++) {
                if (items[i]) {
                    if (correction && /*items[i].order >= correction &&*/ Math.ceil(items[i].order / oldCols) > 1) {
                        items[i].order += (correction * Math.ceil(items[i].order / oldCols) - correction);
                    }
                    outputItems[items[i].order] = items[i];
                }
            }
            return outputItems;
        },
        /**
         * Create popup for editing of a field
         */
        createPopup : function() {
            var background = this.createElement('div', 'popup-background');
            Event.observe(background, 'click', this.hidePopup.bind(this));
            var popupWindow = this.createElement('div', 'popup-window');
            this.block.appendChild(background);
            this.block.appendChild(popupWindow);
            var configBox = this.createElement('div', 'config-box');
            popupWindow.appendChild(configBox);
            var popupLabel = this.createElement('div', 'label');
            popupLabel.update(this.translates.optionConfig);
            popupLabel.insert({top: this.createFieldCopySelect()});
            popupLabel.insert({top: this.createFieldTypesSelect()});
            configBox.appendChild(popupLabel);
            var fieldConfig = this.createElement('div', 'config');
            configBox.appendChild(fieldConfig);
            var buttons = this.createElement('div', 'buttons');
            configBox.appendChild(buttons);
            var popupLoadingMask = this.createElement('div', 'popup-loading-mask');
            popupWindow.appendChild(popupLoadingMask);
            popupLoadingMask.hide();
            var imageMask = this.createElement('div', 'dynamicoptions-image-popup-background');
            Event.observe(imageMask, 'click', this.hideFullSizePreviews.bind(this));
            $$('body')[0].appendChild(imageMask);
            imageMask.setStyle({zIndex: 1020});
            imageMask.hide();
            this.popup = {
                background  : background,
                window      : popupWindow,
                config      : fieldConfig,
                buttons     : buttons,
                loadingMask : popupLoadingMask,
                imageMask   : imageMask
            };
            this.hidePopup();
        },
        hidePopup : function(clearConfig) {
            this.tempFieldConfig = {};
            this.popup.config.update();
            this.popup.background.hide();
            this.popup.window.hide();
            this.doNotMoveField = false;
            this.closeAdditionalPopups();
            this.hideProductsGridPopup();
        },
        closeAdditionalPopups : function() {
            this.conditionsObj.each(function(obj) {if(obj) obj.closePopup();});
            this.closeCustomerGroupsPopup();
        },
        showPopup : function() {
            this.popup.background.show();
            this.popup.window.show();
            var windowHeight = jQuery(document).height();
            var top = ((windowHeight/100)+300) + document.viewport.getScrollOffsets().top;
            this.popup.window.setStyle({'top' : top + 'px'});

        },
        createElement : function(elm, className) {
            var elm = document.createElement(elm);
            Element.extend(elm);
            if (className) {
                elm.addClassName(className);
            }
            return elm;
        },
        addClassName : function(elm, className) {
            if (!elm.hasClassName(className)) {
                elm.addClassName(className);
            }
            return elm;
        },
        removeClassName : function(elm, className) {
            if (elm.hasClassName(className)) {
                elm.removeClassName(className);
            }
            return elm;
        },
        getElementById : function(id) {
            return $('itoris-dynamicoptions-' + id);
        },

        registerActiveField : function(sectionOrder, fieldOrder, fieldConfig, field, ceil, e) {
            if (this.doNotMoveField) {
                return;
            }
            this.activeSectionOrder = sectionOrder;
            this.activeFieldOrder = fieldOrder;
            this.activeCeil = ceil;
            this.tempFieldConfigMove = fieldConfig;
            this.activeField = field;
            var width = field.getWidth() + 'px';
            var height = field.getHeight() + 'px';
            this.activeField.absolutize();
            this.activeFieldOffset = this.activeField.positionedOffset();
            this.activeFieldTop = e.pageY - this.activeFieldOffset.top;
            this.activeFieldLeft = e.pageX - this.activeFieldOffset.left;
            this.activeField.setStyle({
                'zIndex' :1000,
                'backgroundColor' :'#d6d6d6',
                'width'  : width,
                'height' : height
            });
            this.calculateEmtyCeilOffset();
        },
        moveActiveField : function(e) {
            if (this.activeField) {
                this.activeField.setStyle({left: (e.pageX - this.activeFieldLeft) + 'px', top: (e.pageY - this.activeFieldTop) + 'px'});
                this.highlightTargetCeil(e);
            }
        },
        highlightTargetCeil : function(e) {
            this.clearCeilsHighlight();
            var targetCeil = null;
            for (var i = 0; i < this.emptyCeils.length; i++) {
                if (e.pageX > this.emptyCeils[i].x
                    && e.pageX < this.emptyCeils[i].x + this.emptyCeils[i].width
                    && e.pageY > this.emptyCeils[i].y && e.pageY < this.emptyCeils[i].y + this.emptyCeils[i].height
                ) {
                    targetCeil = this.emptyCeils[i];
                    break;
                }
            }
            if (targetCeil) {
                targetCeil.ceil.addClassName('highlight-target-ceil');
            }
        },
        clearCeilsHighlight: function() {
            this.emptyCeils.each(function(item){
                if (item.ceil.hasClassName('highlight-target-ceil')) {
                    item.ceil.removeClassName('highlight-target-ceil');
                }
            });
        },
        unregisterActiveField : function(e) {
            if (this.activeField) {
                this.clearCeilsHighlight();
                var targetCeil = null;
                for (var i = 0; i < this.emptyCeils.length; i++) {
                    if (e.pageX > this.emptyCeils[i].x && e.pageX < this.emptyCeils[i].x + this.emptyCeils[i].width
                        && e.pageY > this.emptyCeils[i].y && e.pageY < this.emptyCeils[i].y + this.emptyCeils[i].height
                    ) {
                        targetCeil = this.emptyCeils[i];
                        break;
                    }
                }
                var returnActiveField = true;
                if (targetCeil) {
                    if (targetCeil.is_empty) {
                        returnActiveField = false;
                        delete this.sections[this.activeSectionOrder]['fields'][this.activeFieldOrder];
                        this.tempFieldConfigMove.order = targetCeil.formOrder;
                        this.sections[targetCeil.section_order]['fields'][targetCeil.formOrder] = this.tempFieldConfigMove;
                        //				if (!this.tempFieldConfigMove.removable) {
                        //					this.sections[targetCeil.section_order].removable = false;
                        //				}
                        this.sectionsBlocks[targetCeil.section_order].remove();
                        this.addSection(this.sections[targetCeil.section_order], true);
                        this.sectionsBlocks[this.activeSectionOrder].remove();
                        this.addSection(this.sections[this.activeSectionOrder], true);
                        this.activeField.remove();
                    } else if (targetCeil.section_order != this.tempFieldConfigMove.section_order || targetCeil.formOrder != this.tempFieldConfigMove.order) {
                        returnActiveField = false;
                        var section = this.sections[targetCeil.section_order];
                        var cols = section.cols;
                        var rows = section.rows;
                        var replaceFields = [];
                        var maxOrder = 0;
                        var deleteActiveField = true;
                        for (var i = targetCeil.formOrder; i <= cols * rows; i += cols) {
                            if (typeof section['fields'][i] == 'undefined'
                                || (this.tempFieldConfigMove.section_order == section['fields'][i].section_order
                                && this.tempFieldConfigMove.order == section['fields'][i].order
                                )
                            ) {
                                if (typeof section['fields'][i] != 'undefined') {
                                    deleteActiveField = false;
                                }
                                maxOrder = i;
                                break;
                            }
                        }
                        if (!maxOrder) {
                            this.changeSectionTable(targetCeil.section_order, 'rows', {value: rows + 1});
                            maxOrder = i;
                        }
                        for (var j = maxOrder; j >= targetCeil.formOrder; j -= cols) {
                            if (j == targetCeil.formOrder) {
                                if (deleteActiveField) {
                                    delete this.sections[this.activeSectionOrder]['fields'][this.activeFieldOrder];
                                }
                                this.tempFieldConfigMove.order = targetCeil.formOrder;
                                section['fields'][targetCeil.formOrder] = this.tempFieldConfigMove;
                                this.sectionsBlocks[targetCeil.section_order].remove();
                                this.addSection(section, true);
                                this.sectionsBlocks[this.activeSectionOrder].remove();
                                this.addSection(this.sections[this.activeSectionOrder], true);
                                this.activeField.remove();
                            } else {
                                delete section['fields'][j];
                                var tempField = section['fields'][j - cols];
                                delete section['fields'][j - cols];
                                tempField.order = j;
                                section['fields'][j] = tempField;
                                //	this.sectionsBlocks[targetCeil.section_order].remove();
                                //	this.addSection(section, true);
                            }
                        }

                    }
                }
                if (returnActiveField) {
                    new Effect.Morph(this.activeField, {
                        style: 'top:'+ this.activeFieldOffset.top +'px; left: '+ this.activeFieldOffset.left +'px;',
                        duration: 0.8
                    });
                    var ceil = this.activeCeil;
                    var field = this.activeField;
                    setTimeout(function() {
                        ceil.appendChild(field);
                        field.writeAttribute('style','');
                    }, 900);
                }
                this.activeSectionOrder = null;
                this.activeFieldOrder = null;
                this.activeField = null;
                this.activeCeil = null;
                this.tempFieldConfigMove = null;
            }
        },
        calculateEmtyCeilOffset : function() {
            var ceils = this.block.select('td.field-ceil');
            this.emptyCeils = [];
            for (var i = 0; i < ceils.length; i++) {
                if (ceils[i].hasClassName('readonly-cell')) continue;
                this.emptyCeils.push({
                    x             : ceils[i].cumulativeOffset().left,//ceils[i].positionedOffset().left,
                    y             : ceils[i].cumulativeOffset().top,//ceils[i].positionedOffset().top,
                    width         : ceils[i].getWidth(),
                    height        : ceils[i].getHeight(),
                    section_order : ceils[i].section,
                    formOrder     : ceils[i].form,
                    ceil          : ceils[i],
                    is_empty      : ceils[i].hasClassName('empty')
                });
                ceils[i].writeAttribute('style','');
            }
        },
        getInternalFieldId : function(withoutSaving) {
            var internalId = this.fieldInternalId;
            while (this.usedFieldInternalIds.indexOf(internalId) != -1) {
                internalId++;
            }
            if (!withoutSaving) {
                this.usedFieldInternalIds.push(internalId);
                this.fieldInternalId = internalId
            }

            return internalId;
        },
        objectToJson: function(obj) {
            if (this.replaceJsonUndefined) {
                this.replaceSectionUndefinedValues(obj);
            }
            try {
                return Object.toJSON(obj);
            } catch (e) {
                return JSON.stringify(obj, function(key, value) {
                    if (typeof value === 'function') {
                        return;
                    }
                    if (typeof value === 'object' && value !== null) {
                        if (typeof value.tagName != 'undefined') {
                            return;
                        }
                    }
                    return value;
                });
            }

        },
        replaceSectionUndefinedValues: function(obj) {
            if (obj instanceof Array) {
                for (var i = 0; i < obj.length; i++) {
                    switch (typeof obj[i]) {
                        case 'undefined':
                            obj[i] = null;
                            break;
                        case 'object':
                            this.replaceSectionUndefinedValues(obj[i]);
                    }
                }
            } else if (obj && obj.items) {
                this.replaceSectionUndefinedValues(obj.items);
            }
        },
        showProductsGridPopup: function(row, config) {
            $('itoris_dynamicoptions_products_grid_popup').show();
            new Ajax.Request(this.config.product_grid_url, {
                onComplete: function(res) {
                    $$('#itoris_dynamicoptions_products_grid_popup .product-grid-popup-content')[0].update(res.responseText);
                    this.productGridItemRow = row;
                    this.productGridItemConfig = config;

                }.bind(this)
            });
        },
        hideProductsGridPopup: function() {
            $('itoris_dynamicoptions_products_grid_popup').hide();
        },
        showTierPopup: function(row, config) {
            window.dpo_tier_price_popup = window.dpo_tier_price_popup ? window.dpo_tier_price_popup : $('itoris_dynamicoptions_tier_popup');
            var tbody = window.dpo_tier_price_popup.select('.tier-popup-table tbody')[0];
            tbody.config = config;
            if (!tbody.rowProto) tbody.rowProto = tbody.select('tr')[0].cloneNode(true);
            row.select('.dynamicoptions-tier-popup-container')[0].appendChild(window.dpo_tier_price_popup);
            window.dpo_tier_price_popup.select('.tier-popup-table tbody tr').each(function(tr){tr.remove();});
            if (config.tier_price) {
                var tierPrice = config.tier_price.evalJSON();
                for(var i=0; i<tierPrice.length; i++) {
                    tbody.appendChild(tbody.rowProto.cloneNode(true));
                    var tr = tbody.select('tr')[i];
                    tr.select('.dpo_tier_qty')[0].value = tierPrice[i].qty;
                    tr.select('.dpo_tier_price')[0].value = parseFloat(tierPrice[i].price).toFixed(2);
                    tr.select('.dpo_tier_price_type')[0].selectedIndex = tierPrice[i].price_type == 'fixed' ? 0 : 1;
                }
            }
            window.dpo_tier_price_popup.show();
        },
        removeTierRow: function(link) {
            Element.extend(link).up('tr').remove();
        },
        addTierRow: function(link) {
            var tbody = window.dpo_tier_price_popup.select('.tier-popup-table tbody')[0];
            tbody.appendChild(tbody.rowProto.cloneNode(true));
        },
        saveTierPrices: function(button) {
            var tbody = window.dpo_tier_price_popup.select('.tier-popup-table tbody')[0], tierPrice = [], tierPrice2 = [];
            tbody.select('tr').each(function(tr){
                var tier = {};
                tier.qty = parseInt(tr.select('.dpo_tier_qty')[0].value);
                tier.price = parseFloat(tr.select('.dpo_tier_price')[0].value).toFixed(2);
                tier.price_type = tr.select('.dpo_tier_price_type')[0].value;
                if (tier.qty > 0 && !isNaN(tier.price)) tierPrice[tierPrice.length] = tier;
            });
            for(var i=0; i<tierPrice.length-1; i++)
                for(var o=i+1; o<tierPrice.length; o++)
                    if (tierPrice[i].qty > tierPrice[o].qty) {
                        var tmp = tierPrice[o]; tierPrice[o] = tierPrice[i]; tierPrice[i] = tmp;
                    }
            for(var i=0; i<tierPrice.length; i++) if (i == 0 || tierPrice[i].qty > tierPrice[i - 1].qty) tierPrice2[tierPrice2.length] = tierPrice[i];
            tbody.config.tier_price = Object.toJSON(tierPrice2);
            this.hideTierPopup();
        },
        hideTierPopup: function() {
            var flag = window.dpo_tier_price_popup.up('td').select('.dynamicoptions-has-tier')[0];
            var tbody = window.dpo_tier_price_popup.select('.tier-popup-table tbody')[0];
            var isHasTier = tbody.config.tier_price && tbody.config.tier_price.evalJSON().length > 0;
            var ch = window.dpo_tier_price_popup.up('tr').select('.dynamicoptions-value-use_qty')[0];
            flag.update(isHasTier ? '&#10004;' : '');
            //if (isHasTier && !ch.checked) ch.click();
            window.dpo_tier_price_popup.hide();
        }
    };

    Itoris.DynamicProductOptionsTemplate = Class.create();
    Itoris.DynamicProductOptionsTemplate.prototype = {
        id_prefix : 'itoris-dynamicproductoptions',
        initialize : function(messages, config) {
            this.field = $(this.id_prefix + '-new-template-name');
            this.buttonNew = $(this.id_prefix + '-button-new-template');
            this.buttonUpdate = $(this.id_prefix + '-button-update');
            this.buttonDelete = $(this.id_prefix + '-button-delete');
            this.buttonLoad = $(this.id_prefix + '-button-load');
            this.templatesDropdown = $(this.id_prefix + '-templates-dropdown');
            this.loadTemplatesDropdown = $(this.id_prefix + '-templates-dropdown-load');
            this.loadTemplatesDropdownMethod = $(this.id_prefix + '-templates-dropdown-load-method');
            this.cssAdjustmentElm = $(this.id_prefix + '-css-adjustments');
            this.extraJsElm = $(this.id_prefix + '-extra-js');
            this.formStyleElm = $(this.id_prefix + '-form-style');
            this.appearanceElm = $(this.id_prefix + '-appearance');
            this.pricingElm = $(this.id_prefix + '-pricing');
            this.absoluteSkuElm = $(this.id_prefix + '-sku');
            this.absoluteWeightElm = $(this.id_prefix + '-weight');
            this.messages = messages;
            this.config = config;
            Event.observe(this.buttonNew, 'click', this.createNewTemplate.bind(this));
            Event.observe(this.buttonUpdate, 'click', this.updateTemplate.bind(this));
            Event.observe(this.buttonDelete, 'click', this.deleteTemplate.bind(this));
            Event.observe(this.buttonLoad, 'click', this.loadTemplate.bind(this));
            Event.observe(this.field, 'focus', this.focus.bind(this));
            Event.observe(this.field, 'blur', this.blur.bind(this));
            this.blur();
        },
        createNewTemplate : function() {
            if (this.field.value == this.messages.empty_text || this.field.value == '') {
                alert(this.messages.err_no_name);
            } else if (!this.isAvailableName()) {
                alert('A template with such a name already exists');
            } else {
                new Ajax.Request(this.config.urls.create, {
                    method: 'post',
                    parameters: {
                        form_key: FORM_KEY,
                        'template[name]': this.field.value,
                        'template[form_style]': this.formStyleElm.value,
                        'template[appearance]': this.appearanceElm.value,
                        'template[absolute_pricing]': this.pricingElm.value,
                        'template[absolute_sku]': this.absoluteSkuElm.value,
                        'template[absolute_weight]': this.absoluteWeightElm.value,
                        'template[css_adjustments]': this.cssAdjustmentElm.value,
                        'template[extra_js]': this.extraJsElm.value,
                        'template[configuration]': itorisDynamicOptions.updateConfigField()
                    },
                    onComplete: function(res) {
                        var resObj = res.responseText.evalJSON();
                        if (resObj.error) {
                            alert(resObj.error);
                        } else if (resObj.message) {
                            this.config.templates.push(this.field.value.strip());
                            this.addTemplateOption(this.field.value.strip(), resObj.template_id, this.templatesDropdown);
                            this.addTemplateOption(this.field.value.strip(), resObj.template_id, this.loadTemplatesDropdown);
                            alert(resObj.message);
                            this.field.value = '';
                            this.blur();
                        }
                    }.bind(this)
                });
            }
        },
        isAvailableName : function() {
            for (var i = 0; i < this.config.templates.length; i++) {
                if (this.field.value.strip() == this.config.templates[i]) {
                    return false;
                }
            }
            return true;
        },
        addTemplateOption : function(name, value, dropdown) {
            var option = document.createElement('option');
            option.value = value;
            option.text = name;
            dropdown.appendChild(option);
        },
        focus : function(event){
            if (this.field.value == this.messages.empty_text) {
                this.field.value='';
                if (this.field.hasClassName('empty-text')) {
                    this.field.removeClassName('empty-text');
                }
            }
        },
        blur : function(event) {
            if (this.field.value == '') {
                this.field.value = this.messages.empty_text;
                if (!this.field.hasClassName('empty-text')) {
                    this.field.addClassName('empty-text');
                }
            }
        },
        updateTemplate : function() {
            if (parseInt(this.templatesDropdown.value)) {
                if (confirm('Do you really want to overwrite the template?')) {
                    new Ajax.Request(this.config.urls.update, {
                        method: 'post',
                        parameters: {
                            form_key: FORM_KEY,
                            template_id: this.templatesDropdown.value,
                            'template[form_style]': this.formStyleElm.value,
                            'template[appearance]': this.appearanceElm.value,
                            'template[absolute_pricing]': this.pricingElm.value,
                            'template[absolute_sku]': this.absoluteSkuElm.value,
                            'template[absolute_weight]': this.absoluteWeightElm.value,
                            'template[css_adjustments]': this.cssAdjustmentElm.value,
                            'template[extra_js]': this.extraJsElm.value,
                            'template[configuration]': itorisDynamicOptions.updateConfigField()
                        },
                        onComplete: this.onCompleteRequest.bind(this)
                    });
                }
            } else {
                alert('Please select a template');
            }
        },
        deleteTemplate : function() {
            if (parseInt(this.templatesDropdown.value)) {
                if (confirm('Do you really want to delete the template?')) {
                    new Ajax.Request(this.config.urls['delete'], {
                        method     : 'post',
                        parameters : {
                            template_id : this.templatesDropdown.value,
                            form_key    : FORM_KEY
                        },
                        onComplete : function(res) {
                            var resObj = res.responseText.evalJSON();
                            if (resObj.error) {
                                alert(resObj.error);
                            } else if (resObj.message) {
                                alert(resObj.message);
                                this.afterDelete(this.templatesDropdown.value);
                            }
                        }.bind(this)
                    });
                }
            } else {
                alert('Please select a template');
            }
        },
        afterDelete : function(templateId) {
            this.deleteOptionFromDropDown(this.templatesDropdown, templateId);
            this.deleteOptionFromDropDown(this.loadTemplatesDropdown, templateId);
        },
        deleteOptionFromDropDown : function(dropdown, optionId) {
            var options = dropdown.select('option');
            for (var i = 0; i < options.length; i++) {
                if (options[i].value == optionId) {
                    this.config.templates = this.config.templates.without(options[i].text);
                    options[i].remove();
                    dropdown.value = '';
                    return;
                }
            }
        },
        loadTemplate : function() {
            if (parseInt(this.loadTemplatesDropdown.value)) {
                if (confirm('Do you really want to load this template?')) {
                    new Ajax.Request(this.config.urls.load, {
                        method     : 'post',
                        parameters : {
                            product_id: itorisDynamicOptions.config.product_id,
                            template_id : this.loadTemplatesDropdown.value,
                            method : this.loadTemplatesDropdownMethod.value,
                            sections: JSON.stringify(itorisDynamicOptions.sections),
                            form_key    : FORM_KEY
                        },
                        onComplete : function(res) {
                            var resObj = res.responseText.evalJSON();
                            if (resObj.error) {
                                alert(resObj.error);
                            } else if (resObj.message) {
                                alert(resObj.message);
                                this.afterLoad(resObj.template);
                            }
                        }.bind(this)
                    });
                }
            } else {
                alert('Please select a template');
            }
        },
        afterLoad : function(template) {
            itorisDynamicOptions.removeAllSections();
            itorisDynamicOptions.setIsDeleteFlagToAllOptions();
            this.formStyleElm.value = template.form_style;
            this.appearanceElm.value = template.appearance;
            this.pricingElm.value = template.absolute_pricing;
            this.absoluteSkuElm.value = template.absolute_sku;
            this.absoluteWeightElm.value = template.absolute_weight;
            this.cssAdjustmentElm.value = template.css_adjustments;
            this.extraJsElm.value = template.extra_js;
            itorisDynamicOptions.formStyle = template.form_style;
            itorisDynamicOptions.createSections(template.configuration.evalJSON());
        },
        onCompleteRequest : function(res) {
            var resObj = res.responseText.evalJSON();
            if (resObj.error) {
                alert(resObj.error);
            } else if (resObj.message) {
                alert(resObj.message);
            }
        }
    };

    ItorisDynamicOptionsCondition = Class.create();
    ItorisDynamicOptionsCondition.prototype = {
        initialize : function(conditions, newId) {
            this.popup = $('itoris-dynamicproductoptions-conditions-popup').cloneNode(true);
            this.popup.id = newId;
            $$('body')[0].appendChild(this.popup);
            this.optionsIds = [];
            this.toggleElements = {
                toHide : null,
                toShow : null
            };
            this.conditions = conditions;
            this.updateFieldOptions();
            this.initConditions();
            document.observe('click', this.hideOpenedConditionElement.bind(this));
        },
        setPopupPosition : function(top, left) {
            this.popup.setStyle({
                top: top + 'px',
                left: (left - this.popup.getWidth()) + 'px'
            });
        },
        openPopup : function() {
            this.popup.show();
        },
        closePopup : function() {
            this.popup.hide();
        },
        hideOpenedConditionElement : function(e) {
            if (e) {
                var elm = e.target || e.srcElement;
                Element.extend(elm);
                if (typeof elm.descendantOf == 'function') {
                    if (elm.descendantOf(this.popup) && (
                        elm.tagName == 'IMG' || elm.hasClassName('rule-param-element') || elm.hasClassName('rule-param')
                        )) {
                        return true;
                    }
                }
            }
            if (this.toggleElements.toHide && this.toggleElements.toShow) {
                this.toggleVisibility(this.toggleElements.toHide, this.toggleElements.toShow);
                this.toggleElements.toHide = null;
                this.toggleElements.toShow = null;
            }
        },
        addOptionId : function(id) {
            this.optionsIds.push(id);
        },
        removeOptionId : function(id) {
            this.optionsIds = this.optionsIds.without(id);
        },
        updateFieldOptions : function() {
            var template = $('itoris-dynamicoptions-conditions-template-new-condition');
            var optGroup = template.select('.fields-options')[0];
            optGroup.update();
            var fields = itorisDynamicOptions.getAllFields();
            if (fields.length) {
                optGroup.show();
                for (var i = 0; i < fields.length; i++) {
                    if (fields[i].type == 'image' || fields[i].type == 'html') {
                        continue;
                    }
                    var option = document.createElement('option');
                    option.value = fields[i].internal_id;
                    option.innerHTML = 'F' + fields[i].internal_id + ' - ' + fields[i].title;
                    optGroup.appendChild(option);
                }
            } else {
                optGroup.hide();
            }
            $$('.dynamicoptions-visibility, .dynamicoptions-price_type, .dynamicoptions-validation, .dynamicoptions-value-visibility, .dynamicoptions-value-visibility_action').each(function(select){
                if(select.selectedIndex == -1){
                    select.selectedIndex = 0;
                }
            });
            $$('.dynamicoptions-visibility_action').each(function(select){
                if(select.selectedIndex == -1){
                    select.selectedIndex = 1;
                }
            });
        },
        initConditions : function() {
            this.popup.update();
            this.createConditionTree(this.conditions, this.popup, false);
        },
        createConditionTree : function(conditionsObj, parentBlock, removable) {
            var conditions = document.createElement('div');
            Element.extend(conditions);
            parentBlock.appendChild(conditions);
            conditions.update(this.getRuleCombinationTemplate());
            if (removable) {
                var removeIcon = conditions.select('.rule-remove-icon')[0];
                removeIcon.show();
                Event.observe(removeIcon, 'click', this.removeCondition.bind(this, conditionsObj, conditions));
            }
            this.updateParam(conditions.select('.rule-param')[0], conditions.select('.rule-param-element')[0], conditionsObj, 'type', true);
            this.updateParam(conditions.select('.rule-param')[1], conditions.select('.rule-param-element')[1], conditionsObj, 'value', true);
            var mainTree = this.appendRuleTree(parentBlock);
            for (var i = 0; i < conditionsObj.conditions.length; i++) {
                if (conditionsObj.conditions[i].type == 'field') {
                    this.appendFieldCondition(mainTree, conditionsObj.conditions[i]);
                } else {
                    var conditionsNode = document.createElement('li');
                    Element.extend(conditionsNode);
                    mainTree.appendChild(conditionsNode);
                    this.createConditionTree(conditionsObj.conditions[i], conditionsNode, true);
                }
            }
            this.appendNewCondition(mainTree, conditionsObj);
        },
        removeCondition : function(obj, block) {
            if (obj.type == 'field') {
                block.remove();
            } else {
                block.up().remove();
            }
            obj.removeFlag = true;
            this.inspectConditions(this.conditions);
            this.updateConditionResult();
        },
        inspectConditions : function(condition, parent, key) {
            if (condition.removeFlag) {
                var newConditions = parent.conditions.slice(0, key);
                parent.conditions = newConditions.concat(parent.conditions.slice(key + 1));
            } else {
                if (condition.type != 'field') {
                    for (var i = 0; i < condition.conditions.length; i++) {
                        this.inspectConditions(condition.conditions[i], condition, i);
                    }
                }
            }
        },
        appendNewCondition : function(mainTree, condition) {
            var conditionElm = document.createElement('li');
            Element.extend(conditionElm);
            mainTree.appendChild(conditionElm);
            conditionElm.update(this.getNewRuleTemplate());
            Event.observe(conditionElm.select('.new-condition-icon')[0], 'click', function(){
                this.toggleVisibility(conditionElm.select('.new-condition-icon')[0], conditionElm.select('.new-condition')[0], true);
            }.bind(this));
            Event.observe(conditionElm.select('.new-condition')[0], 'change', this.createNewCondition.bind(this, conditionElm.select('.new-condition')[0], condition, conditionElm))
        },
        createNewCondition : function(dropdown, condition, newConditionBlock) {
            var type = dropdown.value;
            if (type) {
                if (type == 'combination') {
                    var conditionsNode = document.createElement('li');
                    Element.extend(conditionsNode);
                    newConditionBlock.insert({before: conditionsNode});
                    var newConditionCombination = {
                        type: 'all',
                        value: 1,
                        conditions: []
                    };
                    condition.conditions.push(newConditionCombination);
                    this.createConditionTree(newConditionCombination, conditionsNode, true);
                } else {
                    var newFieldCondition = {
                        type: 'field',
                        field: type,
                        value: '',
                        condition: 'is'
                    };
                    condition.conditions.push(newFieldCondition);
                    var newField = this.appendFieldCondition(newConditionBlock.up(), newFieldCondition);
                    newConditionBlock.insert({before: newField});
                }
            }
            dropdown.value = '';
        },
        appendFieldCondition : function(mainTree, condition) {
            var conditionElm = document.createElement('li');
            Element.extend(conditionElm);
            mainTree.appendChild(conditionElm);
            conditionElm.update(this.getRuleFieldTemplate());
            conditionElm.select('.rule-param-name')[0].update('F' + condition.field);
            this.updateParam(conditionElm.select('.rule-param')[0], conditionElm.select('.rule-param-element')[0], condition, 'condition', true);
            this.updateParam(conditionElm.select('.rule-param')[1], conditionElm.select('.rule-param-element')[1], condition, 'value', true);
            var removeIcon = conditionElm.select('.rule-remove-icon')[0];
            Event.observe(removeIcon, 'click', this.removeCondition.bind(this, condition, conditionElm));
            return conditionElm;
        },
        appendRuleTree : function(block) {
            var tree = document.createElement('ul');
            Element.extend(tree);
            tree.addClassName('rule-tree');
            block.appendChild(tree);
            return tree;
        },
        updateParam : function(textElm, valuesElm, valueObj, paramKey, bindEvent) {
            if (valuesElm.tagName == 'SELECT') {
                var options = valuesElm.select('option');
                for (var i = 0; i < options.length; i++) {
                    if (options[i].value == valueObj[paramKey]) {
                        options[i].selected = true;
                        textElm.update(options[i].text);
                        break;
                    }
                }
            } else {
                valuesElm.value = valueObj[paramKey];
                textElm.update(valueObj[paramKey] || '...');
            }
            if (bindEvent) {
                Event.observe(textElm, 'click', this.toggleVisibility.bind(this, textElm, valuesElm, true));
                Event.observe(valuesElm, 'change', this.changeParamValue.bind(this, textElm, valuesElm, valueObj, paramKey));
            }
            this.updateConditionResult();
        },
        changeParamValue : function(textElm, valuesElm, valueObj, paramKey) {
            valueObj[paramKey] = valuesElm.value;
            this.updateParam(textElm, valuesElm, valueObj, paramKey);
            this.toggleVisibility(valuesElm, textElm);
        },
        toggleVisibility : function(hideElm, showElm, remember) {
            hideElm.hide();
            showElm.show();
            if (remember) {
                this.hideOpenedConditionElement(null);
                this.toggleElements.toHide = showElm;
                this.toggleElements.toShow = hideElm;
            }
        },
        getRuleCombinationTemplate : function() {
            return $('itoris-dynamicoptions-conditions-template-condition-conbination').innerHTML;
        },
        getRuleFieldTemplate : function() {
            return $('itoris-dynamicoptions-conditions-template-field-condition').innerHTML;
        },
        getNewRuleTemplate : function() {
            return $('itoris-dynamicoptions-conditions-template-new-condition').innerHTML;
        },
        updateConditionResult : function() {
            $('condition-result').value = this.convertConditionToStr(this.conditions);
        },
        convertConditionToStr : function(condition) {
            if (condition) {
                if (condition.type == 'field') {
                    return 'F' + condition.field + ' ' + this.convertConditionCodeToSymbol(condition.condition) + ' \'' + condition.value.replace(/([\"\'])/g, "\\$1") + '\'';
                } else {
                    if (condition.conditions.length) {
                        var subConditions = [];
                        for (var i = 0; i < condition.conditions.length; i++) {
                            if (condition.conditions[i]) {
                                var subConditionStr = this.convertConditionToStr(condition.conditions[i]);
                                if (subConditionStr.length) {
                                    subConditions.push((parseInt(condition.value) ? '' : '!') + '(' + subConditionStr + ')');
                                }
                            }
                        }
                        return subConditions.join(condition.type == 'all' ? ' && ' : ' || ')
                    }
                }
            }
            return '';
        },
        convertStrToConditionObj: function(conditionStr) {
            if (conditionStr.length) {
                return this._parseStrToCondition(conditionStr);
            }
            return {type: 'all', value: 1,conditions: []};
        },
        _parseStrToCondition: function(conditionStr) {
            var conditionType = 'all';
            var conditionValue = 1;
            var conditions = [];

            var conditionStarted = false;
            var bracketCount = 0;
            var hasSubconditions = false;
            var subConditionsStart = null;
            var subConditionsEnd = null;
            var subConditions = [];
            var hasAllType = false;
            for (var i = 0; i < conditionStr.length; i++) {
                var curSymbol = conditionStr[i];
                if (curSymbol == ' ') {continue;}
                if (!conditionStarted) {
                    /** start condition by sign ! or ( **/
                    switch (curSymbol) {
                        case '!':
                            conditionValue = 0;
                            conditionStarted = true;
                            continue;
                        case '(':
                            if (!conditionValue) {
                                /** all conditions should be the same inside one condition
                                 * condition should started by sign ! for conditionValue == 0
                                 * **/
                                return false;
                            }
                            conditionStarted = true;
                            bracketCount++;
                            continue;
                        case '&':
                            /** check for && **/
                            if (conditionStr[i + 1] != '&' || conditionType == 'any') {
                                return false;
                            } else {
                                hasAllType = true;
                            }
                            /** skip next sign & **/
                            i++;
                            continue;
                        case '|':
                            /** check for || **/
                            if (conditionStr[i + 1] != '|' || hasAllType) {
                                return false;
                            } else {
                                conditionType = 'any';
                            }
                            /** skip next sign | **/
                            i++;
                            continue;
                        default:
                            /** wrong symbol **/
                            return false;
                    }
                } else {
                    if (!bracketCount && curSymbol != '(') {
                        /** wrong symbol, should be !(...) **/
                        return false;
                    }
                    switch (curSymbol) {
                        case '!':
                            if (!(bracketCount && !subConditionsStart)) {
                                continue;
                            }
                    /** here subconditions !(...) **/
                        case '(':
                            if (bracketCount) {
                                /** subconditions **/
                                if (!subConditionsStart) {
                                    subConditionsStart = i;
                                    /** current bracket for the first subcondition
                                     * current symbol may be ! or (
                                     * **/
                                    var subOpenBrackets = curSymbol == '(' ? 1 : 0;
                                    var isValueStr = false;
                                    for (i++; i < conditionStr.length; i++) {
                                        curSymbol = conditionStr[i];
                                        if (curSymbol == '(') {
                                            if (!isValueStr) {
                                                subOpenBrackets++;
                                            }
                                        } else if (curSymbol == "'" && conditionStr[i - 1] != '\\') {
                                            isValueStr = !isValueStr;
                                        } else if (curSymbol == ')') {
                                            if (isValueStr) {
                                                continue;
                                            } else if (subOpenBrackets) {
                                                subOpenBrackets--;
                                            } else {
                                                subConditionsEnd = i;
                                                break;
                                            }
                                        }
                                    }
                                    if (isValueStr || !subConditionsEnd) {
                                        /** wrong subcondition **/
                                        return false;
                                    }
                                    /** next sign should be ) that ends condition **/
                                    i--;
                                    continue;
                                }
                            }
                            bracketCount++;
                            continue;
                        case ')':
                            bracketCount++;
                            if (!(bracketCount % 2)) {
                                /** finish condition **/
                                if (subConditionsStart && subConditionsEnd) {
                                    subConditions = this._parseStrToCondition(conditionStr.substring(subConditionsStart, subConditionsEnd));
                                    if (!subConditions) {
                                        return false;
                                    } else {
                                        conditions.push(subConditions);
                                    }
                                }
                                conditionStarted = false;
                                bracketCount = 0;
                                hasSubconditions = false;
                                subConditionsStart = null;
                                subConditionsEnd = null;
                            }
                            continue;
                        case 'F':
                            var conditionField = null;
                            var conditionFieldTemp = [];
                            var fieldConditionOperator = null;
                            var fieldConditionOperatorTemp = [];
                            var skipEndSign = false;
                            var fieldConditionValue = [];
                            for (i++;i < conditionStr.length; i++) {
                                curSymbol = conditionStr[i];
                                if (curSymbol == ' ' && !fieldConditionValue.length) {continue;}
                                if (!conditionField && curSymbol == Number(curSymbol)) {
                                    conditionFieldTemp.push(curSymbol);
                                } else if (!fieldConditionOperator) {
                                    if (conditionFieldTemp.length) {
                                        conditionField = conditionFieldTemp.join('');
                                    }
                                    if (curSymbol != "'") {
                                        fieldConditionOperatorTemp.push(curSymbol);
                                    } else {
                                        fieldConditionOperatorTemp = fieldConditionOperatorTemp.join('');
                                        fieldConditionOperator = this.convertSymbolToConditionCode(fieldConditionOperatorTemp);
                                        if (fieldConditionOperator == fieldConditionOperatorTemp) {
                                            /** wrong operator **/
                                            return false;
                                        }
                                    }
                                } else {
                                    if (curSymbol == '\\') {
                                        skipEndSign = true;
                                        if (conditionStr[i + 1] == "'") {
                                            continue;
                                        }
                                    } else if (curSymbol == "'" && !skipEndSign) {
                                        break;
                                    } else if (skipEndSign) {
                                        skipEndSign = false;
                                    }
                                    fieldConditionValue.push(curSymbol);
                                }
                            }
                            if (i == conditionStr.length || !(conditionField && fieldConditionOperator)) {
                                return false;
                            }
                            fieldConditionValue = fieldConditionValue.join('');
                            var conditionObj = {
                                type: 'field',
                                field: conditionField,
                                value: fieldConditionValue,
                                condition: fieldConditionOperator
                            };
                            conditions.push(conditionObj);
                            continue;
                        default:
                            /** wrong format for condition **/
                            return false;
                    }
                }
            }
            return {
                type       : conditionType,
                value      : conditionValue,
                conditions : conditions
            };
        },
        convertConditionCodeToSymbol : function(code) {
            switch (code) {
                case 'is':
                    return '==';
                case 'is_not':
                    return '!=';
                case 'equal_greater':
                    return '>=';
                case 'equal_less':
                    return '<=';
                case 'greater':
                    return '>';
                case 'less':
                    return '<';
            }
            return code;
        },
        convertSymbolToConditionCode : function(code) {
            switch (code) {
                case '==':
                    return 'is';
                case '!=':
                    return 'is_not';
                case '>=':
                    return 'equal_greater';
                case '<=':
                    return 'equal_less';
                case '>':
                    return 'greater';
                case '<':
                    return 'less';
            }
            return code;
        },
        getConditionsStr : function() {
            return this.convertConditionToStr(this.conditions);
        }
    };

    Validation.add('validate-field-condition', 'Enter valid condition', function (v, elm) {
        return !elm.hasClassName('condition-error');
    });

    return Itoris;
});