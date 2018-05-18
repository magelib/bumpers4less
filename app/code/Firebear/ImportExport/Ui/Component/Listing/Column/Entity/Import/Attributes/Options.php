<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Import\Attributes;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Firebear\ImportExport\Model\Import\Product;
use Firebear\ImportExport\Model\Import\Customer;
use Firebear\ImportExport\Model\Import\Address;
use Firebear\ImportExport\Model\Import\CustomerComposite;
use Firebear\ImportExport\Model\Source\Import\Config;
use Magento\ImportExport\Model\Import\Entity\Factory;

/**
 * Class Options
 */
class Options implements OptionSourceInterface
{

    const CATALOG_PRODUCT = 'catalog_product';

    const CATALOG_CATEGORY = 'catalog_category';

    const ADVANCED_PRICING = 'advanced_pricing';

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
     * @var Config
     */
    protected $config;

    /**
     * @var \Magento\ImportExport\Model\Import\Entity\Factory
     */
    protected $entityFactory;

    /**
     * @var \Firebear\ImportExport\Helper\Data
     */
    protected $helper;

    /**
     * Options constructor.
     *
     * @param CollectionFactory $attributeFactory
     * @param Product $productImportModel
     */
    public function __construct(
        CollectionFactory $attributeFactory,
        Product $productImportModel,
        Customer $customer,
        Address $address,
        CustomerComposite $composite,
        Config $config,
        Factory $entityFactory,
        \Firebear\ImportExport\Helper\Data $helper
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->productImportModel = $productImportModel;
        $this->customer = $customer;
        $this->address = $address;
        $this->composite = $composite;
        $this->config = $config;
        $this->entityFactory = $entityFactory;
        $this->helper = $helper;
    }

    /**
     * @param int $withoutGroup
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function toOptionArray($withoutGroup = 0)
    {

        $options = $this->getAttributeCatalog($withoutGroup);

        $this->options = $options;

        return $this->options;
    }

    /**
     * @return \Magento\Catalog\Model\ResourceModel\Product\Attribute\Collection
     */
    public function getAttributeCollection()
    { 
       
        $this->attributeCollection = $this->attributeFactory
            ->create()
            ->addVisibleFilter()
            ->setOrder('attribute_code', AbstractDb::SORT_ORDER_ASC);

        return $this->attributeCollection;
    }

    /**
     * @return array
     */
    protected function getAttributeCatalog($withoutGroup = 0)
    {
        $attributeCollection = $this->getAttributeCollection();
        $subOptions = [];
        foreach ($this->helper->partCollection($attributeCollection) as $attribute) {
            $label = (!$withoutGroup) ? $attribute->getAttributeCode() . ' (' . $attribute->getFrontendLabel() . ')' : $attribute->getAttributeCode();
            $subOptions[] =
                [
                    'label' => $label,
                    'value' => $attribute->getAttributeCode()
                ];
        }

        return $subOptions;
    }
}
