<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */


namespace Amasty\XmlSitemap\Block\Adminhtml\Sitemap\Edit\Tab;

class Products extends \Magento\Backend\Block\Widget\Form\Generic implements \Magento\Backend\Block\Widget\Tab\TabInterface
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
        return __('Products');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Products');
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

        $fieldset = $form->addFieldset('amxmlsitemap_form_products', ['legend' => __('Products')]);

        $fieldset->addField('products', 'select', array(
            'label' => __('Include products'),
            'name' => 'products',
            'title' => __('Include products'),
            'values' => $yesno
        ));

        $fieldset->addField('products_thumbs', 'select', array(
            'label' => __('Add Images'),
            'name' => 'products_thumbs',
            'title' => __('Add Images'),
            'values' => $yesno
        ));

        $fieldset->addField('products_captions', 'select', array(
            'label' => __('Add Images Titles'),
            'name' => 'products_captions',
            'title' => __('Add Images Titles'),
            'values' => $yesno
        ));

        $fieldset->addField('products_captions_template', 'text', array(
            'label' => __('Template for image title'),
            'name' => 'products_captions_template',
            'title' => __('Template for image title'),
            'note' => __('Specify text to be used for empty captions with {product_name} placeholder for product name. Example - "enjoy {product_name} from e-store"')
        ));

        $fieldset->addField('products_priority', 'text',
            array(
                'label' => __('Priority'),
                'name' => 'products_priority',
                'note' => __('0.01-0.99'),
                'class' => 'validate-number validate-number-range number-range-0.01-0.99'
            )
        );

        $fieldset->addField('products_frequency', 'select', array(
            'label' => __('Frequency'),
            'name' => 'products_frequency',
            'title' => __('Frequency'),
            'values' => $this->_helper->getFrequency()
        ));

        $fieldset->addField('products_modified', 'select', array(
            'label' => __('Include Last Modified'),
            'name' => 'products_modified',
            'title' => __('Include Last Modified'),
            'values' => $yesno
        ));

        $form->addValues($model->getData());

        $this->setChild('form_after', $this->getLayout()
            ->createBlock('Magento\Backend\Block\Widget\Form\Element\Dependence')
            ->addFieldMap('products_thumbs', 'products_thumbs')
            ->addFieldMap('products_captions_template', 'products_captions_template')
            ->addFieldMap('products_captions', 'products_captions')
            ->addFieldDependence('products_captions_template', 'products_thumbs', 1)
            ->addFieldDependence('products_captions_template', 'products_captions', 1)
            ->addFieldDependence('products_captions', 'products_thumbs', 1)
        );

        $form->addValues($model->getData());

        $this->setForm($form);
        return parent::_prepareForm();
    }
}
