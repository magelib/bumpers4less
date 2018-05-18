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

class Downfields extends JobController
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
        \Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Export\Options $entity
    ) {
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
        $this->jsonFactory = $jsonFactory;
        $this->export = $export;
        $this->entity = $entity;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $options  = [];
        $entities = $this->entity->toOptionArray();
        if ($this->getRequest()->isAjax()) {
            $entity = $this->getRequest()->getParam('entity');

            if ($entity) {
                $list = $this->loadList();
                $options = $list[$entity];
            }

            return $resultJson->setData($options);
        }
    }

    /**
     * @return array
     */
    protected function loadList()
    {
        $entities = $this->entity->toOptionArray();
        $options  = [];
        foreach ($entities as $item) {
            $childs = [];
            if ($item['value']) {
                $fields = $this->export->create()->setData(['entity' => $item['value']])->getFields();
                foreach ($fields as $field) {
                    if (!isset($field['optgroup-name'])) {
                        $childs[] = ['value' => $field, 'label' => $field];
                    } else {
                        $options[$field['optgroup-name']] = $field['value'];
                    }
                }
                if (!isset($options[$item['value']])) {
                    $options[$item['value']] = $childs;
                }
            }
        }

        return $options;
    }
}
