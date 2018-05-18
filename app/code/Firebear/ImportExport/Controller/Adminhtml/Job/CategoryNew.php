<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Magento\Backend\App\Action\Context;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;


class CategoryNew extends JobController
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $collectionCategoryFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    private $jsonFactory;

    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        CategoryCollectionFactory $collectionCategoryFactory,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
        $this->collectionCategoryFactory = $collectionCategoryFactory;
        $this->jsonFactory               = $jsonFactory;
    }

    /**
     * @return $this
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        
        if ($this->getRequest()->isAjax()) {
            $newCategoryId = $this->getRequest()->getParam('categoryId');
            $categoryPath = $this->getCategoryPathById($newCategoryId);
            return $resultJson->setData(
                [
                    'data' => $categoryPath
                ]
            );
        }
    }
    
    private function getCategoryPathById($categoryId)
    {
        $storeId = $this->getRequest()->getParam('store');

        /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        $collection = $this->collectionCategoryFactory->create();

        $collection->addAttributeToFilter('entity_id', $categoryId)
            ->addAttributeToSelect(['name', 'is_active', 'parent_id', 'path'])
            ->setStoreId($storeId);
        $categoryFullPath = '';
        foreach ($collection as $category) {
            $categoryPath        = $category->getPath();
            $explodeCategoryPath = explode('/', $categoryPath);
            foreach ($explodeCategoryPath as $categoryId) {
                if ($categoryId == 1) {
                    continue;
                }
                $collectionPathCategory = $this->collectionCategoryFactory->create();
                $collectionPathCategory->addAttributeToFilter('entity_id', $categoryId)
                    ->addAttributeToSelect(['name', 'is_active', 'parent_id', 'path'])
                    ->setStoreId($storeId);
                foreach ($collectionPathCategory as $categoryPath) {
                    if (empty($categoryFullPath)) {
                        $categoryFullPath = $categoryPath->getName();
                    } else {
                        $categoryFullPath .= '/' . $categoryPath->getName();
                    }
                }
            }
        }

        return $categoryFullPath;
    }
}
