/**
* Copyright © 2016 ITORIS INC. All rights reserved.
* See license agreement for details
*/
window.DynamicProductOptions = {
    dateFormat : 'm/d/y',
    groupProducts: [],
    groupPopupContent: null,
    initialize : function(config, options, isGrouped, tierPrices, translations) {
        var obj = this;
        this.priceBoxSelector = '#itoris_dynamicproductoptions_popup_price [data-role=priceBox], [data-role=priceBox][data-product-id="'+config.product_id+'"]';
        if ((!jQuery.mage || !jQuery.mage.priceOptions && !this.getPriceBox()[0]) && options.length) {
            //wait until priceOptions widget is loaded and initialized
            setTimeout(function(){obj.initialize(config, options, isGrouped, tierPrices, translations);}, 200);
            return;
        }
        jQuery('.catalog-product-view #product_addtocart_form').show();
        config.is_grouped = isGrouped;
        this.config = config;
        this.options = options;
        this.tierPrices = tierPrices;
        this.translations = translations;
        this.popup = $('itoris_dynamicproductoptions_popup');
        this.popupMask = $('itoris_dynamicproductoptions_popup_mask');
        this.productForm = $('product_addtocart_form');
        if ((this.getAppearance() == 'popup_cart' || this.getAppearance() == 'popup_configure') && $('product-options-wrapper')) $('product-options-wrapper').insert({bottom: this.popup});
        this.addToCartButton = null;
        this.addToCartBlock = null;
        this.buttonConfigure = null;
        if (!this.options.length && !this.canShowWithoutOptions() || !this.productForm) {
            return;
        }
        this.canReloadPrice = true;
        this.isInit = false;
        this.outOfStockElms = [];
        this.prepareOptions();
        this.initForm();
        this.isInit = true;
        if ((this.config.is_configured || this.config.option_errors.length) && this.getAppearance() == 'popup_configure') {
            this.showConfiguration();
        }
        if (this.config.option_errors.length) {
            if (this.getAppearance() == 'popup_configure' || this.getAppearance() == 'popup_cart') {
                this.showPopup();
                setTimeout(function() {alert(this.config.error_message);}.bind(this), 600);
            } else {
                alert(this.config.error_message);
            }
            for (var i = 0; i < this.config.option_errors.length; i++) {
                this.showFieldError(this.config.option_errors[i].option_id, this.config.option_errors[i].message);
            }
        }
        Event.observe(window, 'resize', this.correctPopupPosition.bind(this));
        jQuery('#qty').on('change', function(){jQuery('.use_product_qty').trigger('change'); obj.updateAllPrices();});
        jQuery('#itoris_dynamicoptions_qty').on('change', function(){ jQuery('#qty').val(this.value); jQuery('#qty').trigger('change'); });
        //hide empty sections
        $$('#itoris_dynamicproductoptions > .fieldset').each(function(section){
            if (!section.select('input, select, textarea, img')[0]) jQuery(section).css({display: 'none'}).addClass('hidden');
        });
        this.negativePriceCheck();
    },
    initForm : function() {
        if (this.config.is_grouped) {
            var origOptions = $$('.itoris_slider_group[data_product_id="'+this.config.product_id+'"]')[0];
            var dynamicOptions = $('itoris_dynamicproductoptions' + this.config.product_id);
            if (origOptions && dynamicOptions) {
                var optionsWrapper = origOptions.select('.product-options-wrapper')[0];
                optionsWrapper.hide();
                if (this.getAppearance() == 'popup_configure' || this.getAppearance() == 'popup_cart') {
                    origOptions.appendChild($('itoris_dynamicproductoptions_configuration' + this.config.product_id));

                    if (optionsWrapper.select('dl').length > 1 || this.canShowWithoutOptions()) {
                        if (!this.options.length) {
                            $('itoris_dynamicproductoptions_popup' + this.config.product_id).select('.itoris_dynamicproductoptions')[0].select('.fieldset').each(function(elm){
                                elm.hide();
                            });
                        }
                        $('itoris_dynamicproductoptions_popup' + this.config.product_id).select('.itoris_dynamicproductoptions')[0].insert({
                            top:optionsWrapper.select('dl')[0].addClassName('product-options')
                        });
                    }

                    if (this.getAppearance() == 'popup_configure') {
                        var editButton = $('itoris_dynamicproductoptions_configuration' + this.config.product_id).select('.itoris_dynamicproductoptions_button_edit')[0];
                        this.popup = $('itoris_dynamicproductoptions_popup' + this.config.product_id);
                        editButton.observe('click', this.showPopup.bind(this));
                        this.popup.select('.itoris_dynamicproductoptions_popup_close_icon')[0].observe('click', this.cancelPopup.bind(this));
                        this.popup.select('.itoris_dynamicproductoptions_popup_button_cancel')[0].observe('click', this.cancelPopup.bind(this));
                        this.popup.select('.itoris_dynamicproductoptions_popup_button_apply')[0].observe('click', this.applyPopupConfig.bind(this));
                        this.showConfiguration();
                    } else {
                        if (!this.groupPopupContent) {
                            this.groupPopupContent = $('itoris_dynamicproductoptions_popup_content');
                        }
                        this.groupPopupContent.appendChild($('itoris_dynamicproductoptions_popup' + this.config.product_id));
                        $('itoris_dynamicproductoptions_popup' + this.config.product_id).show();
                    }
                } else {
                    if (optionsWrapper) {
                        if (optionsWrapper.select('dl').length > 1) {
                            optionsWrapper.select('dl')[0].disallowHide = true;
                            optionsWrapper.select('dl')[0].select('*').each(function(elm){elm.disallowHide = true;});
                        }
                        optionsWrapper.select('*').each(function(elm){if (!elm.disallowHide) elm.hide();});
                        optionsWrapper.select('p.required').each(function(elm){elm.show();});
                        //	optionsWrapper.appendChild($('itoris_dynamicproductoptions'));
                    }
                    dynamicOptions.show();
                    origOptions.appendChild(dynamicOptions);
                }
            }
            /*if (!this.groupProducts.length) {
                this.addToCartButton = this.productForm.select('#product-addtocart-button')[0];
                if (!this.addToCartButton) {
                    return;
                }
                this.addToCartButton.callback = this.addToCartButton.onclick;
                this.addToCartButton.onclick = null;
                this.addToCartButton.observe('click', this.showAddToCartPopupGrouped.bind(this));

                //cart popup
                this.popup.select('.itoris_dynamicproductoptions_popup_close_icon')[0].observe('click', this.cancelPopup.bind(this));
                this.popup.select('.itoris_dynamicproductoptions_popup_button_cancel')[0].observe('click', this.cancelPopup.bind(this));
                $('itoris_dynamicoptions_add_to_cart').observe('click', this.addToCartFromPopupGrouped.bind(this));
            }*/
            this.groupProducts.push(this);
        } else {
            this.addToCartButton = this.productForm.select('button.tocart')[0];
            if (!this.addToCartButton) {
                return;
            }

            var optionsWrapper = $('product-options-wrapper');

            if (this.getAppearance() == 'popup_cart' || this.getAppearance() == 'popup_configure') {
                
                if (optionsWrapper) optionsWrapper.hide();
                var configurableList = $$('.configurable-list');
                for(var i=configurableList.length-1; i>=0; i--) $('itoris_dynamicproductoptions').insert({top: configurableList[i]});
                var form = this.productForm;
                if (form) {
                    var addToBox = document.createElement('div');
                    Element.extend(addToBox);
                    addToBox.addClassName('add-to-box');
                    /*if (form.select('.product-shop')[0]) {
                        var shortDescription = form.select('.product-shop')[0].select('.short-description')[0];
                        if (shortDescription) {
                            shortDescription.insert({before: addToBox});
                        } else {
                            form.select('.product-shop')[0].appendChild(addToBox);
                        }
                    }*/
                    var optionBottomBlock = form.select('.product-options-bottom')[0];
                    var addToCart = $('product-addtocart-button');
                    if (!addToCart) addToCart = $('product-updatecart-button');
                    if (addToCart) optionBottomBlock = addToCart.up('.product-options-bottom');
                    if (optionBottomBlock) {
                        if (this.getAppearance() == 'popup_configure') optionBottomBlock.hide(); else optionBottomBlock.select('.field.qty')[0].hide();
                        //var addToLinks = optionBottomBlock.select('.add-to-links')[0];
                        if (addToCart) {
                            this.addToCartBlock = addToCart;
                            //addToBox.appendChild(addToCart);
                            if (this.getAppearance() == 'popup_configure') {
                                addToCart.hide();
                                var addToBoxConfigure = $('itoris_dynamicproductoptions_add_to_cart_configure');
                                addToBoxConfigure.addClassName('add-to-cart');
                                if ($$('.product-info-main .product-info-price')[0]) {
                                    $$('.product-info-main .product-info-price')[0].insert({after: addToBoxConfigure});
                                } else {
                                    addToBox.appendChild(addToBoxConfigure);
                                }
                                addToBoxConfigure.show();
                                addToBoxConfigure.observe('click', this.showPopup.bind(this));
                                this.buttonConfigure = addToBoxConfigure;
                            } else {
                                var obj = this;
                                addToCart.observe('click', function(ev){
                                    if (!$$('#itoris_dynamicproductoptions_popup_price .price')[0]) {
                                        ev.stop();
                                        obj.showPopup();
                                    }
                                });
                            }
                            /*if (addToLinks) {
                                var orSpan = document.createElement('span');
                                Element.extend(orSpan);
                                orSpan.addClassName('or');
                                orSpan.update('OR');
                                addToBox.appendChild(orSpan);
                            }*/
                        }
                        /*if (addToLinks) {
                            addToBox.appendChild(addToLinks);
                        }*/
                    }
                }
                $$('body')[0].appendChild(this.popupMask);
                $('itoris_dynamicproductoptions_popup_button_cancel').observe('click', this.cancelPopup.bind(this));
                $('itoris_dynamicproductoptions_popup_close_icon').observe('click', this.cancelPopup.bind(this));
                $('itoris_dynamicoptions_add_to_cart').observe('click', this.addToCartFromPopup.bind(this));
                $('itoris_dynamicproductoptions_popup_button_apply').observe('click', this.applyPopupConfig.bind(this));
                $('itoris_dynamicproductoptions_button_edit').observe('click', this.showPopup.bind(this));
                if ($('product-price-' + this.config.product_id + '_clone')) {
                    $('itoris_dynamicproductoptions_popup_price').appendChild($('product-price-' + this.config.product_id + '_clone'));
                }
                if (optionsWrapper.select('.swatch-opt')[0] || optionsWrapper.select('.configurable')[0] || this.canShowWithoutOptions()) {
                    if (!this.options.length) {
                        $('itoris_dynamicproductoptions').select('.fieldset').each(function(elm){elm.hide();});
                    }
                    if (optionsWrapper.select('.swatch-opt')[0]) {
                        $('itoris_dynamicproductoptions').insert({top:optionsWrapper.select('.swatch-opt')[0].addClassName('product-options')});
                    }
                    if (optionsWrapper.select('.configurable')[0]) {
                        $('itoris_dynamicproductoptions').insert({top:optionsWrapper.select('.configurable')[0].addClassName('product-options')});
                    }
                }
                if (this.getAppearance() == 'popup_configure') {
                    //this.showConfiguration();
                }
            } else {
                if (optionsWrapper) {
                    optionsWrapper.select('.swatch-opt, .configurable').each(function(swatch){
                        swatch.parentNode.disallowHide = true;
                        swatch.disallowHide = true;
                        swatch.select('*').each(function(elm){elm.disallowHide = true;});                            
                    });
                    optionsWrapper.select('*').each(function(elm){if (!elm.disallowHide) elm.hide();});
                    optionsWrapper.select('p.required').each(function(elm){elm.show();});
                    optionsWrapper.appendChild($('itoris_dynamicproductoptions'));
                }
            }
        }
        this.changeOpFileInitialization();
    },
    getAppearance : function() {
        return this.isGrouped() ? 'on_product_view' : this.config.appearance;
    },
    canShowWithoutOptions: function() {
        return this.getAppearance() != 'on_product_view' && (this.config.product_type == 'bundle' || this.config.product_type == 'configurable');
    },
    presetOptionsQty: function() {
        for(var key in this.config.options_qty) {
            var qty = this.config.options_qty[key];
            if (typeof qty == 'object') {
                for(var key2 in qty) {
                    var qty2 = qty[key2];
                    var qtyElm = document.getElementsByName('options_qty['+key+']['+key2+']')[0];
                    if (qtyElm) qtyElm.value = qty2;
                }
            } else {
                var qtyElm = document.getElementsByName('options_qty['+key+']')[0];
                if (qtyElm) qtyElm.value = qty;
            }
        }
    },
    prepareOptions : function() {
        this.canUseEffects = false;
        this.canReloadPrice = false;
        $$('select').each(function(select){
            if (!select.optionList) {
                select.optionList = select.select('option');
                select.optionList.each(function(option){option._parent = select});
            }
        });
        for (var i = 0; i < this.config.section_conditions.length; i++) {
            var conditions = this.config.section_conditions[i].visibility_condition.evalJSON();
            var sectionField = this.getDynamicOptionsBlock().select('.fieldset-section-' + this.config.section_conditions[i].order)[0];
            if (sectionField) {
                if (conditions.conditions.length && this.isConditionCorrect(conditions)) {
                    this.updateVisibility(this.config.section_conditions[i].visibility_action, sectionField, {type: 'section'});
                } else {
                    this.updateVisibility(this.config.section_conditions[i].visibility, sectionField, {type: 'section'});
                }
            }
        }
        for (var i = 0; i < this.options.length; i++) {
            if (this.isSystemOption(this.options[i])) {
                var origField = this.getOrigOptionField(this.options[i]);
                var dynamicField = this.getDynamicOptionField(this.options[i].id);
                if (origField && dynamicField) {
                    if (this.options[i].type == 'radio' || this.options[i].type == 'checkbox') {
                        this.prepareRadioOptions(origField, this.options[i]);
                    } else if (this.options[i].type == 'multiple' || this.options[i].type == 'drop_down') {
                        this.prepareDropdownOptions(origField, this.options[i]);
                    }
                    dynamicField.appendChild(origField);
                    if (this.options[i].css_class) {
                        if (this.config.form_style == 'list_div') {
                            dynamicField.up('li').addClassName(this.options[i].css_class);
                        } else {
                            dynamicField.up('td').addClassName(this.options[i].css_class);
                        }
                    }

                    if (this.options[i].type == 'field' && this.options[i].validation) {
                        var inputElm = origField.select('input')[0];
                        if (inputElm) {
                            switch (this.options[i].validation) {
                                case 'email':
                                    inputElm.addClassName('validate-email');
                                    break;
                                case 'number':
                                    inputElm.addClassName('validate-digits');
                                    break;
                                case 'money':
                                    inputElm.addClassName('validate-money');
                                    break;
                                case 'phone':
                                    inputElm.addClassName('validate-phone-number');
                                    break;
                                case 'zip':
                                    inputElm.addClassName('validate-zip');
                                    break;
                            }
                        }
                    }
                    this.updateVisibility(this.options[i].visibility, dynamicField, this.options[i]);
                    if (this.options[i].type == 'radio' || this.options[i].type == 'checkbox') {
                        for (var j = 0; j < this.options[i].items.length; j++) {
                            if (typeof this.options[i].items[j] != 'undefined') {
                                this.updateVisibility(this.options[i].items[j].visibility, this.options[i].items[j].elm.up('div.choice'), this.options[i].items[j], false);
                            }
                        }
                    }
                    var oldIE = Prototype.Browser.IE && parseInt(navigator.userAgent.substring(navigator.userAgent.indexOf('MSIE')+5)) < 9;
                    dynamicField.select('input, select, textarea').each(function(elm){
                        Event.observe(elm, 'change', this.checkVisibilityConditions.bind(this, elm));
                        if (elm.tagName != 'SELECT') {
                            Event.observe(elm, 'keyup', this.checkVisibilityConditionsKeyUp.bind(this, elm));
                            if (oldIE) {
                                elm.attachEvent ('onpaste', this.checkVisibilityConditions.bind(this,elm));
                            } else {
                                elm.addEventListener ('paste', this.checkVisibilityConditions.bind(this, elm), false);
                            }
                        }
                    }.bind(this));
                }
            }
        }

        var obj = this;
        this.getPriceBox().on('updatePrice', function(ev, prices){
            if (!prices) return;
            
            var priceBox = obj.getPriceBox().data('magePriceBox');
            var finalPrice = priceBox.options.priceConfig.prices ? priceBox.options.priceConfig.prices.finalPrice : priceBox.options.prices.finalPrice;
            var basePrice = priceBox.options.priceConfig.prices ? priceBox.options.priceConfig.prices.basePrice : (priceBox.options.prices.basePrice ? priceBox.options.prices.basePrice : priceBox.options.prices.finalPrice);

            if (!obj.taxRate) {
                obj.taxRate = (basePrice.amount && finalPrice.amount) ? finalPrice.amount / basePrice.amount : 1;
                obj.initialPrices = {basePrice: basePrice.amount, finalPrice: finalPrice.amount};
            }

            for(key in prices) {
                var _key = key;
                var qtyElm = obj.updateQtyElement(key);
                if (qtyElm && prices[key].basePrice) {
                    var qty = parseInt(qtyElm.value);
                    if (qtyElm.hasClassName('use_product_qty')) qty = obj.getQtyField() ? obj.getQtyField().value : 1;
                    if (isNaN(qty) || qty < 1) qty = 1;
                    if (!prices[key].dpoPrice) prices[key].dpoPrice = {amount: prices[key].basePrice.amount};
                    prices[key].basePrice.amount = prices[key].dpoPrice.amount * qty;
                    prices[key].finalPrice.amount = prices[key].dpoPrice.amount * obj.taxRate * qty;
                    
                    var tierPrice = obj.getTierPrice(qtyElm, prices[key].dpoPrice.amount);
                    if (tierPrice) {
                        prices[key].basePrice.amount = tierPrice * (qtyElm.hasClassName('use_product_qty') ? 1 : qty);
                        prices[key].finalPrice.amount = tierPrice * obj.taxRate * (qtyElm.hasClassName('use_product_qty') ? 1 : qty);
                    }
                }                
                if (obj.config.absolute_pricing == 2 && prices[key].basePrice) { //fixed pricing
                    prices[key].basePrice.amount = 0;
                    prices[key].finalPrice.amount = 0;
                }
            }
            
            if (obj.getQtyField().value - 0 > 0) {
                var productTierPrice = 0;
                for(var i=0; i<obj.tierPrices.length; i++) {
                    if (obj.tierPrices[i].qty <= obj.getQtyField().value - 0) productTierPrice = obj.tierPrices[i].price;
                }
                if (productTierPrice > 0) {
                    basePrice.amount = productTierPrice;
                    finalPrice.amount = productTierPrice * obj.taxRate;
                } else {
                    basePrice.amount = obj.initialPrices.basePrice;
                    finalPrice.amount = obj.initialPrices.finalPrice;
                }
            }
            
            if (obj.config.absolute_pricing == 1) { //absolute pricing
                basePrice.amount = 0;
                finalPrice.amount = 0;
            }
            if (!obj.updatePriceOnce) {
                obj.updatePriceOnce = true;
                jQuery(this).trigger('updatePrice', prices);
                obj.updatePriceOnce = false;
            }
        });
        
        this.checkVisibilityConditions();
        this.canUseEffects = true;
        this.canReloadPrice = true;
    },
    updateAllPrices: function() {
        $$('#itoris_dynamicproductoptions select, #itoris_dynamicproductoptions textarea, #itoris_dynamicproductoptions input[type=text]').each(function(elm){
            jQuery(elm).trigger('change');
        });
        $$('#itoris_dynamicproductoptions input[type=checkbox], #itoris_dynamicproductoptions input[type=radio]').each(function(elm){
            if (elm.checked) jQuery(elm).trigger('change');
        });
    },
    updateQtyElement : function(key) {
        if (key.indexOf('##')) key = "'"+key.replace('##', "'][value='")+"'";
        var elms = $$('[name='+key+']'), obj = this, _qtyElm = null;
        elms.each(function(elm){
            if (elm.tagName == 'INPUT') {
                if (typeof elm.use_qty != 'undefined' && (elm.use_qty || elm.tier_price && elm.tier_price.length > 0)) {
                    var qtyElm = obj.addQtyElement(elm);
                    if (elm.checked) {
                        obj.updateQtyComment(qtyElm);
                        _qtyElm = qtyElm;
                    } else if (qtyElm) {
                        qtyElm.up('div').remove();
                    }
                }
            } else if (typeof elm.qty_values != 'undefined' && (elm.qty_values.length || elm.tier_values)) {
                var qtyElm = obj.addQtyElement(elm);
                if (elm.qty_values.indexOf(elm.value) == -1 && (!elm.tier_values || !elm.tier_values[elm.value]) && qtyElm) {
                    qtyElm.up('div').remove();
                } else {
                    obj.updateQtyComment(qtyElm);
                    _qtyElm = qtyElm;
                }
            }
        });

        return _qtyElm;
    },
    updateQtyComment: function(qtyElm) {
        var commentBox = qtyElm.up().select('.option-qty-comment')[0], tierPrices = this.getTierPrices(qtyElm);
        if (!commentBox) return;
        if (tierPrices.length > 0) {
            var tierList = "";
            for(var i=0; i<tierPrices.length; i++) {
                tierList += '<div>'+this.getTranslation('Buy %1 for %2 each').replace('%1', '<b>'+tierPrices[i].qty+'</b>').replace('%2', '<b>'+ formatCurrency(parseFloat(tierPrices[i].price), this.getPriceBox().data('magePriceBox').options.priceConfig.priceFormat, false) +'</b>')+'</div>';
            }
            commentBox.update(tierList);
        } else commentBox.update("");
    },
    getTierPrices: function(qtyElm) {
        var tierPrices = [];
        for(var i=0; i<qtyElm.parentElm.config.items.length; i++) {
            var tier = qtyElm.parentElm.config.items[i].tier_price, optionId = qtyElm._parent.value, optionConfigId = qtyElm.parentElm.config.items[i].option_type_id;
            var inputs = qtyElm.parentElm.select('input.radio, input.checkbox'), selectedInputs = ',';
            inputs.each(function(input){if (input.checked) selectedInputs += input.value + ',';});
            if (tier && optionConfigId && (optionId == optionConfigId /*|| selectedInputs.indexOf(','+optionConfigId+',') > -1*/)) return tier.evalJSON();
        }
        return [];
    },
    getTierPrice: function(qtyElm, price) {
        var qty = parseInt(qtyElm.hasClassName('use_product_qty') ? (this.getQtyField() ? this.getQtyField().value : 1) : qtyElm.value), tierPrices = this.getTierPrices(qtyElm);
        if (tierPrices.length > 0) {
            for(var i=0; i<tierPrices.length; i++) if (qty >= parseInt(tierPrices[i].qty)) price = parseFloat(tierPrices[i].price);
        }
        return price;
    },
    addQtyElement: function(elm) {
        var qtyId = elm.id + '_qty';
        if ($(qtyId)) {
            return $(qtyId);
        } else {
            var box = document.createElement('div');
            Element.extend(box);
            var labelQty = document.createElement('span');
            Element.extend(labelQty);
            labelQty.update(this.getTranslation('Qty')+":");
            labelQty.className = 'option-qty-label';
            if (!elm.use_qty && (!elm.qty_values || elm.qty_values.indexOf(elm.value) == -1)) labelQty.style.display = 'none';
            box.appendChild(labelQty);
            var elmQty = document.createElement('input');
            Element.extend(elmQty);
            elmQty.id = qtyId;
            elmQty.className = 'input-text option-qty required-entry validate-number-range number-range-1-10000';
            elmQty.value = 1;
            elmQty.parentElm = elm.config ? elm : elm.up('div.options-list');
            elmQty._parent = elm;
            jQuery(elmQty).on('change', function(){
                jQuery(elm).trigger('change');
            });
            if (elm.next() && elm.next().hasClassName('label')) {
                elm.next().insert({after: box});
            } else {
                elm.insert({after: box});
            }
            if (!elm.use_qty && (!elm.qty_values || elm.qty_values.indexOf(elm.value) == -1)) {
                elmQty.style.display = 'none';
                elmQty.addClassName('use_product_qty');
            } else {
                elmQty.name = elm.qty_input_name;
            }
            box.appendChild(elmQty);
            var commentQty = document.createElement('div');
            Element.extend(commentQty);
            commentQty.className = 'option-qty-comment';
            box.appendChild(commentQty);
            return elmQty;
        }
    },
    updateVisibility : function(visibility, field, option, fieldIsElm) {
        if(field !== undefined){
            if (option.type == 'image' || option.type == 'html') {
                field = $('itoris_dynamic_option_id_' + option.itoris_option_id);
                if (field) {
                    switch (visibility) {
                        case 'visible':
                            this.showField(field);
                            break;
                        case 'hidden':
                            this.hideField(field);
                            break;
                    }
                }
                return;
            }
            switch (visibility) {
                case 'visible':
                    if (field.visible() && !field.hasClassName('field-disabled') && !field.disabled) break;
                    this.showField(field);
                    var _parent = field.up('.field');
                    if (_parent && _parent.hasClassName('field-disabled')) break;
                    if (fieldIsElm) {
                        field.disabled = false;
                    } else {
                        field.select('input, select, textarea').each(function(elm) {
                            if (elm._defaultState) {
                                if (elm.value != elm._defaultState.value ||
                                    elm.checked != elm._defaultState.checked ||
                                    elm.selected != elm._defaultState.selected ||
                                    elm.selectedIndex != elm._defaultState.selectedIndex) {
                                        elm.value = elm._defaultState.value;
                                        elm.checked = elm._defaultState.checked;
                                        elm.selected = elm._defaultState.selected;
                                        elm.selectedIndex = elm._defaultState.selectedIndex;
                                        jQuery(elm).trigger('change');
                                    }
                            }
                            elm.disabled = false;
                        });
                    }
                    break;
                case 'hidden':
                    this.hideField(field);
                case 'disabled':
                    if (fieldIsElm) {
                        field.disabled = true;
                    } else {
                        field.select('input, select, textarea').each(function(elm) {
                            if (!elm._defaultState) elm._defaultState = {value: elm.value, checked: elm.checked, selected: elm.selected, selectedIndex: elm.selectedIndex};
                            if (elm.type == 'select' && elm.selectedIndex != 0) {
                                elm.selectedIndex = 0;
                                jQuery(elm).trigger('change');
                            } else if (elm.type != 'checkbox' && elm.type != 'radio' && elm.value != '') {
                                elm.value = '';
                                jQuery(elm).trigger('change');
                            } else if (elm.type == 'checkbox' && elm.checked) {
                                elm.checked = false;
                                jQuery(elm).trigger('change');
                            }
                            if (elm.type == 'radio' && elm.checked) {
                                var valueTmp = elm.value;
                                elm.value = '';
                                jQuery(elm).trigger('change');
                                elm.checked = false;
                                elm.value = valueTmp;
                            }
                            elm.disabled = true;
                            if (elm.hasClassName('option-qty')) elm.value = 1;
                        }.bind(this));
                    }
                    break;
            }
            if (!fieldIsElm) {
                var fieldBox = field.hasClassName('field') || field.up('.options-list') ? field : field.up('.field');
                if (fieldBox) {
                    if (visibility == 'disabled') {
                        if (!fieldBox.hasClassName('field-disabled')) {
                            field.addClassName('field-disabled');
                        }
                    } else {
                        if (fieldBox.hasClassName('field-disabled')) {
                            field.removeClassName('field-disabled');
                        }
                    }
                }
            }
            if (this.canReloadPrice) {
                //this.getPriceBox().trigger('updatePrice');
            }
        }
    },
    hideField: function(field) {
        if (this.canUseEffects) {
            if (field.visible()) {
                var beforeOverflow = field.style.overflow;
                field.style.overflow = 'hidden';
                field.addClassName('ihidden');
                jQuery(field).fadeOut(100)
                jQuery(field).animate({height: 0, width: 0}, 100, 'linear', function(effect) {field.style.overflow = beforeOverflow;});
            }
        } else {
            field.hide();
            field.addClassName('ihidden');
        }
        if (field.tagName.toLowerCase() == 'option') {
            var select = field.up();
            field.hiddenFlag = true;
            if (select) {
                //select.removeChild(field);
                field.remove();
            }
        }

    },
    showField: function(field) {
        if (this.canUseEffects) {
            if (!field.visible()) {
                field.setStyle({height:'auto', width: 'auto'});
                var targetHeight = field.getHeight();
                var targetWidth = field.getWidth();
                if (Prototype.Browser.IE) {
                    targetWidth++;
                }
                field.setStyle({height:0, width:0});
                var beforeOverflow = field.style.overflow;
                field.style.overflow = 'hidden';
                field.removeClassName('ihidden');
                jQuery(field).fadeIn(100)
                jQuery(field).animate({height: targetHeight + 'px', width: targetWidth + 'px'}, 100, 'linear', function(effect) {field.style.overflow = beforeOverflow; field.setStyle({width:'', height:'auto'});});
            }
        } else {
            field.show();
            field.removeClassName('ihidden');
        }
        if (field.tagName.toLowerCase() == 'option' && !field.up() && field._parent) {
            var selectedValue = field._parent.value;
            field.hiddenFlag = false;
            field._parent.select('option').each(function(option){option.remove();/*option.up().removeChild(option)*/});
            field._parent.optionList.each(function(option){if (!option.hiddenFlag) field._parent.appendChild(option)});
            field._parent.selectedIndex = 0;
            field._parent.select('option').each(function(option){if (option.value == selectedValue) option.selected = true;});
        }
    },
    checkVisibilityConditionsKeyUp : function(elm) {
        setTimeout(function(){
            if (!this.activeChecking) {
                this.checkVisibilityConditions(elm);
            }
        }.bind(this), 500);
    },
    checkVisibilityConditions: function(elm){
        this.checkVisibilityConditionsNode(elm, 1);
        this.checkVisibilityConditionsNode(elm, 2);
    },
    checkVisibilityConditionsNode : function(elm, num) {
        if (this.activeChecking) {
            setTimeout(this.checkVisibilityConditionsNode.bind(this), 500);
        } else {
            this.disableOutOfStockElms();
            this.activeChecking = true;
            this.canReloadPrice = false;
            if(num != 2){
                this.hideDefaultMessages();
            }

            for (var i = 0; i < this.config.section_conditions.length; i++) {
                var conditions = this.config.section_conditions[i].visibility_condition.evalJSON();
                var sectionField = this.getDynamicOptionsBlock().select('.fieldset-section-' + this.config.section_conditions[i].order)[0];
                if (sectionField) {
                    if (conditions.conditions.length && this.isConditionCorrect(conditions)) {
                        this.updateVisibility(this.config.section_conditions[i].visibility_action, sectionField, {type: 'section'});
                    } else {
                        this.updateVisibility(this.config.section_conditions[i].visibility, sectionField, {type: 'section'});
                    }
                }
            }
            for (var i = 0; i < this.options.length; i++) {
                if (this.options[i].visibility_condition) {
                    var conditions = this.options[i].visibility_condition.evalJSON();
                    if (conditions.conditions.length && this.isConditionCorrect(conditions)) {
                        this.updateVisibility(this.options[i].visibility_action, this.getDynamicOptionField(this.options[i].id), this.options[i]);
                    } else {
                        this.updateVisibility(this.options[i].visibility, this.getDynamicOptionField(this.options[i].id), this.options[i]);
                    }
                } else if (this.options[i].visibility == 'hidden') {
                    this.updateVisibility(this.options[i].visibility, this.getDynamicOptionField(this.options[i].id), this.options[i]);
                }
                if (elm /*&& elm.type != 'change'*/
                    && this.options[i].option_field
                    && (this.options[i].option_field.up('div.fieldset').hasClassName('ihidden')
                    || this.options[i].option_field.up('div.field').hasClassName('ihidden'))
                ) {
                    if (elm.id && elm.id != 'options_'  + this.options[i].option_id + '_text' && this.options[i].type == 'field' && this.options[i].default_value && $('options_'  + this.options[i].option_id + '_text').value == this.options[i].default_value) {
                        $('options_'  + this.options[i].option_id + '_text').value = '';//this.options[i].default_value;
                    } else if (elm.id && elm.id != 'options_'  + this.options[i].option_id + '_text' && this.options[i].type == 'area' && this.options[i].default_value && $('options_'  + this.options[i].option_id + '_text').value == this.options[i].default_value) {
                        $('options_'  + this.options[i].option_id + '_text').value = '';
                    }
                }
                else if(elm
                    && this.options[i].option_field
                    && (!this.options[i].option_field.up('div.fieldset').hasClassName('ihidden')
                    || !this.options[i].option_field.up('div.field').hasClassName('ihidden')) ){
                    if (elm.id && elm.id != 'options_'  + this.options[i].option_id + '_text' && this.options[i].type == 'field' && this.options[i].default_value && $('options_'  + this.options[i].option_id + '_text').value == '') {
                        $('options_'  + this.options[i].option_id + '_text').value = this.options[i].default_value;
                    } else if (elm.id && elm.id != 'options_'  + this.options[i].option_id + '_text' && this.options[i].type == 'area' && this.options[i].default_value && $('options_'  + this.options[i].option_id + '_text').value == '') {
                        $('options_'  + this.options[i].option_id + '_text').value = this.options[i].default_value;
                    }
                }

                if (typeof this.options[i].items != 'undefined') {
                    var isSelectElm = this.options[i].type == 'drop_down' || this.options[i].type == 'multiple';
                    var isAnyVisible = false;
                    for (var j = 0; j < this.options[i].items.length; j++) {
                        if (this.options[i].items[j] === null) continue;
                        if (this.options[i].items[j].visibility_condition) {
                            var conditions = this.options[i].items[j].visibility_condition.evalJSON();
                            if (conditions.conditions.length && this.isConditionCorrect(conditions)) {
                                this.updateVisibility(this.options[i].items[j].visibility_action, isSelectElm ? this.options[i].items[j].elm : this.options[i].items[j].elm.up('div.choice'), this.options[i].items[j], isSelectElm);
                                if (this.options[i].items[j].visibility_action == 'visible') {
                                    isAnyVisible = true;
                                }
                            } else {
                                this.updateVisibility(this.options[i].items[j].visibility, isSelectElm ? this.options[i].items[j].elm : this.options[i].items[j].elm.up('div.choice'), this.options[i].items[j], isSelectElm);
                                if (this.options[i].items[j].visibility == 'visible') {
                                    isAnyVisible = true;
                                }
                            }
                        } else {
                            isAnyVisible = true;
                        }
                        if(elm !== undefined && this.options[i].items[j] !== undefined && this.options[i].items[j].elm !== undefined && this.options[i].items[j].elm._parent !== undefined) {
                            if (elm && this.options[i].items[j].elm._parent && (this.options[i].items[j].elm._parent.up('div.field') && this.options[i].items[j].elm._parent.up('div.field').hasClassName('ihidden')
                                || this.options[i].items[j].elm._parent.up('div.fieldset') && this.options[i].items[j].elm._parent.up('div.fieldset').hasClassName('ihidden')) ||
                                elm && !this.options[i].items[j].elm._parent && (this.options[i].items[j].elm.up('div.field') && this.options[i].items[j].elm.up('div.field').hasClassName('ihidden')
                                || this.options[i].items[j].elm.up('div.fieldset') && this.options[i].items[j].elm.up('div.fieldset').hasClassName('ihidden'))
                            ) {

                                if (elm.type != 'checkbox' && this.options[i].type == 'checkbox' && parseInt(this.options[i].items[j].is_selected)) {
                                    this.options[i].items[j].elm.checked = true;
                                } else if (elm.type != 'dropdown' && this.options[i].type == 'drop_down' && parseInt(this.options[i].items[j].is_selected)) {
                                    this.options[i].items[j].elm.selected = 'selected';
                                } else if (elm.type != 'multiple' && this.options[i].type == 'multiple') {
                                    if (parseInt(this.options[i].items[j].is_selected)) {
                                        this.options[i].items[j].elm.selected = 'selected';
                                    } else {
                                        this.options[i].items[j].elm.selected = false;
                                    }
                                } else if (elm.type != 'radio' && this.options[i].type == 'radio' && parseInt(this.options[i].items[j].is_selected)) {
                                    this.options[i].items[j].elm.checked = true;
                                }
                            }
                        }

                    }
                    if (this.options[i].type == 'drop_down') {
                        var dropdownElm = $('select_' + this.options[i].id);
                        if (dropdownElm) {
                            var dropdownOptions = dropdownElm.select('option');
                            var hasSelected = false;
                            for (var j = 0; j < dropdownOptions.length; j++) {
                                if (!dropdownOptions[j].disabled && dropdownOptions[j].selected) {
                                    hasSelected = true;
                                    break;
                                }
                            }
                            if (!hasSelected && dropdownOptions.length) {
                                dropdownOptions[0].selected = true;
                            }
                        }
                    } else if (this.options[i].type == 'multiple') {
                        var dropdownElm = $('select_' + this.options[i].id);
                        if (dropdownElm) {
                            var dropdownOptions = dropdownElm.select('option');
                            for (var j = 0; j < dropdownOptions.length; j++) {
                                if (dropdownOptions[j].disabled && dropdownOptions[j].selected) {
                                    dropdownOptions[j].selected = false;
                                }
                            }
                        }
                    }
                    if (this.options[i].type == 'drop_down' && !this.options[i].visibility_condition && this.options[i].visibility_condition != null) {
                        if (isAnyVisible) {
                            if (!this.getDynamicOptionField(this.options[i].id).visible()) {
                                this.updateVisibility('visible', this.getDynamicOptionField(this.options[i].id), this.options[i]);
                            }
                        } else if (this.getDynamicOptionField(this.options[i].id).visible()) {
                            this.updateVisibility('hidden', this.getDynamicOptionField(this.options[i].id), this.options[i]);
                        }
                    }
                }
            }
            this.disableOutOfStockElms();
            if(num != 1) {
                this.showDefaultMessages();
            }
            if (this.canUseEffects) {
                setTimeout(function(){this.activeChecking=false;}.bind(this), 500);
            } else {
                this.activeChecking = false;
            }
            this.canReloadPrice = true;
            //this.getPriceBox().trigger('updatePrice');
            if (this.popup.visible()) {
                var popupResizer = setInterval(this.resizePopup.bind(this), 50);
                setTimeout(function(){clearInterval(popupResizer);}, 600);
            }
        }
    },
    disableOutOfStockElms: function() {
        this.outOfStockElms.each(function(elm){
            elm.disabled = true;
            if (elm.tagName != 'OPTION') {
                var field = elm.up('div.choice');
                if (!field.hasClassName('field-disabled')) {
                    field.addClassName('field-disabled')
                }
            }
        });
    },
    isConditionCorrect : function(condition) {
        var isCorrect = true;
        if (condition.type == 'field') {
            return this.isCorrect(condition.value, condition.condition, this.getOptionValue(this.getOptionByInternalId(condition.field), false, true, true))
        } else {
            for (var i = 0; i < condition.conditions.length; i++) {
                if (this.isConditionCorrect(condition.conditions[i])) {
                    if (condition.value) {
                        if (condition.type == 'any') {
                            return true;
                        }
                    } else {
                        if (condition.type == 'all') {
                            return false;
                        } else {
                            isCorrect = false;
                        }
                    }
                } else {
                    if (condition.value) {
                        if (condition.type == 'all') {
                            return false;
                        } else {
                            isCorrect = false;
                        }
                    } else {
                        if (condition.type == 'any') {
                            return true;
                        }
                    }
                }
            }
        }
        return isCorrect;
    },
    isCorrect : function(value, condition, optionValue) {
        if (optionValue instanceof Array && !optionValue.length) {
            optionValue = '';
        }
        if (optionValue instanceof Array) {
            for (var i = 0; i < optionValue.length; i++) {
                if (condition == 'is_not') {
                    if (this.isCorrect(value, 'is', optionValue[i])) {
                        return false;
                    }
                } else {
                    if (this.isCorrect(value, condition, optionValue[i])) {
                        return true;
                    }
                }
            }
            if (condition == 'is_not') {
                if (value == '' && !optionValue.length) {
                    return false;
                }
                return true;
            }
        } else {
            value = this.verifyNumeric(value);
            optionValue = this.verifyNumeric(optionValue);
            switch (condition) {
                case 'is':
                    return value == optionValue;
                case 'is_not':
                    return value != optionValue;
                case 'equal_greater':
                    return optionValue >= value;
                case 'equal_less':
                    return optionValue <= value;
                case 'greater':
                    return optionValue > value;
                case 'less':
                    return optionValue < value;
            }
        }
        return false;
    },
    getOptionByInternalId : function(id) {
        for (var i = 0; i < this.options.length; i++) {
            if (id == this.options[i].internal_id) {
                return this.options[i];
            }
        }
    },
    getOptionById : function(id) {
        for (var i = 0; i < this.options.length; i++) {
            if (id == this.options[i].id) {
                return this.options[i];
            }
        }
    },
    prepareRadioOptions : function(field, option) {
        var allRadios = field.select('input');
        var optionsBlock = field.select('.options-list')[0];
        var hasImages = false;
        var carriageReturnElms = [];
        for (var i = 0; i < option.items.length; i++) {
            for (var j = 0; j < allRadios.length; j++) {
                if (allRadios[j].value == option.items[i].option_type_id) {
                    if (parseInt(option.items[i].is_disabled)) {
                        allRadios[j].up('div.choice').remove();
                        continue;
                    } else if (parseInt(option.items[i].is_selected) && !$$('body.checkout-cart-configure')[0]) {
                        var checked = $$('input[name="'+allRadios[j].name+'"]:checked')[0];
                        if (!checked || !checked.value || allRadios[j].type == 'checkbox') allRadios[j].checked = true;
                    }
                    if (option.items[i].image_src) {
                        var img = document.createElement('img');
                        if (this.getAppearance() == 'popup_configure' || this.getAppearance() == 'popup_cart') {
                            var img = document.createElement('div');
                            img.className = 'img_delayed_load';
                        }
                        img.className += ' itoris-dynamicoptions-thumbnail-image';
                        img.src = option.items[i].image_src;
                        allRadios[j].up().appendChild(img);
                        img.insert({after: this.createClearBothDiv()});
                        Event.observe(allRadios[j].up(), 'click', function(radio) {
                            radio.click();
                        }.bind(this, allRadios[j]));
                        hasImages = true;
                        if (option.items[i].base_img) {
                            var imgSrc = option.items[i].image_src;
                            Event.observe(allRadios[j], 'click', function() {
                                if (this.checked && jQuery('.fotorama-item')[0]) {
                                    jQuery('.fotorama-item').fotorama().data('fotorama').load([ {img: imgSrc, thumb: imgSrc} ]);
                                }
                            });
                        }
                        if (option.items[i].swatch) allRadios[j].up('div.choice').addClassName('dpo_swatch');
                    } else if (option.items[i].color) {
                        var img = document.createElement('div');
                        img.className = 'itoris-dynamicoptions-thumbnail-color';
                        img.style.background = option.items[i].color;
                        allRadios[j].up().appendChild(img);
                        img.insert({after: this.createClearBothDiv()});
                        Event.observe(img, 'click', function(radio) {
                            radio.click();
                        }.bind(this, allRadios[j]));
                        hasImages = true;
                        if (option.items[i].swatch) allRadios[j].up('div.choice').addClassName('dpo_swatch');
                    }
                    if (j == 1 && (option.items[i].image_src || option.items[i].color) && option.items[i].swatch && allRadios[0].value == "") {
                        var img = document.createElement('div');
                        img.className = 'itoris-dynamicoptions-thumbnail-color dpo-choice-none';
                        img.style.background = 'transparent';
                        allRadios[0].up().appendChild(img);
                        img.insert({after: this.createClearBothDiv()});
                        Event.observe(img, 'click', function(radio) {
                            radio.click();
                        }.bind(this, allRadios[0]));
                        hasImages = true;
                        if (option.items[i].swatch) allRadios[0].up('div.choice').addClassName('dpo_swatch');
                    }
                    if (parseInt(option.items[i].carriage_return)) {
                        carriageReturnElms.push(allRadios[j]);
                    }
                    if (option.items[i].css_class) {
                        allRadios[j].up('div.choice').addClassName(option.items[i].css_class);
                    }
                    option.items[i].elm = allRadios[j];
                    if (option.items[i].sku_is_product_id - 0 && !option.items[i].is_salable) {
                        var elmLabel = allRadios[j].up('div.choice').select('label')[0];
                        if (elmLabel) {
                            elmLabel.innerHTML += ' (' + this.config.out_of_stock_message + ')';
                        }
                        allRadios[j].disabled = true;
                        this.outOfStockElms.push(allRadios[j]);
                    }
                    allRadios[j].use_qty = option.items[i].use_qty;
                    allRadios[j].tier_price = option.items[i].tier_price;
                    if (option.items[i].use_qty) {
                        allRadios[j].qty_input_name = 'options_qty[' + option.id + ']';
                        if (option.type == 'checkbox') {
                            allRadios[j].qty_input_name += '[' + allRadios[j].value + ']';
                        }
                    }
                    optionsBlock.insert({bottom: allRadios[j].up()});
                    break;
                }
            }
        }
        if (carriageReturnElms.length) {
            for (var i = 0; i < carriageReturnElms.length; i++) {
                var nextElm = carriageReturnElms[i].up('div.choice').next();
                if (nextElm) {
                    nextElm.setStyle({clear: 'left'});
                }
            }
        }
        if (hasImages) {
            optionsBlock.addClassName('itoris-dynamicoptions-list-images');
        }
    },
    createClearBothDiv: function() {
        var div = document.createElement('div');
        div.style.clear = 'both';
        return div;
    },
    prepareDropdownOptions : function(field, option) {
        var dropdown = field.select('select')[0];
        var dropdownOptions = dropdown.select('option');
        if (option.type == 'drop_down' && option.default_select_title != '-- Please select --') {
            dropdownOptions[0].update(option.default_select_title);
        }
        dropdown.qty_values = [];
        dropdown.tier_values = [];
        dropdown.qty_input_name = 'options_qty[' + option.id + ']';
        for (var i = 0; i < option.items.length; i++) {
            for (var j = 0; j < dropdownOptions.length; j++) {
                if (dropdownOptions[j].value == option.items[i].option_type_id) {
                    if (parseInt(option.items[i].is_disabled)) {
                        dropdownOptions[j].remove();
                        continue;
                    } else if (parseInt(option.items[i].is_selected) && dropdown.selectedIndex == 0 && !$$('body.checkout-cart-configure')[0]) {
                        dropdownOptions[j].selected = true;
                    }
                    this.updateVisibility(option.items[i].visibility, dropdownOptions[j], option.items[i], true);
                    if (option.items[i].css_class) {
                        dropdownOptions[j].addClassName(option.items[i].css_class);
                    }
                    dropdownOptions[j].origTitle = option.items[i].title;
                    option.items[i].elm = dropdownOptions[j];
                    dropdown.insert({bottom: dropdownOptions[j]});

                    if (option.items[i].sku_is_product_id - 0 && !option.items[i].is_salable) {
                        dropdownOptions[j].innerHTML += ' (' + this.config.out_of_stock_message + ')';
                        dropdownOptions[j].disabled = true;
                        this.outOfStockElms.push(dropdownOptions[j]);
                    }
                    if (option.items[i].use_qty) {
                        dropdown.qty_values.push(dropdownOptions[j].value);
                    }
                    if (option.items[i].tier_price && option.items[i].tier_price.length > 0) {
                        dropdown.tier_values[dropdownOptions[j].value] = option.items[i].tier_price;
                    }
                    break;
                }
            }
        }
        jQuery(dropdown).trigger('change');
    },
    isSystemOption : function(option) {
        return !(option.type == 'image' || option.type == 'html');
    },
    getDynamicOptionField : function(id) {
        return $('dynamic_option_id_' + id);
    },
    getOrigOptionField : function(option) {
        var id = option.id;
        var type = option.type;
        var field = null;
        switch (type) {
            case 'field':
            case 'area':
                field = $('options_' + id + '_text');
                break;
            case 'date':
            case 'date_time':
                if ($('options_' + id + '_date')) {
                    var dateField = $('options_' + id + '_date');
                } else {
                    var dateField = $('options_' + id + '_month');
                }
                field = document.createElement('div');
                Element.extend(field);
                field.addClassName('control');
                if (this.config.form_style != 'list_div') {
                    if (dateField.tagName == 'INPUT') {
                        field.addClassName('date-ceil-picker');
                    } else {
                        field.addClassName('date-ceil');
                    }
                }
                if ($('options_' + id + '_hour') && $('options_' + id + '_hour').value == '') {
                    $('options_' + id + '_minute').value = '';
                }
                dateField.up().childElements().each(function(elm){field.appendChild(elm);});
                if (field.select('img').length) {
                    field.select('img')[0].addClassName('date-trig-icon');
                }
                this.decorateOption(field, option);
                return field;
            case 'time':
                var monthField = $('options_' + id + '_hour');
                if (monthField.value == '') {
                    $('options_' + id + '_minute').value = '';
                }
                field = document.createElement('div');
                Element.extend(field);
                if (this.config.form_style != 'list_div') {
                    field.addClassName('date-ceil');
                }
                field.addClassName('control');
                monthField.up().childElements().each(function(elm){field.appendChild(elm);});
                this.decorateOption(field, option);
                return field;
            case 'file':
                field = $$('input[name=options_' + id + '_file]')[0];
                this.decorateOption(field, option);
                var container = document.createElement('div');
                Element.extend(container);
                container.setStyle({clear:'both', minWidth: '180px'});
                this.wrapField(field);
                var deleteFileCheckbox = field.up('div.control').up().select('input[type=checkbox]')[0];
                if (deleteFileCheckbox) {
                    var deleteFileIcon = document.createElement('div');
                    Element.extend(deleteFileIcon);
                    deleteFileIcon.addClassName('delete-file-icon');
                    deleteFileCheckbox.insert({before:deleteFileIcon});
                    deleteFileCheckbox.hide();//setStyle({float: 'none', marginLeft: '5px'});
                    if (deleteFileIcon.next('span.label')) {
                        deleteFileIcon.next('span.label').remove();
                    }
                    deleteFileIcon.up().select('a').each(function(elm){elm.remove();});
                    deleteFileIcon.observe('click', function(){
                        deleteFileCheckbox.click();
                        deleteFileCheckbox.checked = true;
                        deleteFileIcon.up().select('span').each(function(elm){elm.remove();});
                        deleteFileIcon.up().next('.control').show();
                        deleteFileIcon.up().next('.control').select('input[type=file]')[0].disabled = false;
                        deleteFileIcon.remove();
                    });
                }
                field.up('div.control').up().childElements().each(function(elm){container.appendChild(elm)});
                return container;
            case 'drop_down':
            case 'multiple':
                field = $('select_' + id);
                break;
            case 'radio':
            case 'checkbox':
                field = $('options-' + id + '-list');
                break;
        }
        if (field) {
            field.config = option;
            if (option.default_value) {
                if (field.value != option.default_value) {
                    if (parseInt(option.hide_on_focus)) {
                        var opConfig = this.getOpConfig();
                        if (opConfig.config[option.id]) {
                            var priceConfig = opConfig.config[option.id];
                            delete opConfig.config[option.id];
                        } else {
                            var priceConfig = null;
                        }

                        Event.observe(field, 'focus', this.hideDefaultMessage.bind(this, field, option, priceConfig));
                        Event.observe(field, 'blur', this.showDefaultMessage.bind(this, field, option));
                        this.showDefaultMessage(field, option, priceConfig);
                    } else if (!this.config.is_configured) {
                        field.value = option.default_value;
                    }
                }
                option.option_field = field;
            }
            this.decorateOption(field, option);

            this.wrapField(field);
            return field.up('div.control');
        }
        return null;
    },
    wrapField: function(field) {
        if (!field.up('div.control') && field.up('div.field')) {
            var box = field.up('dd');
            var childElements = box.childElements();
            var wrapElm = document.createElement('div');
            Element.extend(wrapElm);
            wrapElm.className = 'control';
            box.appendChild(wrapElm);
            childElements.each(function(elm){
                wrapElm.appendChild(elm);
            });
        }
    },
    getOpConfig : function() {
        //if (this.config.is_grouped) {
        //    return eval('opConfig' + this.config.product_id);
        //}
        return {config: window['opConfig' + this.config.product_id]};

    },
    decorateOption : function(field, option) {
        if (option.comment) {
            var note = document.createElement('p');
            Element.extend(note);
            note.addClassName('no-margin');
            note.update(option.comment);
            if (!field.parentNode) {
                if (!field.select('.no-margin')[0]) field.insert({bottom: note});
            } else {
                if (!field.up().select('.no-margin')[0]) field.insert({after: note});
            }
        }
        //if (option.css_class) {
        //	field.addClassName(option.css_class);
        //}
        if (option.html_args) {
            this.writeAttributes(field, option.html_args);
        }
    },
    writeAttributes : function(elm, str) {
        var attribute = '';
        var attributeValue = '';
        var writeAttributeValue = false;
        for (var i = 0; i < str.length; i++) {
            switch (str[i]) {
                case '=':
                    if (writeAttributeValue) {
                        attributeValue += '=';
                    }
                    break;
                case '"':
                    writeAttributeValue = !writeAttributeValue;
                    if (!writeAttributeValue) {
                        this.writeAttribute(elm, attribute, attributeValue);
                        attribute = '';
                        attributeValue = '';
                    }
                    break;
                case ' ':
                    if (writeAttributeValue) {
                        attributeValue += ' ';
                    } else {
                        this.writeAttribute(elm, attribute, attributeValue);
                        attribute = '';
                        attributeValue = '';
                    }
                    break;
                default:
                    if (writeAttributeValue) {
                        attributeValue += str[i];
                    } else {
                        attribute += str[i];
                    }
            }
        }
    },
    writeAttribute : function(elm, attr, attrValue) {
        if (attr) {
            elm.setAttribute(attr, attrValue.length ? attrValue : attr);
        }
    },
    hideDefaultMessages : function() {
        for (var i = 0; i < this.options.length; i++) {
            if (this.options[i].option_field && this.options[i].default_value && parseInt(this.options[i].hide_on_focus) && this.options[i].default_value == this.options[i].option_field.value) {
                this.options[i].option_field.value = '';
                if (this.options[i].option_field.hasClassName('default-message')) {
                    this.options[i].option_field.removeClassName('default-message');
                }
            }
        }
    },
    showDefaultMessages : function() {
        for (var i = 0; i < this.options.length; i++) {
            if (this.options[i].option_field && this.options[i].default_value && parseInt(this.options[i].hide_on_focus) && !this.options[i].option_field.value) {
                if (!this.options[i].option_field.hasClassName('default-message')) {
                    this.options[i].option_field.addClassName('default-message');
                    this.options[i].option_field.value = this.options[i].default_value;
                }
            }
        }
    },
    scrollToValidationFailed: function(elm) {
        new Effect.ScrollTo(elm, {duration: 0.5});
        this.getDynamicOptionsBlock().select('.options-list.mage-error').each(function(elm) {
            elm.removeClassName('mage-error');
        });
    },
    hideDefaultMessage : function(field, option, priceConfig) {
        if (field.value == option.default_value) {
            field.value = '';
            if (field.hasClassName('default-message')) {
                field.removeClassName('default-message');
            }
            if (priceConfig) {
                this.getOpConfig().config[option.id] = priceConfig;
            }
        }
    },
    showDefaultMessage : function(field, option, priceConfig) {
        if (field.value == '') {
            var opConfig = this.getOpConfig();
            if (opConfig.config[option.id]) {
                delete opConfig.config[option.id];
            }
            field.value = option.default_value;
            if (!field.hasClassName('default-message')) {
                field.addClassName('default-message');
            }
        }
    },
    showPopup : function() {
        jQuery('.img_delayed_load').each(function(index, div){
            jQuery('<img>').attr({src: div.src}).insertAfter(div);
            jQuery(div).remove();
        });
        this.showDefaultMessages();
        $$('body')[0].appendChild(this.popup);
        this.popup.select('.product-options').each(function(el){el.hide();});
        this.popupMask.show();
        this.popup.show();
        if (this.getAppearance() == 'popup_cart') {
            $('itoris_dynamicproductoptions_popup_button_apply').hide();
            $('itoris_dynamicproductoptions_popup_button_apply').up().select('.or')[0].hide();
        }
        var _this = this;
        var topScrollOffset = document.viewport.getScrollOffsets()[1];
        var maxWidth = this.popup.getWidth() > document.viewport.getWidth() ? document.viewport.getWidth() : this.popup.getWidth();
        var beforeStyle = {height: this.popup.getHeight() + 'px', width: this.popup.getWidth() + 'px', marginLeft:-(maxWidth / 2) + 'px', top: (topScrollOffset + 75) + 'px'}
        this.popup.setStyle({width:0, height:0, marginLeft: 0, top: (topScrollOffset + document.viewport.getHeight()/2) + 'px'});
        jQuery(this.popup).animate(beforeStyle, 400, 'swing', function(effect) {$('itoris_dynamicproductoptions_popup').setStyle({height:'auto'}); _this.resizePopup();});
        if (!this.config.is_grouped) {
            $('itoris_dynamicoptions_qty').disabled = false;
            $('itoris_dynamicoptions_qty').value = this.getQtyField() ? this.getQtyField().value : '';
        }
        this.popup.select('input, textarea').each(function(el){ el._value = el.value; el._checked = el.checked; });
        this.popup.select('select option').each(function(el){ el._selected = el.selected; });
        $('itoris_dynamicproductoptions_popup_price').insert($$(this.priceBoxSelector)[0]);
        this.resizePopup();
    },
    resizePopup: function() {
        var beforeWidth = parseNumber(this.popup.style.width);
        if (isNaN(beforeWidth)) {
            return;
        }
        this.popup.setStyle({width: 'auto'});
        var topScrollOffset = document.viewport.getScrollOffsets()[1];
        var newWidth = this.popup.getWidth();
        if (beforeWidth == newWidth) {
            this.popup.setStyle({width:beforeWidth + 'px'});
            return;
        }
        var maxWidth = newWidth > document.viewport.getWidth() ? document.viewport.getWidth() : newWidth;
        var newStyles = {width: newWidth + 'px', marginLeft:-(maxWidth / 2) + 'px', top: (topScrollOffset + 75) + 'px'}
        //this.popup.setStyle({width:beforeWidth + 'px'});
        //jQuery(this.popup).animate(newStyles, 50, 'swing', function(effect) {$('itoris_dynamicproductoptions_popup').setStyle({height:'auto'});});
        this.popup.setStyle(newStyles);
        this.popup.setStyle({height:'auto'});
    },
    correctPopupPosition: function() {
        this.resizePopup();
        if (this.popup.visible()) {
            var width = parseNumber(this.popup.style.width);
            if (!isNaN(width)) {
                var viewportWidth = document.viewport.getWidth();
                var marginLeft = parseNumber(this.popup.getStyle('marginLeft'));
                if (viewportWidth > width) {
                    if (marginLeft != - width / 2) {
                        this.popup.setStyle({marginLeft: - (width / 2) + 'px'})
                    }
                } else {
                    if (marginLeft != - viewportWidth / 2) {
                        this.popup.setStyle({marginLeft: - (viewportWidth / 2) + 'px'})
                    }
                }
            }
        }
    },
    showGroupedPopup: function() {
        var canShow = false;
        this.groupProducts.each(function(obj){
            if (obj.getAppearance() == 'popup_cart') {
                if (obj.getGroupProductQty()) {
                    $('itoris_dynamicproductoptions_popup' + obj.config.product_id).show();
                    canShow = true;
                } else {
                    $('itoris_dynamicproductoptions_popup' + obj.config.product_id).hide();
                }
            }
        });
        if (canShow) {
            this.groupProducts.each(function(obj){obj.showDefaultMessages();});
            $$('body')[0].appendChild(this.popup);
            this.popupMask.show();
            this.popup.show();
            var topScrollOffset = document.viewport.getScrollOffsets()[1];
            var beforeStyle = 'height:' + this.popup.getHeight() + 'px;width:' + this.popup.getWidth() + 'px;margin-left:-' + (this.popup.getWidth() / 2) + 'px;top:' + (topScrollOffset + 75) + 'px;';
            this.popup.setStyle({width:0, height:0, marginLeft: 0, top: (topScrollOffset + document.viewport.getHeight()/2) + 'px'});
            new Effect.Morph(this.popup, {
                style: beforeStyle,
                duration: 0.5,
                afterFinishInternal : function(effect) {
                    effect.element.setStyle({height:'auto'});
                }
            });
        } else {
            this.addToCartFromPopupGrouped();
        }
    },
    cancelPopup : function() {
        this.popup.select('input, textarea').each(function(el){
            if (el.type == 'radio' || el.type == 'checkbox') el.checked = el._checked;
            else if (el.type != 'file') el.value = el._value;

        });
        this.popup.select('select option').each(function(el){ el.selected = el._selected; });
        this.checkVisibilityConditions();
        this.updateAllPrices();
        this.hidePopup();
    },
    hidePopup : function() {
        this.popupMask.hide();
        this.popup.hide();
        this.productForm.appendChild(this.popup);
        $$('.product-info-main .product-info-price')[0].insert({top: $$(this.priceBoxSelector)[0]});
    },
    showAddToCartPopup : function(ev) {
        this.removeItorisValidationAdvices();
        if (this.addToCartButton.onclick) {
            //this.addToCartButton.onclick = null;
            var skipValidationOptions = [];
            this.productForm.select('input,textarea,select').each(function(elm){
                if (elm.disabled && elm.hasClassName('required-entry')) {
                    elm.removeClassName('required-entry');
                    skipValidationOptions.push(elm);
                }
            });
            setTimeout(function(){
                skipValidationOptions.each(function(elm){
                    elm.addClassName('required-entry');
                });
                this.showDefaultMessages();
                this.addToCartButton.onclick = null;
                //reset focus from required field, default value issue
                this.addToCartButton.focus();
                if ($$('.mage-error')[0]) {
                    this.scrollToValidationFailed($$('.mage-error')[0]);
                }
            }.bind(this), 100);
        } else {
            if (this.getAppearance() == 'popup_cart') {
                this.showPopup();
            } else {
                ev.stop();
                this.hideDefaultMessages();
                /*if (typeof this.addToCartButton.callback == 'function') this.addToCartButton.callback(ev); else {
                    this.addToCartButton.onclick = this.addToCartButton.callback;
                    //this.addToCartButton.click();
                    //jQuery('#product_addtocart_form')[0].submit();
                    var form = '#product_addtocart_form';
                    var widget = jQuery(form).catalogAddToCart({
                        bindSubmit: false
                    });
                    widget.catalogAddToCart('submitForm', jQuery(form));
                    //this.productForm.submit();
                }*/
            }
        }
    },
    showAddToCartPopupGrouped: function(ev) {
        this.removeItorisValidationAdvices();

        if (this.addToCartButton.onclick) {
            //this.addToCartButton.onclick = null;
            var skipValidationOptions = [];
            this.productForm.select('input,textarea,select').each(function(elm){
                if (elm.disabled && elm.hasClassName('required-entry')) {
                    elm.removeClassName('required-entry');
                    skipValidationOptions.push(elm);
                }
            });
            setTimeout(function(){
                skipValidationOptions.each(function(elm){
                    elm.addClassName('required-entry');
                });
                this.groupProducts.each(function(obj){obj.showDefaultMessages();});
                //this.addToCartButton.onclick = null;
                //reset focus from required field, default value issue
                this.addToCartButton.focus();
                if ($$('.mage-error')[0]) {
                    this.scrollToValidationFailed($$('.mage-error')[0]);
                }
            }.bind(this), 100);
        } else {
            var showPopupCart = false;
            this.groupProducts.each(function(obj){
                if (obj.getAppearance() == 'popup_cart' && obj.getGroupProductQty()) {
                    showPopupCart = true;
                }
            });
            if (showPopupCart) {
                this.showGroupedPopup();
            } else {
                ev.stop();
                var hasPopups = false;
                this.groupProducts.each(function(obj){
                    obj.hideDefaultMessages();
                    if (obj.getAppearance() == 'popup_configure') {
                        if (obj.getGroupProductQty()) {
                            obj.productForm.appendChild(obj.popup);
                            obj.popup.show();
                            hasPopups = true;
                        }
                    }
                });
                if (hasPopups) {
                    var isValid = this.validateForm();
                    this.groupProducts.each(function(obj){
                        if (obj.getAppearance() == 'popup_configure') {
                            obj.popup.hide();
                            if (obj.popup.select('.validation-advice')[0]) {
                                obj.addGroupedPopupValidationAdvice();
                            }
                        }
                    });
                    if (!isValid) {
                        this.groupProducts.each(function(obj){
                            obj.showDefaultMessages();
                        });
                        return;
                    }
                }
                //this.addToCartButton.onclick = this.addToCartButton.callback;
                this.addToCartButton.click();
            }
        }
    },
    addGroupedPopupValidationAdvice: function() {
        var popupAdvice = document.createElement('span');
        popupAdvice.className = 'validation-advice itoris-validation-advice';
        popupAdvice.update(this.config.configure_product_message);
        $('itoris_dynamicproductoptions_configuration' + this.config.product_id).appendChild(popupAdvice);
    },
    getGroupProductQty: function() {
        var qtyElms = this.productForm.select('.itoris_input_qty');
        var qtyValue = 0;
        for (var i = 0; i < qtyElms.length; i++) {
            if (qtyElms[i].name == 'super_group[' + this.config.product_id + ']') {
                if (qtyElms[i].value && !isNaN(qtyElms[i].value)) {
                    qtyValue = parseNumber(qtyElms[i].value);
                }
                break;
            }
        }
        return qtyValue;
    },
    validateForm: function() {
        var formValidation = new Validation(this.productForm);
        var skipValidationOptions = [];
        this.productForm.select('input,textarea,select').each(function(elm){
            if (elm.disabled && elm.hasClassName('required-entry')) {
                elm.removeClassName('required-entry');
                skipValidationOptions.push(elm);
            }
        });
        var result = formValidation.validate();

        skipValidationOptions.each(function(elm){
            elm.addClassName('required-entry');
        });

        return result;
    },
    addToCartFromPopup : function() {
        this.productForm.appendChild(this.popup);
        this.hideDefaultMessages();
        this.removeItorisValidationAdvices();
        this.showDefaultMessages();
        this.addToCartButton.click();
        //jQuery(this.addToCartButton).trigger('click');
        $$('body')[0].appendChild(this.popup);
        var _obj = this;
        if (this.addToCartButton.hasClassName('disabled')) {
            var btn = $('itoris_dynamicoptions_add_to_cart');
            btn.addClassName('disabled');
            btn._title = btn.select('span span')[0].innerHTML;
            btn.select('span span')[0].innerHTML = this.addToCartButton.title;
            btn.checkState = function(){
                if (!_obj.addToCartButton.hasClassName('disabled')) {
                    btn.select('span span')[0].innerHTML = btn._title;
                    btn.removeClassName('disabled');
                    setTimeout(function(){_obj.hidePopup()}, 1000);
                    return;
                }
                setTimeout(function(){btn.checkState();}, 100);
            }
            btn.checkState();
        }
    },
    addToCartFromPopupGrouped : function() {
        var addToCartButton = this.groupProducts[0].addToCartButton;
        addToCartButton.onclick = addToCartButton.callback;
        this.productForm.appendChild(this.popup);

        this.groupProducts.each(function(obj){
            obj.hideDefaultMessages();
            if (obj.getAppearance() == 'popup_configure' && obj.getGroupProductQty()) {
                obj.productForm.appendChild(obj.popup);
                obj.popup.show();
            }
        });

        this.removeItorisValidationAdvices();
        addToCartButton.click();

        this.groupProducts.each(function(obj){
            obj.showDefaultMessages();
            if (obj.getAppearance() == 'popup_configure' && obj.getGroupProductQty()) {
                obj.popup.hide();
                if (obj.popup.select('.mage-error')[0]) {
                    obj.addGroupedPopupValidationAdvice();
                }
            }
        });

        addToCartButton.onclick = null;
        if (this.productForm.select('.validation-advice').length && !this.popup.select('.validation-advice').length) {
            this.hidePopup();
        }
        $$('body')[0].appendChild(this.popup);
    },
    applyPopupConfig : function() {
        this.productForm.appendChild(this.popup);
        var formValidation = new Validation(this.productForm);
        this.hideDefaultMessages();
        this.removeItorisValidationAdvices();
        var skipValidationOptions = [];
        this.productForm.select('input,textarea,select').each(function(elm){
            if (elm.disabled && elm.hasClassName('required-entry')) {
                elm.removeClassName('required-entry');
                skipValidationOptions.push(elm);
            }
        });
        if (formValidation.validate()) {
            this.hidePopup();
            if (!this.isGrouped()) {
                this.getQtyField().value = $('itoris_dynamicoptions_qty').value;
            }
            this.showConfiguration();
        } else {
            this.showDefaultMessages();
            $$('body')[0].appendChild(this.popup);
            if (this.popup.select('.mage-error')[0]) {
                this.scrollToValidationFailed(this.popup.select('.mage-error')[0]);
            } else if (this.isGrouped() && this.productForm.select('.mage-error')[0]) {
                this.hidePopup();
                this.showConfiguration();
            }
        }
        setTimeout(function(){
            skipValidationOptions.each(function(elm){
                elm.addClassName('required-entry');
            });
        }.bind(this), 100);
    },
    showConfiguration : function() {
        if (this.isGrouped()) {
            var config = $('itoris_dynamicproductoptions_configuration' +  this.config.product_id).select('ul')[0];
        } else {
            if (!this.addToCartBlock) {
                return;
            }
            this.addToCartBlock.show();
            $$('#product_addtocart_form .product-options-bottom')[0].show();
            $('itoris_dynamicoptions_qty').disabled = true;
            this.buttonConfigure.hide();
            $('itoris_dynamicproductoptions_add_to_cart_configure').insert({before: $('itoris_dynamicproductoptions_configuration')});
            $('itoris_dynamicproductoptions_configuration').show();
            var config = $('itoris_dynamicproductoptions_configuration').select('ul')[0];
        }
        config.update();
        this.canShowConfiguration = false;
        if (this.getDynamicOptionsBlock().select('dl.product-options')[0]) {
            if (this.config.product_type == 'bundle') {
                this.prepareBundleConfiguration(config, this.getDynamicOptionsBlock().select('dl.product-options')[0]);
            } else {
                var configurableTitles = this.getDynamicOptionsBlock().select('dl.product-options')[0].select('label');
                var configurableValueElms = this.getDynamicOptionsBlock().select('dl.product-options')[0].select('select');
                for (var i = 0; i < configurableValueElms.length; i++) {
                    if (configurableTitles[i] && configurableValueElms[i].value != '') {
                        config.appendChild(this.prepareOptionValueConfig(configurableTitles[i].getInnerText().replace(/^\*/, ''), configurableValueElms[i].options[configurableValueElms[i].selectedIndex].innerHTML));
                    }
                }
            }
        }
        for (var i = 0; i < this.options.length; i++) {
            if (this.isSystemOption(this.options[i])) {
                var value = this.getOptionValue(this.options[i], true);
                if (this.options[i].default_value && this.options[i].default_value == value && parseInt(this.options[i].hide_on_focus)) {
                    continue;
                }
                if ($('dynamic_option_id_' + this.options[i].id) && $('dynamic_option_id_' + this.options[i].id).up('div') && $('dynamic_option_id_' + this.options[i].id).up('div').hasClassName('fieldset-section')) {
                    if ($('dynamic_option_id_' + this.options[i].id).up('div').getStyle('display') == 'none') {
                        continue;
                    }
                }
                if (value && value.length) {
                    var optionConfig = this.prepareOptionValueConfig(this.options[i].title, value);
                    config.appendChild(optionConfig);
                }
            }
        }
        if (!this.canShowConfiguration) {
            this.hideConfiguration();
        }
    },
    prepareBundleConfiguration: function(config, optionsBlock) {
        var labels = optionsBlock.select('dt');
        var values = optionsBlock.select('dd');
        for (var i = 0; i < labels.length; i++) {
            if (values[i]) {
                try {
                    var title = this.simpleStripTags(labels[i].select('label')[0].innerHTML);
                    var value = null;
                    var qty = values[i].select('.qty-holder input')[0] ? values[i].select('.qty-holder input')[0].value : 1;
                    if (values[i].select('.control select')[0]) {
                        var selectBox = values[i].select('.control select')[0];
                        if (selectBox.multiple) {
                            value = [];
                            var options = selectBox.select('option');
                            for (var j = 0; j < options.length; j++) {
                                if (options[j].selected) {
                                    value.push({title: this.simpleStripTags(options[j].innerHTML)});
                                }
                            }
                        } else if (selectBox.value != '') {
                            value = this.simpleStripTags(selectBox.options[selectBox.selectedIndex].innerHTML);
                        }

                    } else if (values[i].select('.control input[type=radio]')[0]) {
                        var radios = values[i].select('.control input[type=radio]');
                        for (var j = 0; j < radios.length; j++) {
                            if (radios[j].checked) {
                                value = this.simpleStripTags(radios[j].next('.label').select('label')[0].innerHTML);
                                break;
                            }
                        }
                    } else if (values[i].select('.control input[type=checkbox]')[0]) {
                        var checkboxes = values[i].select('.control input[type=checkbox]');
                        value = [];
                        for (var j = 0; j < checkboxes.length; j++) {
                            if (checkboxes[j].checked) {
                                value.push({title: this.simpleStripTags(checkboxes[j].next('.label').select('label')[0].innerHTML)});
                            }
                        }
                    } else {
                        value = this.simpleStripTags(values[i].select('.control')[0].innerHTML);
                    }
                    if (value && !(value instanceof Array)) {
                        if (qty > 1) {
                            value = qty + ' x ' + value;
                        }
                    }
                    config.appendChild(this.prepareOptionValueConfig(title, value));
                } catch (e) {/** prevent unexpected errors **/}
            }
        }
    },
    simpleStripTags: function(str) {
        return str.replace(/<[^>]*>.*<[^>]*>/g, '');
    },
    hideConfiguration: function() {
        if (this.isGrouped()) {
            return;
        }
        this.buttonConfigure.show();
        if (!this.config.is_configured) {
            this.addToCartBlock.hide();
        }
        if (this.config.is_configured && !this.buttonConfigure.hasClassName('configure-button-update-page')) {
            this.addToCartBlock.insert({before:this.buttonConfigure});
            this.buttonConfigure.addClassName('configure-button-update-page');
        }
        $('itoris_dynamicproductoptions_configuration').hide();
    },
    prepareOptionValueConfig : function(optionTitle, value){
        var optionConfig = document.createElement('li');
        Element.extend(optionConfig);

        if (value instanceof Array) {
            if (value.length) {
                this.canShowConfiguration = true;
                optionConfig.update('<strong>' + optionTitle + ':</strong> ');
                var valuesBox = document.createElement('div');
                Element.extend(valuesBox);
                valuesBox.addClassName('dynamicoptions-radio-checkbox-values');
                optionConfig.appendChild(valuesBox);
                for (var j = 0; j < value.length; j++) {
                    var itemValueElm = document.createElement('span');
                    Element.extend(itemValueElm);
                    if (value[j].title) {
                        itemValueElm.update(value[j].title + '<br/>');
                    }
                    if (value[j].image_src) {
                        var itemValueImg = document.createElement('img');
                        itemValueImg.src = value[j].image_src;
                        itemValueImg.title = value[j].title;
                        itemValueElm.appendChild(itemValueImg);
                    }
                    valuesBox.appendChild(itemValueElm);
                }
            }
        } else {
            if (value) {
                this.canShowConfiguration = true;
                optionConfig.update('<strong>' + optionTitle + ':</strong> ' + value);
            }
        }
        return optionConfig;
    },
	getOptionValue : function(option, withImages, asArray, skipQty) {
		if (option) switch (option.type) {
			case 'field':
			case 'area':
				return $('options_' + option.id + '_text').value;
				break;
			case 'date':
			case 'date_time':
				if ($('options_' + option.id + '_date')) {
					var value =  $('options_' + option.id + '_date').value;
					value += this.getTimeOptionValue(option.id);
					return value;
				} else {
					return this.getDateOptionValue(option.id);
				}
			case 'time':
				return this.getTimeOptionValue(option.id);
			case 'file':
				var fileName = $$('input[name=options_' + option.id + '_file]')[0].disabled ? '' : $$('input[name=options_' + option.id + '_file]')[0].value;
				if (!fileName) {
					var selectedFile = $$('.options_' + option.id + '_file_name')[0];
					if (selectedFile) {
						return selectedFile.innerHTML;
					}
				}
				return fileName;
			case 'drop_down':
			case 'multiple':
				var field = $('select_' + option.id), qtyElm = $('select_' + option.id + '_qty');
				var options = field.select('option');
				var value = [], qty = false;
				for (var i = 0; i < options.length; i++) {
					if (options[i].value && options[i].selected) {
						value.push((qtyElm && !qtyElm.hasClassName('use_product_qty') && !skipQty ? qtyElm.value + ' x ' : '') + options[i].origTitle);
					}
				}
				if (asArray) {
					return value;
				}
				return value.join(',');
			case 'radio':
			case 'checkbox':
				var field = $('options-' + option.id + '-list');
				var options = field.select('input');
				var valueStr = [];
				for (var i = 0; i < options.length; i++) {
					if (options[i].checked) {
						for (var j = 0; j < option.items.length; j++) {
							if (options[i].value == option.items[j].option_type_id) {
                                var qtyElm = $$('input[name="options_qty['+option.items[j].option_id+']['+option.items[j].option_type_id+']"]')[0];
								if (withImages) {
									valueStr.push({
										image_src: option.items[j].image_src,
										title: (qtyElm && !qtyElm.hasClassName('use_product_qty') && !skipQty ? qtyElm.value + ' x ' : '') + option.items[j].title
									});
								} else {
									valueStr.push(option.items[j].title);
								}
								break;
							}
						}
					}
				}
				if (withImages || asArray) {
					return valueStr;
				}
				return valueStr.join(',');
		}
		return null;
	},
    getDateOptionValue : function(optionId) {
        var value = '';
        if ($('options_' + optionId + '_month')
            && $('options_' + optionId + '_day').value != ''
            && $('options_' + optionId + '_month').value != ''
            && $('options_' + optionId + '_year').value != ''
        ) {
            var month = $('options_' + optionId + '_month').value;
            var day = $('options_' + optionId + '_day').value;
            var year = $('options_' + optionId + '_year').value;
            value = this.dateFormat.replace('m', month).replace('d', day).replace('y', year);
            value += this.getTimeOptionValue(optionId);
        }
        return value;
    },
    getTimeOptionValue : function(optionId) {
        var value = '';
        if ($('options_' + optionId + '_hour') && $('options_' + optionId + '_hour').value != '' && $('options_' + optionId + '_minute').value != '') {
            value += ' ' + this.getSelectedOptionLabel($('options_' + optionId + '_hour'), $('options_' + optionId + '_hour').value)
            + ':' + this.getSelectedOptionLabel($('options_' + optionId + '_minute'), $('options_' + optionId + '_minute').value);
            if ($('options_' + optionId + '_day_part')) {
                value += ' ' + this.getSelectedOptionLabel($('options_' + optionId + '_day_part'), $('options_' + optionId + '_day_part').value);
            }
        }
        return value;
    },
    getSelectedOptionLabel : function(dropdown, optionValue) {
        var options = dropdown.select('option');
        for (var i = 0; i < options.length; i++) {
            if (options[i].value == optionValue) {
                return options[i].text;
            }
        }
    },
    changeOpFileInitialization : function() {
        for (var key in window) {
            if (key.indexOf('opFile') == 0) {
                var initFunction = window[key].initializeFile.toString()
                window[key].initializeFile = eval('(' + initFunction.replace('.up(\'dd\')', '.up(\'div\')') + ')');
            }
        }
    },
    showFieldError : function(optionId, message) {
        var option = this.getOption(optionId);
        if (option) {
            var fieldBox = this.getDynamicOptionField(optionId);
            var errorElm = null;
            switch (option.type) {
                case 'field':
                case 'file':
                    errorElm = fieldBox.select('input')[0];
                    break;
                case 'area':
                    errorElm = fieldBox.select('textarea')[0];
                    break;
                case 'drop_down':
                case 'multiple':
                    errorElm = fieldBox.select('select')[0];
                    break;
                case 'radio':
                case 'checkbox':
                    errorElm = fieldBox.select('.options-list')[0];
                    break;
                case 'date':
                case 'date_time':
                case 'time':
                default:
                    errorElm = fieldBox.select('.control')[0];
            }
            if (errorElm) {
                errorElm.addClassName('mage-error');
                var errorNote = document.createElement('div');
                errorNote.className = 'validation-advice itoris-validation-advice';
                errorNote.innerHTML = message;
                errorElm.insert({after:errorNote});
            }
        }
    },
    getOption : function(id) {
        for (var i = 0; i < this.options.length; i++) {
            if (id == this.options[i].id) {
                return this.options[i];
            }
        }
        return null;
    },
    removeItorisValidationAdvices : function() {
        $$('.itoris-validation-advice').each(function(elm){elm.remove();});
    },
    isGrouped: function() {
        return this.config.is_grouped;
    },
    getDynamicOptionsBlock: function() {
        return this.isGrouped() ? $('itoris_dynamicproductoptions' + this.config.product_id) : $('itoris_dynamicproductoptions');
    },
    getPriceBox: function(){
        return jQuery(this.priceBoxSelector);
    },
    getQtyField: function(){
        return $$('#qty, #options-' + this.config.product_id)[0];
    },
    verifyNumeric: function(value) {
        return (!isNaN(parseFloat(value)) && isFinite(value)) ? value-0 : value;
    },
    getTranslation: function(str) {
        return this.translations[str] ? this.translations[str] : str;
    },
    negativePriceCheck: function() {
        var obj = this;
        if (!jQuery.mage.priceOptions) {
            setTimeout(function(){obj.negativePriceCheck()}, 200);
            return;
        }
        if (typeof this.config.options_qty.length != 'number') {
            obj.updateAllPrices(); obj.presetOptionsQty(); obj.updateAllPrices();
        }
        jQuery('#itoris_dynamicproductoptions select option').each(function(index, option){
            if (option.innerHTML.indexOf('+-') > -1) option.innerHTML = option.innerHTML.replace('+-', '-');
        });
        if (jQuery('#qty').val() > 1) jQuery('#qty').trigger('change');
    }
    
};