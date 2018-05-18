<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Checkout
 */

namespace Amasty\Checkout\Model;

use Magento\Framework\Model\AbstractModel;

class Field extends AbstractModel
{
    /**
     * @var ResourceModel\Field\CollectionFactory
     */
    protected $attributeCollectionFactory;

    /**
     * Field constructor.
     *
     * @param \Magento\Framework\Model\Context          $context
     * @param \Magento\Framework\Registry               $registry
     * @param ResourceModel\Field                       $resource
     * @param ResourceModel\Field\Collection        $resourceCollection
     * @param ResourceModel\Field\CollectionFactory $attributeCollectionFactory
     * @param array                                     $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Amasty\Checkout\Model\ResourceModel\Field $resource,
        \Amasty\Checkout\Model\ResourceModel\Field\Collection $resourceCollection,
    
        \Amasty\Checkout\Model\ResourceModel\Field\CollectionFactory $attributeCollectionFactory,

        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        
        $this->attributeCollectionFactory = $attributeCollectionFactory;
    }

    protected function _construct()
    {
        $this->_init('Amasty\Checkout\Model\ResourceModel\Field');
    }

    public function getInheritedAttributes()
    {
        return [
            'region_id' => 'region',
            'vat_is_valid' => 'vat_id',
            'vat_request_id' => 'vat_id',
            'vat_request_date' => 'vat_id',
            'vat_request_success' => 'vat_id',
        ];
    }

    public function getConfig($store = null)
    {
        /** @var \Amasty\Checkout\Model\ResourceModel\Field\Collection $collection */
        $collection = $this->attributeCollectionFactory->create();
        
        $collection
            ->joinStore($store)
            ->joinAttribute()
            ->setOrder('sort_order', 'ASC')
        ;

        $result = [];

        foreach ($collection as $item) {
            $result[$item->getData('attribute_code')] = $item;
        }

        return $result;
    }
}
