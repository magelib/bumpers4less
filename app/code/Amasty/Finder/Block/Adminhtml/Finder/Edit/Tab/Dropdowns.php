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

class Dropdowns extends Generic implements TabInterface
{
    /**
     * @var \Amasty\Finder\Model\Finder
     */
    protected $_model;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory, array $data
    ) {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_model = $this->_coreRegistry->registry('current_amasty_finder_finder');

    }


    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Dropdowns');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Dropdowns');
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

        return !$this->_model->getId();
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
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create();
        $form->setHtmlIdPrefix('finder_dropdown_');
        $values = [];
        foreach($this->_model->getDropdowns() as $dropdown) {
            $prefix = 'dropdown_'.$dropdown->getId();

            $fieldset = $form->addFieldset($prefix, ['legend' => __('Dropdown #%1',$dropdown->getPos()+1)]);
            $fieldset->addField(
                $prefix . '_name',
                'text',
                ['name'=>$prefix . '_name', 'label' => __('Name'), 'title' => __('Name'), 'required' => true]
            );
            $values[$prefix . '_name'] = $dropdown->getName();
            $fieldset->addField(
                $prefix . '_sort',
                'select',
                [
                    'name'=>$prefix . '_sort', 'label' => __('Sort'), 'title' => __('Sort'), 'required' => true,
                    'values' => [
                        ['value' => \Amasty\Finder\Helper\Data::SORT_STRING_ASC,     'label' => __('alphabetically, asc')],
                        ['value' => \Amasty\Finder\Helper\Data::SORT_STRING_DESC,    'label' => __('alphabetically, desc')],
                        ['value' => \Amasty\Finder\Helper\Data::SORT_NUM_ASC,        'label' => __('numerically, asc')],
                        ['value' => \Amasty\Finder\Helper\Data::SORT_NUM_DESC,       'label' => __('numerically, desc')],
                    ]
                ]
            );
            $values[$prefix . '_sort'] = $dropdown->getSort();

            $fieldset->addField(
                $prefix . '_range',
                'select',
                [
                    'name'=>$prefix . '_range', 'label' => __('Range'), 'title' => __('Range'), 'required' => true,
                    'values' => [
                        ['value' => 0,     'label' => __('No')],
                        ['value' => 1,    'label' => __('Yes')],
                    ]
                ]
            );
            $values[$prefix . '_range'] = $dropdown->getRange();
        }
        $form->setValues($values);
        $this->setForm($form);
        return parent::_prepareForm();
    }
}
