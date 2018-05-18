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

class Downfiltr extends JobController
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var \Firebear\ImportExport\Model\ExportFactory
     */
    protected $export;

    /**
     * @var \Magento\ImportExport\Model\Source\Export\Entity
     */
    protected $entity;

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
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\CollectionFactory $collectionFactory,
        \Firebear\ImportExport\Model\Export\Dependencies\Config $config,
        \Firebear\ImportExport\Model\Source\Factory $createFactory,
        \Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory $typeCollection,
        \Firebear\ImportExport\Model\Export\Product\Additional $additional,
        \Firebear\ImportExport\Model\Export\Customer\Additional $additionalCust
    ) {
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
        $this->jsonFactory = $jsonFactory;
        $this->export = $export;
        $this->entity = $entity;
        $this->collection = $collectionFactory;
        $this->config = $config;
        $this->createFactory = $createFactory;
        $this->typeCollection = $typeCollection;
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
        $options  = [];
        $list = [];
        $tables= $this->getFromTables();
        $extTypes = array_keys($tables);
        if ($this->getRequest()->isAjax()) {
            $entity = $this->getRequest()->getParam('entity');
            if ($entity) {
                if (!in_array($entity, $extTypes)) {
                  //  $options[] = $this->getFromAttributes($entity);
                    $list = $this->getFromAttributes($entity);
                    if (in_array($entity, ['advanced_pricing', 'catalog_product'])) {
                        foreach ($this->uniqualFields() as $field) {
                            $list[] = $field;
                        }
                    }
                    if (in_array($entity, ['customer'])) {
                        foreach ($this->uniqualFieldsCust() as $field) {
                            $list[] = $field;
                        }
                    }
                    $options = $list;
                } else {
                    $tables= $this->getFromTables();

                    $options = $tables[$entity];

                }
            }

            return $resultJson->setData($options);
        }
    }

    /**
     * @param $type
     * @return array
     */
    protected function getFromAttributes($type)
    {
        $options = [];
        if ($type == 'advanced_pricing') {
            $type = 'catalog_product';
        }
        $types = $this->typeCollection->create()->addFieldToFilter('entity_type_code', $type);
        if ($types->getSize()) {
            $collection = $this->collection->create()->addFieldToFilter(
                'entity_type_id',
                $types->getFirstItem()->getId()
            );
            foreach ($collection as $item) {
                $options[] = [
                    'value' => $item->getAttributeCode(),
                    'label' => $item->getFrontendLabel() ? $item->getFrontendLabel() : $item->getAttributeCode()
                ];
            }
        }

        return $options;
    }

    /**
     * @return array
     */
    protected function getFromTables()
    {
        $options = [];
        $data = $this->config->get();;
        foreach ($data as $typeName => $type) {
            $model = $this->createFactory->create($type['model']);
            $options += $model->getFieldsForFilter();
        }

        return $options;
    }

    protected function uniqualFields()
    {
        return $this->additional->toOptionArray();
    }

    protected function uniqualFieldsCust()
    {
        return $this->additionalCust->toOptionArray();
    }
}
