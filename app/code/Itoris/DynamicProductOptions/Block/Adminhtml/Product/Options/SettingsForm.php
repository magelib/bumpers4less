<?php
/**
 * ITORIS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the ITORIS's Magento Extensions License Agreement
 * which is available through the world-wide-web at this URL:
 * http://www.itoris.com/magento-extensions-license.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to sales@itoris.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extensions to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to the license agreement or contact sales@itoris.com for more information.
 *
 * @category   ITORIS
 * @package    ITORIS_M2_DYNAMIC_PRODUCT_OPTIONS
 * @copyright  Copyright (c) 2016 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

/**
 * @method setOptionsConfig()
 * @method \Itoris\DynamicProductOptions\Model\Options getOptionsConfig()
 */
//app/code/Itoris/DynamicProductOptions/Block/Adminhtml/Product/Options/SettingsForm.php
namespace Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options;

class SettingsForm extends \Magento\Backend\Block\Widget\Form\Generic
{
    protected function _prepareForm() {
        $form = $this->_formFactory->create();

        $configurationFieldset = $form->addFieldset('itoris_dynamicproductoptions_configuration_fieldset', ['legend' => $this->escapeHtml(__('Product Options Settings'))]);

        $configurationFieldset->addField('itoris-dynamicproductoptions-form-style', 'select', [
            'label'  => $this->escapeHtml(__('Form Style')),
            'title'  => $this->escapeHtml(__('Form Style')),
            'name'   => 'itoris_dynamicproductoptions[form_style]',
            'values' => [
                [
                    'label' => $this->escapeHtml(__('List DIV-based')),
                    'value' => 'list_div',
                ],
                [
                    'label' => $this->escapeHtml(__('Table-based')),
                    'value' => 'table',
                ],
                [
                    'label' => $this->escapeHtml(__('Table-based with sections')),
                    'value' => 'table_sections',
                ],
            ],
            'value' => $this->getOptionsConfig()->getFormStyle(),
            'data-form-part' => 'product_form',
            'style' => 'width:300px'
        ]);

        $configurationFieldset->addField('itoris-dynamicproductoptions-appearance', 'select', [
            'label'  => $this->escapeHtml(__('Appearance')),
            'title'  => $this->escapeHtml(__('Appearance')),
            'name'   => 'itoris_dynamicproductoptions[appearance]',
            'values' => [
                [
                    'label' => $this->escapeHtml(__('On Product View')),
                    'value' => 'on_product_view',
                ],
                [
                    'label' => __('In a Popup after clicking "Add to Cart"'),
                    'value' => 'popup_cart',
                ],
                [
                    'label' => __('In a Popup after clicking "Configure"'),
                    'value' => 'popup_configure',
                ],
            ],
            'value' => $this->getOptionsConfig()->getAppearance(),
            'data-form-part' => 'product_form',
            'style' => 'width:300px'
        ]);
        
        $configurationFieldset->addField('itoris-dynamicproductoptions-pricing', 'select', [
            'label'  => $this->escapeHtml(__('Pricing')),
            'title'  => $this->escapeHtml(__('Pricing')),
            'name'   => 'itoris_dynamicproductoptions[absolute_pricing]',
            'values' => [
                [
                    'label' => __('Relative'),
                    'value' => 0,
                ],
                [
                    'label' => __('Absolute'),
                    'value' => 1,
                ],
                [
                    'label' => __('Fixed'),
                    'value' => 2,
                ]
            ],
            'value' => $this->getOptionsConfig()->getAbsolutePricing(),
            'data-form-part' => 'product_form',
            'style' => 'width:200px'
        ]);
        
        $configurationFieldset->addField('itoris-dynamicproductoptions-sku', 'select', [
            'label'  => $this->escapeHtml(__('SKU')),
            'title'  => $this->escapeHtml(__('SKU')),
            'name'   => 'itoris_dynamicproductoptions[absolute_sku]',
            'values' => [
                [
                    'label' => __('Relative'),
                    'value' => 0,
                ],
                [
                    'label' => __('Absolute'),
                    'value' => 1,
                ],
                [
                    'label' => __('Fixed'),
                    'value' => 2,
                ]
            ],
            'value' => $this->getOptionsConfig()->getAbsoluteSku(),
            'data-form-part' => 'product_form',
            'style' => 'width:200px'
        ]);
        
        $configurationFieldset->addField('itoris-dynamicproductoptions-weight', 'select', [
            'label'  => $this->escapeHtml(__('Weight')),
            'title'  => $this->escapeHtml(__('Weight')),
            'name'   => 'itoris_dynamicproductoptions[absolute_weight]',
            'values' => [
                [
                    'label' => __('Relative'),
                    'value' => 0,
                ],
                [
                    'label' => __('Absolute'),
                    'value' => 1,
                ],
                [
                    'label' => __('Fixed'),
                    'value' => 2,
                ]
            ],
            'value' => $this->getOptionsConfig()->getAbsoluteWeight(),
            'data-form-part' => 'product_form',
            'style' => 'width:200px'
        ]);
        $this->setForm($form);

        return $this;
    }
}