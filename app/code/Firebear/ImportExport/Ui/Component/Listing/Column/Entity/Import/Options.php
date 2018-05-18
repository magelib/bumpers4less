<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\Component\Listing\Column\Entity\Import;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Firebear\ImportExport\Model\Import\Product;
use Firebear\ImportExport\Model\Source\Import\Config;
use Magento\ImportExport\Model\Import\Entity\Factory;
use Firebear\ImportExport\Model\Source\Config\CartPrice;
/**
 * Class Options
 */
class Options implements OptionSourceInterface
{

    const CATALOG_PRODUCT = 'catalog_product';

    const CATALOG_CATEGORY = 'catalog_category';

    const ADVANCED_PRICING = 'advanced_pricing';

    const CART_PRICE_RULE = 'cart_price_rule';

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

    protected $factory;

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
     * @var \Firebear\ImportExport\Model\Source\Config\CartPrice
     */
    protected $cartPrice;

    /**
     * Options constructor.
     *
     * @param CollectionFactory $attributeFactory
     * @param Product $productImportModel
     */
    public function __construct(
        CollectionFactory $attributeFactory,
        Product $productImportModel,
        Config $config,
        Factory $entityFactory,
        CartPrice $cartPrice,
        \Firebear\ImportExport\Helper\Data $helper
    ) {
        $this->attributeFactory = $attributeFactory;
        $this->productImportModel = $productImportModel;
        $this->config = $config;
        $this->entityFactory = $entityFactory;
        $this->cartPrice = $cartPrice;
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

        foreach ($this->config->getEntities() as $key => $items) {
            if (in_array($key, [
                self::CATALOG_PRODUCT,
                self::CATALOG_CATEGORY,
                self::ADVANCED_PRICING
            ])) {
                $newOptions[$key] = $options;
            } else if (in_array($key, [
                self::CART_PRICE_RULE
            ])) {
                $newOptions[$key] = '';
            } else {
                try {
                    $object = $this->entityFactory->create($items['model']);
                    $newOptions[$key] = $this->getAllFields($object);
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Please enter a correct entity model.')
                    );
                }
            }
        }

        $this->options = $newOptions;

        return $this->options;
    }

    public function getOptions($entity) 
    {
        if (in_array($entity, [
            self::CATALOG_PRODUCT,
            self::CATALOG_CATEGORY,
            self::ADVANCED_PRICING
        ])) {
            $options = $this->getAttributeCatalog();
            $newOptions[$entity] = $options;
        } else {
            $configes = $this->config->getEntities();
            if (isset($configes[$entity])) {
                try {
                    $object = $this->entityFactory->create($configes[$entity]['model']);
                    $newOptions[$entity] = $this->getAllFields($object);
                } catch (\Exception $e) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Please enter a correct entity model.')
                    );
                }
            }
        }

        return $newOptions;
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
        $options = [];
        $subOptions = [];
      
        foreach ($this->helper->partCollection($attributeCollection) as $attribute) {
            $label = (!$withoutGroup) ? $attribute->getAttributeCode() . ' (' . $attribute->getFrontendLabel() . ')' : $attribute->getAttributeCode();
            $subOptions[] =
                [
                    'label' => $label,
                    'value' => $attribute->getAttributeCode()
                ];
        }
         unset($attributeCollection);
        if (!$withoutGroup) {
            $options[] = [
                'label' => __('Product Attributes'),
                'optgroup-name' => 'product_attributes',
                'value' => $subOptions
            ];
        } else {
            $options += $subOptions;
        }
        $specialAttributes = $this->productImportModel->getSpecialAttributes();
        $subOptions = [];
        foreach ($this->helper->partCollection($specialAttributes) as $attribute) {
            $subOptions[] = ['label' => $attribute, 'value' => $attribute];
        }
        unset($specialAttributes);
        $AddFields = $this->productImportModel->getAddFields();
        foreach ($this->helper->partCollection($AddFields) as $attribute) {
            $subOptions[] = ['label' => $attribute, 'value' => $attribute];
        }
        unset($AddFields);
        
        if (!$withoutGroup) {
            $options[] = [
                'label' => __('Special Fields'),
                'optgroup-name' => 'special_attributes',
                'value' => $subOptions
            ];
        } else {
            $options = array_merge($options, $subOptions);
        }
        $subOptions = [];
        $subOptions[] = ['label' => '_category', 'value' => '_category'];
        $subOptions[] = ['label' => '_root_category', 'value' => '_root_category'];
        if (!$withoutGroup) {
            $options[] = [
                'label' => __('Other Fields'),
                'optgroup-name' => 'other_attributes',
                'value' => $subOptions
            ];
        } else {
            $options = array_merge($options, $subOptions);
        }

        return $options;
    }

    /**
     * @return array
     */
    protected function getAllFields($object)
    {
        $options = [];
        foreach ($object->getAllFields() as $field) {
            $options[] = ['label' => $field, 'value' => $field];
        }

        return $options;
    }
}
