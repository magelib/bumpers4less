<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Block\Adminhtml\Value\Edit;

class Form extends \Magento\Backend\Block\Widget\Form\Generic
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * Form constructor.
     *
     * @param \Magento\Backend\Block\Template\Context   $context
     * @param \Magento\Framework\Registry               $registry
     * @param \Magento\Framework\Data\FormFactory       $formFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array                                     $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Data\FormFactory $formFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager, array $data
    ) {
        $this->_objectManager = $objectManager;
        parent::__construct($context, $registry, $formFactory, $data);
    }


    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('amasty_finder_value_form');
        $this->setTitle(__('Value Information'));
    }

    /**
     * Prepare form before rendering HTML
     *
     * @return $this
     */
    protected function _prepareForm()
    {
        /**
         * @var $value \Amasty\Finder\Model\Value
         */
        $value = $this->_coreRegistry->registry('current_amasty_finder_value');
        $finder = $this->_coreRegistry->registry('current_amasty_finder_finder');
        $settingData = [];
        /** @var \Magento\Framework\Data\Form $form */
        $form = $this->_formFactory->create(
            [
                'data' => [
                    'id' => 'edit_form',
                    'action' => $this->getUrl('amasty_finder/value/save', ['id' => $this->getRequest()->getParam('id'), 'finder_id'=>$finder->getId()]),
                    'method' => 'post',
                    'enctype' => 'multipart/form-data',
                ],
            ]
        );
        $form->setUseContainer(true);
        $this->setForm($form);

        $fieldset = $form->addFieldset('set', array('legend'=> __('General')));
        $fieldset->addField('sku', 'text', [
            'label'    => __('SKU'),
            'title'    => __('SKU'),
            'name'     => 'sku',
        ]);

        if($value->getId()) {
            $settingData['sku'] = $value->getSkuById($this->getRequest()->getParam('id'), $value->getId());
        }
        $currentId = $value->getId();


        $fields = [];
        while($currentId) {
            $alias_name  =   'name_' . $currentId;
            $alias_label =   'label_'.$currentId;

            $model = clone $value;
            $model->load($currentId);
            $currentId = $model->getParentId();
            $dropdownId =  $model->getDropdownId();
            $dropdown =  $this->_objectManager->create('Amasty\Finder\Model\Dropdown')->load($dropdownId);
            $dropdownName = $dropdown->getName();
            $settingData[$alias_name] = $model->getName();
            $fields[$alias_name] = [
                'label'    => __($dropdownName),
                'title'    => __($dropdownName),
                'name'     => $alias_label
            ];
        }

        $fields = array_reverse($fields);

        foreach($fields as $alias_name=>$fieldData) {
            $fieldset->addField($alias_name, 'text', $fieldData);
        }

        if(!$value->getId()) {
            $finder = $value->getFinder();

            foreach ($finder->getDropdowns() as $drop){
                $alias_name  = 'name_'.$drop->getId();
                $alias_label = 'label_'.$drop->getId();
                $fieldset->addField($alias_name, 'text', [
                    'label'    => __($drop->getName()),
                    'title'    => __($drop->getName()),
                    'name'     => $alias_label
                ]);
            }

            $fieldset->addField('new_finder', 'hidden', ['name'     => 'new_finder']);
            $settingData['new_finder'] = 1;
        }


        //set form values
        $form->setValues($settingData);

        return parent::_prepareForm();
    }
}
