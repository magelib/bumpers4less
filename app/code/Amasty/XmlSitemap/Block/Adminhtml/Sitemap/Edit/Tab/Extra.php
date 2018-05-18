<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */


namespace Amasty\XmlSitemap\Block\Adminhtml\Sitemap\Edit\Tab;

class Extra extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /** @var \Magento\Config\Model\Config\Source\YesnoFactory $_yesnoFactory */
    protected $_yesnoFactory;

    /** @var \Amasty\XmlSitemap\Helper\Data $_helper */
    protected $_helper;

    /**
     * Products constructor.
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
        return __('Extra');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Extra');
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

        $fieldset = $form->addFieldset('amxmlsitemap_form_extra', ['legend' => __('Extra Links')]);

        $fieldset->addField('extra', 'select', array(
            'label' => __('Include Extra Links'),
            'name' => 'extra',
            'title' => __('Include Extra Links'),
            'values' => $yesno
        ));

        $fieldset->addField('extra_priority', 'text',
            array(
                'label' => __('Priority'),
                'name' => 'extra_priority',
                'note' => __('0.01-0.99'),
                'class' => 'validate-number validate-number-range number-range-0.01-0.99'
            )
        );

        $fieldset->addField('extra_frequency', 'select', array(
            'label' => __('Frequency'),
            'name' => 'extra_frequency',
            'title' => __('Frequency'),
            'values' => $this->_helper->getFrequency()
        ));

        $fieldset->addField('extra_links', 'textarea', array(
            'label' => __('Extra Links to include'),
            'name' => 'extra_links',
            'note' => __('Extra Links to include')
        ));

        $form->addValues($model->getData());

        $this->setForm($form);
        return parent::_prepareForm();
    }
}
