<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Block\Adminhtml\Finder\Edit\Tab;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Backend\Block\Widget\Tab\TabInterface;

class Main extends Generic implements TabInterface
{
    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('General');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('General');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('current_amasty_finder_finder');
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('finder_');
        $fieldset = $form->addFieldset('base_fieldset', ['legend' => __('General')]);
        if ($model->getId()) {
            $fieldset->addField('id', 'hidden', ['name' => 'id']);
        }
        $fieldset->addField(
            'name',
            'text',
            ['name' => 'name', 'label' => __('Title'), 'title' => __('Title'), 'required' => true]
        );

        if (!$model->getId()) {
            $fieldset->addField(
                'cnt',
                'text',
                ['name'  => 'cnt', 'label' => __('Number of Dropdowns'),
                 'title' => __('Number of Dropdowns'), 'required' => true]
            );
        }

        $fieldset->addField(
            'template',
            'text',
            [
                'name' => 'template',
                'label' => __('Template'),
                'title' => __('Template'),
                'required' => false,
                'note'=>__('E.g. `vertical`, `horizontal`, `responsive`. Leave blank to use a default template')
            ]
        );

        $fieldset->addField(
            'custom_url',
            'text',
            [
                'name' => 'custom_url',
                'label' => __('Custom Destination URL'),
                'title' => __('Custom Destination URL'),
                'required' => false,
                'note'=>
                    __(
                        'E.g. special-category.html  In most cases you don`t need to set it.'
                        . ' Useful when you have 2 or more finders and want to show search results at specific categories.'
                        . ' It`s NOT the url key. You can modify /amfinder/ url key in app/code/Amasty/Finder/etc/config.xml'
                    )
            ]
        );

        if ($model->getId()) {
            $fieldset->addField(
                'code_for_inserting',
                'label',
                [
                    'label' => __('Code for inserting'),
                    'title' => __('Code for inserting'),
                ]
            );
            $fieldset->addField(
                'code_for_inserting_in_layout',
                'label',
                [
                    'label' => __('Code for inserting in Layout Update XML'),
                    'title' => __('Code for inserting in Layout Update XML'),
                ]
            );
        }

        $form->setValues($model->getData());
        $form->addValues(
            [
                'id' => $model->getId(),
                'code_for_inserting' =>
                    '{{block class="Amasty\\Finder\\Block\\Form" block_id="finder_form" id="'.$model->getId().'"}}'
            ]
        );

        $form->addValues(
            [
                'id' => $model->getId(),
                'code_for_inserting_in_layout'=>
                    '<referenceContainer name="content">
                         <block class="Amasty\Finder\Block\Form" name="amasty.finder.' . $model->getId() . '">
                             <arguments>
                                 <argument name="id" xsi:type="string">' . $model->getId() . '</argument>
                                 <argument name="template" xsi:type="string">' . $model->getTemplate() . '</argument>
                             </arguments>
                         </block>
                     </referenceContainer>'
            ]
        );

        $this->setForm($form);
        return parent::_prepareForm();
    }
}
