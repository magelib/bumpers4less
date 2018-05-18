<?php

/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Helper\Data;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Downfiltres extends JobController
{
    /**
     * @var \Firebear\ImportExport\Model\ExportFactory
     */
    protected $export;

    /**
     * @var \Magento\ImportExport\Model\Source\Export\Entity
     */
    protected $entity;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection
     */
    protected $collection;

    /**
     * @var \Firebear\ImportExport\Model\Export\Dependencies\Config
     */
    protected $config;

    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Firebear\ImportExport\Model\Source\Factory
     */
    protected $createFactory;

    /**
     * @var \Firebear\ImportExport\Helper\Data
     */
    protected $helper;

    /**
     * @var \Firebear\ImportExport\Model\Export\Product\Additional
     */
    protected $additional;

    protected $additionalCust;

    /**
     * Downfields constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param JsonFactory $jsonFactory
     * @param \Firebear\ImportExport\Model\ExportFactory $export
     * @param \Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Export\Options $entity
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        JsonFactory $jsonFactory,
        \Firebear\ImportExport\Model\ExportFactory $export,
        \Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Export\Options $entity,
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Collection $collection,
        \Firebear\ImportExport\Model\Export\Dependencies\Config $config,
        \Firebear\ImportExport\Model\Source\Factory $createFactory,
        \Firebear\ImportExport\Helper\Data $helper,
        \Firebear\ImportExport\Model\Export\Product\Additional $additional,
        \Firebear\ImportExport\Model\Export\Customer\Additional $additionalCust
    ) {
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
        $this->jsonFactory = $jsonFactory;
        $this->export = $export;
        $this->entity = $entity;
        $this->collection = $collection;
        $this->config = $config;
        $this->createFactory = $createFactory;
        $this->helper = $helper;
        $this->additional = $additional;
        $this->additionalCust = $additionalCust;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $options = [];
        $result = [];
        if ($this->getRequest()->isAjax()) {
            $entity = $this->getRequest()->getParam('entity');
            $type = $this->getRequest()->getParam('type');
            if ($entity && $type) {
                $options = $this->getFromAttributes();
                $options += $this->getFromTables();
            }
            if (!empty($options)) {
                foreach ($options[$type] as $field) {
                if ($entity == $field['field']) {
                    $result = $field;
                }
            }
            }

            return $resultJson->setData($result);
        }
    }

    protected function getFromAttributes()
    {
        $options = [];
        $options['attr'] = [];
        $collection = $this->collection;
        foreach ($collection as $item) {
            $select = [];
            $type = $item->getFrontendInput();
            if (in_array($type,[\Magento\ImportExport\Model\Export::FILTER_TYPE_SELECT,'multiselect'])) {
                if ($optionsAttr = $item->getSource()->getAllOptions()) {
                    foreach ($optionsAttr as $option) {
                        if (!empty($option['value'])) {
                        $select[] = ['label' => $option['label'], 'value' => $option['value']];
                        }
                    }
                }
            }

            if ($item->getFrontendInput() != 'select'
                && in_array($item->getBackendType(), ['int', 'decimal'])) {
                $type = 'int';
            }
            if (in_array($item->getFrontendInput(), ['textarea', 'media_image', 'image', 'multiline', 'gallery'])) {
                $type = 'text';
            }
            if (in_array($item->getFrontendInput(), ['hidden'])) {
                $type = 'not';
            }
            if (in_array($item->getFrontendInput(), ['multiselect'])) {
                $type = 'select';
            }
            if ($item->getFrontendInput() == 'boolean') {
                $type = 'select';
                $select[] = ['label' => __('Yes'), 'value' => 1];
                $select[] = ['label' => __('No'), 'value' => 0];
            }
            if ($item->getAttributeCode() == 'category_ids') {
                $type = 'int';
            }
    
        
            $options['attr'][] =
                [
                    'field' => $item->getAttributeCode(),
                    'type' => $type,
                    'select' => $select
                ];
        }
        foreach ($this->additional->getAdditionalFields() as $field) {
            $options['attr'][] = $field;
        }
        foreach ($this->additionalCust->getAdditionalFields() as $field) {
            $options['attr'][] = $field;
        }


        return $options;
    }

    /**
     * @return array
     */
    protected function getFromTables()
    {
        $options = [];
        $data = $this->config->get();
        foreach ($data as $typeName => $type) {
            $model = $this->createFactory->create($type['model']);
            $options += $model->getFieldColumns();
        }

        return $options;
    }
}
