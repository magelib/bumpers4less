<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */


namespace Amasty\XmlSitemap\Block\Adminhtml\Sitemap\Edit\Tab;

class Landing extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /** @var \Magento\Config\Model\Config\Source\YesnoFactory $_yesnoFactory */
    protected $_yesnoFactory;

    /** @var \Amasty\XmlSitemap\Helper\Data $_helper */
    protected $_helper;

    /** @var \Magento\Framework\Module\Manager $_moduleManager */
    protected $_moduleManager;

    /**
     * Landing constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Data\FormFactory $formFactory
     * @param \Magento\Config\Model\Config\Source\YesnoFactory $yesnoFactory
     * @param \Amasty\XmlSitemap\Helper\Data $helper
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Config\Model\Config\Source\YesnoFactory $yesnoFactory,
        \Amasty\XmlSitemap\Helper\Data $helper,
        \Magento\Framework\Module\Manager $moduleManager,
        array $data = []
    )
    {
        parent::__construct($context, $registry, $formFactory, $data);
        $this->_yesnoFactory = $yesnoFactory;
        $this->_helper = $helper;
        $this->_moduleManager = $moduleManager;

    }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Landing Pages');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Landing Pages');
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
        $isHidden = true;

        if ($this->_moduleManager->isEnabled('Amasty_Xlanding')) {
            $isHidden = false;
        }

        return $isHidden;
    }

    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('amxmlsitemap_profile');

        $yesno = $this->_yesnoFactory->create()->toOptionArray();

        $form = $this->_formFactory->create();

        $fieldset = $form->addFieldset('amxmlsitemap_form_landing_pages', ['legend' => __('Landing Pages')]);

        $fieldset->addField('landing', 'select', array(
            'label' => __('Include Landing Pages'),
            'name' => 'landing',
            'title' => __('Include Landing Pages'),
            'values' => $yesno
        ));

        $fieldset->addField('landing_priority', 'text',
            array(
                'label' => __('Priority'),
                'name' => 'landing_priority',
                'note' => __('0.01-0.99'),
                'class' => 'validate-number validate-number-range number-range-0.01-0.99'
            )
        );

        $fieldset->addField('landing_frequency', 'select', array(
            'label' => __('Frequency'),
            'name' => 'landing_frequency',
            'title' => __('Frequency'),
            'values' => $this->_helper->getFrequency()
        ));

        $form->addValues($model->getData());

        $this->setForm($form);
        return parent::_prepareForm();
    }
}
