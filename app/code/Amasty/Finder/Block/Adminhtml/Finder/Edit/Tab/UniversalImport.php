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


use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Backend\Block\Widget\Form\Generic;

class UniversalImport extends Generic implements TabInterface
{
    use \Amasty\Finder\MyTrait\FinderTab;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory, array $data
    ) {
        $this->_model = $registry->registry('current_amasty_finder_finder');
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_tabLabel = __('Universal Products Import');
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
        $form->setHtmlIdPrefix('finder_');
        $fieldset = $form->addFieldset('amfinder_universalimport', ['legend' => __('Import Universal Products')]);
        $fieldset->addField(
            'importuniversal_clear',
            'select',
            ['name' => 'importuniversal_clear', 'label' => __('Delete Existing Data'), 'title' => __('Delete Existing Data'), 'required' => true,'values' => [
                ['value' => 0,     'label' => __('No')],
                ['value' => 1,    'label' => __('Yes')],
            ]]
        );
        $fieldset->addField(
            'importuniversal_file',
            'file',
            ['name' => 'importuniversal_file', 'label' => __('CSV File'), 'title' => __('CSV File'), 'required' => false, 'note'=>__('SKU1,SKU2,SKU3,...')]
        );

        $this->setForm($form);
        return parent::_prepareForm();
    }
}
