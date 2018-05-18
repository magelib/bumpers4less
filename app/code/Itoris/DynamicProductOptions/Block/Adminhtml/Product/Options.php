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

//app/code/Itoris/DynamicProductOptions/Block/Adminhtml/Product/Options.php
namespace Itoris\DynamicProductOptions\Block\Adminhtml\Product;

class Options extends \Magento\Backend\Block\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    protected $optionsConfig = null;

    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        $this->_coreRegistry = $registry;
        $this->_objectManager = $objectManager;
        $this->setTemplate('product/options.phtml');
        parent::__construct($context, $data);
    }

    /**
     * @return \Itoris\DynamicProductOptions\Model\Options
     */
    public function getOptionsConfig() {
        if (is_null($this->optionsConfig)) {
            if ($this->_coreRegistry->registry('current_template')) {
                $this->optionsConfig = $this->_coreRegistry->registry('current_template');
                $this->optionsConfig['use_global'] = $this->optionsConfig->getStoreId() == $this->getStoreId() ? false : true;
            } else {
                $this->optionsConfig = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Options')
                    ->setStoreId($this->getStoreId())
                    ->load($this->getProductId());

                //if there is no store config need to load from the default store
                $this->optionsConfig['use_global'] = false;
                if (!$this->optionsConfig->getData('form_style')) {
                    $this->optionsConfig = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Options')
                        ->setStoreId(0)
                        ->load($this->getProductId());
                    //$this->optionsConfig['store_id'] = $this->getStoreId();
                    $this->optionsConfig['use_global'] = true;
                }
            }
            $this->optionsConfig['has_config'] = !!$this->optionsConfig->getData('form_style');
        }
        return $this->optionsConfig;
    }

    public function getStoreId() {
        return (int)$this->_request->getParam('store');
    }
    /** @return \Magento\Store\Model\Store  */
    public function getStore(){
        /** @var  $storeInterface \Magento\Store\Model\StoreManagerInterface*/
        $storeInterface = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
        return $storeInterface->getStore($this->getStoreId());
    }

    public function getProductId() {
        if ($this->_coreRegistry->registry('current_product')) return $this->_coreRegistry->registry('current_product')->getId();
        return (int) $this->getRequest()->getParam('product');
    }

    public function isHasStoreConfig() {
        $this->getOptionsConfig();
        return $this->optionsConfig['has_config'];
    }

    public function isNeedUseGlobal() {
        return $this->optionsConfig['use_global'];
    }

    /*protected function _prepareLayout() {
        $head = $this->getLayout()->getBlock('head');
        if ($head) {
            $head->addJs('options.js');
        }
        $settingsForm = $this->getLayout()->createBlock('Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options\SettingsForm');
        $settingsForm->setOptionsConfig($this->getOptionsConfig());
        $this->setChild('settings_form', $settingsForm);

        return parent::_prepareLayout();
    }*/

    public function getSettingsFormHtml() {
        $block = $this->_objectManager->create('Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options\SettingsForm');
        $block->setOptionsConfig($this->getOptionsConfig());
        return $block->toHtml();
        //return $this->getChildHtml('settings_form');
    }

    public function getDynamicOptionsJsObjectConfig() {
        $config = [
            'translates' => $this->getTranslates(),
            'calendar_url'   => $this->getViewFileUrl('Itoris_DynamicProductOptions::images/calendar.gif'),
            'upload_image_url' => $this->getUrl('dynamicproductoptions/product_options_options_image/upload'),
            'validation_types' => $this->getValidationTypes(),
            'field_types'      => $this->getFieldTypes(),
            'price_types'      => $this->getPriceTypes(),
            'form_styles' => [
                'list_div'       => $this->escapeHtml(__('List DIV-based')),
                'table'          => $this->escapeHtml(__('Table-based')),
                'table_sections' => $this->escapeHtml(__('Table-based with sections')),
            ],
            'product_grid_url' => $this->getUrl('dynamicproductoptions/product_options/productGrid'),
            'template_save_url' => $this->getUrl('dynamicproductoptions/product_options_template/save'),
            'store_id' => $this->getStoreId(),
            'product_id' => $this->getProductId(),
            'use_global' => $this->isNeedUseGlobal()
        ];
        return $config;
    }

    public function getPriceTypes() {
        return [
            // for compatibility with old js object
            'fixed'   => 'fixed',
            'percent' => 'percent',
        ];
    }

    public function getFieldTypes() {
        return [
            'field' => [
                'title' => $this->escapeHtml(__('Input Box')),
            ],
            'area' => [
                'title' => $this->escapeHtml(__('Textarea')),
            ],
            'file' => [
                'title' => $this->escapeHtml(__('File')),
            ],
            'drop_down' => [
                'title' => $this->escapeHtml(__('Dropdown')),
            ],
            'radio' => [
                'title' => $this->escapeHtml(__('Radio Buttons')),
            ],
            'checkbox' => [
                'title' => $this->escapeHtml(__('Check Boxes')),
            ],
            'multiple' => [
                'title' => $this->escapeHtml(__('Multiple Select')),
            ],
            'date' => [
                'title' => $this->escapeHtml(__('Date')),
            ],
            'date_time' => [
                'title' => $this->escapeHtml(__('Date & Time')),
            ],
            'time' => [
                'title' => $this->escapeHtml(__('Time')),
            ],
            'image' => [
                'title' => $this->escapeHtml(__('Image')),
            ],
            'html' => [
                'title' => $this->escapeHtml(__('DIV/HTML Text')),
            ],
        ];
    }

    public function getValidationTypes($withLabels = false) {
        $types = [
            'email'    => $withLabels ? $this->escapeHtml(__('Email')) : 'validate-email',
            'number'   => $withLabels ? $this->escapeHtml(__('Number')) : 'validate-digits',
            'money'    => $withLabels ? $this->escapeHtml(__('Money')) : 'validate-money',
            'phone'    => $withLabels ? $this->escapeHtml(__('Phone')) : 'validate-phone-number',
            //'date'     => $withLabels ? $this->escapeHtml(__('Date')) : 'validate-date',
            'zip'      => $withLabels ? $this->escapeHtml(__('Zip Code')) : 'validate-zip',
        ];
        if (!$withLabels) {
            $types['please_select'] = 0;
        }
        return $types;
    }

    public function getVisibilityOptions() {
        return [
            [
                'label' => $this->escapeHtml(__('Visible')),
                'value' => 'visible',
            ],
            [
                'label' => $this->escapeHtml(__('Hidden')),
                'value' => 'hidden',
            ],
            [
                'label' => $this->escapeHtml(__('Disabled')),
                'value' => 'disabled',
            ]
        ];
    }

    protected function getTranslates() {
        return [
            'sectionLabel'    => $this->escapeHtml(__('Section Label')),
            'moveDown'        => $this->escapeHtml(__('Move Down')),
            'moveUp'          => $this->escapeHtml(__('Move Up')),
            'remove'          => $this->escapeHtml(__('Remove')),
            'removeLCase'     => $this->escapeHtml(__('remove')),
            'optionConfig'    => $this->escapeHtml(__('Option Configuration')),
            'removeSection'   => $this->escapeHtml(__('Do you really want to remove this section?')),
            'columns'         => $this->escapeHtml(__('Columns')),
            'cols'            => $this->escapeHtml(__('Columns')),
            'rows'            => $this->escapeHtml(__('Rows')),
            'label'           => $this->escapeHtml(__('Label')),
            'fieldType'       => $this->escapeHtml(__('Field Type')),
            'input_box'       => $this->escapeHtml(__('Input Box')),
            'password_box'    => $this->escapeHtml(__('Password Box')),
            'checkbox'        => $this->escapeHtml(__('Checkbox(es)')),
            'radio'           => $this->escapeHtml(__('Radio(s)')),
            'select_box'      => $this->escapeHtml(__('Select Box')),
            'list_box'        => $this->escapeHtml(__('List Box')),
            'multiselect_box' => $this->escapeHtml(__('Multi-select List Box')),
            'textarea'        => $this->escapeHtml(__('Textarea')),
            'file'            => $this->escapeHtml(__('File Upload')),
            'static_text'     => $this->escapeHtml(__('Static Text')),
            'captcha'         => $this->escapeHtml(__('Captcha')),
            'yes'             => $this->escapeHtml(__('Yes')),
            'no'              => $this->escapeHtml(__('No')),
            'please_select'   => $this->escapeHtml(__('-- Please Select --')),
            'email'           => $this->escapeHtml(__('Email')),
            'number'          => $this->escapeHtml(__('Number')),
            'money'           => $this->escapeHtml(__('Money')),
            'phone'           => $this->escapeHtml(__('Phone')),
            'validation'      => $this->escapeHtml(__('Validation')),
            'default_value'   => $this->escapeHtml(__('Default Value')),
            'css_class'       => $this->escapeHtml(__('CSS Class')),
            'html_args'       => $this->escapeHtml(__('HTML Arguments')),
            'apply'           => $this->escapeHtml(__('Apply')),
            'cancel'          => $this->escapeHtml(__('Cancel')),
            'file_extension'  => $this->escapeHtml(__('File Extensions Allowed')),
            'max_file_size'   => $this->escapeHtml(__('Max file size in bytes')),
            'quantity'        => $this->escapeHtml(__('Quantity')),
            'itemLabel'       => $this->escapeHtml(__('Item Label')),
            'itemValue'       => $this->escapeHtml(__('Item Value')),
            'checked'         => $this->escapeHtml(__('Checked')),
            'selected'        => $this->escapeHtml(__('Selected')),
            'cannotChangeQuantity' => $this->escapeHtml(__('Cannot change quantity. Please remove unnecessary items.')),
            'removeItem'           => $this->escapeHtml(__('Do you really want to remove this item?')),
            'removeField'          => $this->escapeHtml(__('Do you really want to remove this field?')),
            'onlyOneItem'          => $this->escapeHtml(__('Cannot delete this item. Field should contain at least one item.')),
            'minRequired'          => $this->escapeHtml(__('Minimum Required')),
            'size'                 => $this->escapeHtml(__('Size')),
            'alikon_mod'           => $this->escapeHtml(__('Alikon mod')),
            'captcha_form'         => $this->escapeHtml(__('Captcha form')),
            'secur_image'          => $this->escapeHtml(__('SecurImage')),
            'name'                 => $this->escapeHtml(__('Name')),
            'noteNameDb'           => $this->escapeHtml(__('This name will be used for saving field value in database.')),
            'noteHtmlArgs'         => $this->escapeHtml(__('Don\'t use name attribute here. It is in the field below.')),

            'noteFileExt'          => $this->escapeHtml(__('example: png, jpg, jpeg, gif')),
            'cannotResizeTable'    => $this->escapeHtml(__('Cannot resize table. Please remove or move bordered fields.')),
            'nameUsed'             => $this->escapeHtml(__('Entered name is used. Please enter other name.')),
            'valueUsed'            => $this->escapeHtml(__('Entered value is used. Please enter other value.')),
            'captchaNote'          => $this->escapeHtml(__('Please, enter the text shown in the image into the field below')),
            'noteMaxCols'          => $this->escapeHtml(__('maximum: 35')),
            'minRequiredCheckboxes' => $this->escapeHtml(__('Min required should be equal or less than quantity of checkboxes')),
            'onlyOneItemChecked'    => $this->escapeHtml(__('Only one item can be checked at the same time')),
            'selectDefaultField'    => $this->escapeHtml(__('--select default field--')),
            'date'                  => $this->escapeHtml(__('Date')),
            'zip'                   => $this->escapeHtml(__('Zip Code')),
            'resetForm'             => $this->escapeHtml(__('Are you sure want to reset the form? This will discard all changes you have made.')),

            'title'                 => $this->escapeHtml(__('Title')),
            'required'              => $this->escapeHtml(__('Required')),
            'price'                 => $this->escapeHtml(__('Price')),
            'price_type'            => $this->escapeHtml(__('Price Type')),
            'fixed'                 => $this->escapeHtml(__('Fixed')),
            'percent'               => $this->escapeHtml(__('Percent')),
            'sku'                   => $this->escapeHtml(__('Sku')),
            'max_characters'        => $this->escapeHtml(__('Max Characters')),
            'comment'               => $this->escapeHtml(__('Comment')),
            'img_src'               => $this->escapeHtml(__('Image SRC')),
            'swatch'               => $this->escapeHtml(__('Swatch')),
            'img_alt'               => $this->escapeHtml(__('Image Alt')),
            'img_title'             => $this->escapeHtml(__('Image Title')),
            'hideOnFocus'           => $this->escapeHtml(__('Hide on focus')),
            'image_size'            => $this->escapeHtml(__('Maximum Image Size')),
            'note_image_size'       => $this->escapeHtml(__('leave blank if its not an image')),
            'addOption'             => $this->escapeHtml(__('Add Option')),
            'change_option_style'   => $this->escapeHtml(__('Options will be converted into %s style. Ok?')),
            'reupload'              => $this->escapeHtml(__('Reupload')),
            'upload_image'          => $this->escapeHtml(__('Upload Image')),
            'require_options'       => $this->escapeHtml(__('This Field Type required options')),
            'remove_all_fields'     => $this->escapeHtml(__('Do you really want to remove all fields?')),
            'condition_error'       => $this->escapeHtml(__('Please check conditions!')),
            'copy_field'            => $this->escapeHtml(__('Сopy Configuration From')),
            'copy_field_confirm'    => $this->escapeHtml(__('Do you really want to copy configuration from selected element?')),

            'use_global'             => $this->escapeHtml(__('Use Default')),
            'cantRemoveSection'        => $this->escapeHtml(__('You can\'t remove section with fields on a store view. Move fields to another section before remove')),

            'weight'                => $this->escapeHtml(__('Weight'))
        ];
    }

    public function getSectionsJson() {
        $sections = $this->getOptionsConfig()->getSections();
        return \Zend_Json::encode($sections);
    }

    public function escapeJsHtml($text) {
        return addslashes($this->escapeHtml($text));
    }

    public function getRuleParamsConditions() {
        return [
            'is'            => $this->escapeHtml(__('is')),
            'is_not'        => $this->escapeHtml(__('is not')),
            'equal_greater' => $this->escapeHtml(__('equals or greater than')),
            'equal_less'    => $this->escapeHtml(__('equals or less than')),
            'greater'       => $this->escapeHtml(__('greater than')),
            'less'          => $this->escapeHtml(__('less than')),
            //    'contain'       => $this->escapeHtml(__('contains')),
            //    'not_contain'   => $this->escapeHtml(__('does not contain')),
            //    'one_of'        => $this->escapeHtml(__('is one of')),
            //    'not_one_of'    => $this->escapeHtml(__('is not one of')),
        ];
    }

    public function getCustomerGroups() {
        $groups = $this->_objectManager->create('Magento\Customer\Model\Group')->getCollection();
        $result = [
            [
                'value' => '',
                'label' => $this->escapeHtml(__('All Groups'))
            ]
        ];
        if ($groups->getSize()) {
            foreach ($groups as $group) {
                $result[] = [
                    'value' => $group->getCustomerGroupId(),
                    'label' => $this->escapeHtml(__($group->getCustomerGroupCode()))
                ];
            }
        }

        return $result;
    }

    /**
     * @return \Itoris\DynamicProductOptions\Helper\Data
     */
    protected function getDataHelper() {
        return $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Data');
    }

    /**
     * @param $text
     * @return array|string
     */
    public function htmlEscape($text) {
        return $this->getDataHelper()->htmlEscape($text);
    }
}