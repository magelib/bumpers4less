<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Duplicate;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Firebear\ImportExport\Model\Import\Product;
use Firebear\ImportExport\Model\Import\Customer;
use Firebear\ImportExport\Model\Import\Address;
use Firebear\ImportExport\Model\Import\CustomerComposite;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{

    /**
     * @var array
     */
    protected $options;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory
     */
    protected $attributeFactory;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    protected $attributeCollection;

    /**
     * @var Product
     */
    protected $productImportModel;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var Address
     */
    protected $address;

    /**
     * @var CustomerComposite
     */
    protected $composite;

    /**
     * Options constructor.
     *
     * @param CollectionFactory $attributeFactory
     * @param Product          $productImportModel
     */
    public function __construct(
        CollectionFactory $attributeFactory,
        Product $productImportModel,
        Customer $customer,
        Address $address,
        CustomerComposite $composite
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->productImportModel = $productImportModel;
        $this->customer = $customer;
        $this->address = $address;
        $this->composite = $composite;
    }
    
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $newOptions = $this->productImportModel->getDuplicateFields();
        $newOptions = array_merge($newOptions, $this->customer->getDuplicateFields());
        $newOptions = array_merge($newOptions,$this->address->getDuplicateFields());
        $newOptions = array_merge($newOptions,$this->composite->getDuplicateFields());
        $this->options = array_unique($newOptions);

        return $this->options;
    }
}
