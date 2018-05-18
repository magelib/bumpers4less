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

//app/code/Itoris/DynamicProductOptions/Block/Adminhtml/Product/Options/Template/Edit/Form.php
namespace Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options\Template\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    protected function _prepareForm() {
        $form = $this->_formFactory->create([
            'data' => [
                'id'        => 'edit_form',
                'action'    => $this->getData('action'),
                'method'    => 'post'
            ]
        ]);

        /** @var $template \Itoris\DynamicProductOptions\Model\Template */
        $template = $this->_coreRegistry->registry('current_template');

        $fieldset = $form->addFieldset('details_fieldset', [
            'legend' => $this->escapeHtml(__('Configuration')),
        ]);

        $fieldset->addField('id', 'hidden', [
            'name'     => 'id',
            'value'    => $template->getId(),
        ]);

        $fieldset->addField('name', 'text', array_merge([
            'label'    => $this->escapeHtml(__('Name')),
            'title'    => $this->escapeHtml(__('Name')),
            'name'     => 'template[name]',
            'required' => true,
            'value'    => $template->getName()
        ], $this->_request->getParam('store') ? ['readonly' => 'readonly', 'style' => 'background: #eeeeee;'] : []));

        $options = $form->addFieldset('options_fieldset', []);
        $renderer = $this->getLayout()->createBlock('Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options\Template\Edit\Form\Renderer');
        $options->setRenderer($renderer);
        $form->setUseContainer(true);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function _prepareLayout() {
        $this->setChild('dynamic_options', $this->getLayout()->createBlock('Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options'));
        return parent::_prepareLayout();
    }
}