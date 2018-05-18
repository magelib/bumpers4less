<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */


namespace Amasty\XmlSitemap\Block\Adminhtml\Sitemap\Edit\Tab;

class Categories extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /** @var \Magento\Config\Model\Config\Source\YesnoFactory $_yesnoFactory */
    protected $_yesnoFactory;

    /** @var \Amasty\XmlSitemap\Helper\Data $_helper */
    protected $_helper;

    /**
     * Categories constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Config\Model\Config\Source\YesnoFactory $yesnoFactory
     * @param \Amasty\XmlSitemap\Helper\Data $helper
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\YesnoFactory $yesnoFactory,
        \Amasty\XmlSitemap\Helper\Data $helper,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_yesnoFactory = $yesnoFactory;
        $this->_helper = $helper;

    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Categories');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Categories');
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

    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('amxmlsitemap_profile');

        $yesno = $this->_yesnoFactory->create()->toOptionArray();

        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset('amxmlsitemap_form_categories', ['legend' => __('Categories')]);

        $fieldset->addField('categories', 'select', array(
            'label' => __('Include categories'),
            'name' => 'categories',
            'title' => __('Include categories'),
            'values' => $yesno
        ));

        $fieldset->addField('categories_thumbs', 'select', array(
            'label' => __('Add Images'),
            'name' => 'categories_thumbs',
            'title' => __('Add Images'),
            'values' => $yesno
        ));

        $fieldset->addField('categories_priority', 'text',
            array(
                'label' => __('Priority'),
                'name' => 'categories_priority',
                'note' => __('0.01-0.99'),
                'class' => 'validate-number validate-number-range number-range-0.01-0.99'
            )
        );

        $fieldset->addField('categories_frequency', 'select', array(
            'label' => __('Frequency'),
            'name' => 'categories_frequency',
            'title' => __('Frequency'),
            'values' => $this->_helper->getFrequency()
        ));

        $fieldset->addField('categories_modified', 'select', array(
            'label' => __('Include Last Modified'),
            'name' => 'categories_modified',
            'title' => __('Include Last Modified'),
            'values' => $yesno
        ));

        $form->addValues($model->getData());

        $this->setForm($form);
        return parent::_prepareForm();
    }
}
