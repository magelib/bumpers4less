<?php
/* Magedelight
 * Copyright (C) 2016 Magedelight <info@magedelight.com>
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @category Magedelight
 * @package Magedelight_Faqs
 * @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 * 
 */
namespace Magedelight\Arp\Block\Adminhtml\Index\Edit\Tab;

use Magento\Backend\Block\Widget\Grid\Extended as ExtendedGrid;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\Product\Visibility as ProductVisibility;
use Magento\Framework\Registry;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data as DataHelper;
use Magento\Store\Model\Store;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory as SetCollectionFactory;
use Magento\Catalog\Model\ProductFactory as productFactory;
use Magento\Directory\Model\Currency;
use Magento\Store\Model\ScopeInterface;
use Magedelight\Arp\Model\ProductruleFactory as ProductruleFactory;

/**
 * @method Product setUseAjax(\bool $useAjax)
 * @method array|null getAuthorProducts()
 * @method Product setAuthorProducts(array $products)
 */
class Products extends ExtendedGrid implements TabInterface
{
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    public $productCollectionFactory;
    /**
     * Faq factory
     *
     * @var FaqFactory
     */
    public $productruleFactory;
    
    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    public $type;

    /**
     * @var \Magento\Catalog\Model\Product\Attribute\Source\Status
     */
    public $status;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    public $visibility;

    /**
     * @var  \Magento\Framework\Registry
     */
    public $coreRegistry;
    public $setCollectionFactory;
    public $productFactory;
    public $objectManager;

    /**
     * @param CollectionFactory $productCollectionFactory
     * @param ProductType $type
     * @param ProductStatus $status
     * @param ProductVisibility $visibility
     * @param Registry $coreRegistry
     * @param SetCollectionFactory $setsFactory
     * @param ProductFactory $productFactory
     * @param Context $context
     * @param DataHelper $backendHelper
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        CollectionFactory $productCollectionFactory,
        ProductType $type,
        ProductStatus $status,
        ProductVisibility $visibility,
        Registry $coreRegistry,
        SetCollectionFactory $setsFactory,
        ProductFactory $productFactory,
        ProductruleFactory $productruleFactory,
        Context $context,
        DataHelper $backendHelper,
        array $data = []
    ) {
    
        $this->productCollectionFactory = $productCollectionFactory;
        $this->type = $type;
        $this->productruleFactory = $productruleFactory;
        $this->status = $status;
        $this->visibility = $visibility;
        $this->coreRegistry = $coreRegistry;
        $this->setCollectionFactory = $setsFactory;
        $this->productFactory = $productFactory;
        $this->objectManager = $objectManager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Set grid params
     */
    public function _construct()
    {
        parent::_construct();
        $this->setId('product_grid');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        $this->setFilterVisibility(false);
        if ($this->getRequest()->getParam('id')) {
            $this->setDefaultFilter(['in_products'=>1]);
        }
    }
    /**
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     * @return $this
     */
     // @codingStandardsIgnoreStart
    protected function _addColumnFilterToCollection($column)
    {
    // Set custom filter for in product flag
        if ($column->getId() == 'in_products') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
            } else {
                if ($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $productIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }
    /**
     * prepare the collection
     * @return $this
     */
    protected function _prepareCollection()
    {
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('price');
        $adminStore = Store::DEFAULT_STORE_ID;
        $collection->joinAttribute('product_name', 'catalog_product/name', 'entity_id', null, 'left', $adminStore);
        $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'left', $adminStore);
        $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'left', $adminStore);
        $this->setCollection($collection);
        parent::_prepareCollection();
        return $this;
    }
    
    /**
     * @return $this
     */
    protected function _prepareMassaction()
    {
        return $this;
    }

    /**
     * @return $this
     */
    protected function _prepareColumns()
    {
        
        $this->addColumn(
            'in_products',
            [
                'header_css_class'  => 'a-center',
                'type'   => 'checkbox',
                'name'   => 'in_products',
                'values' => $this->_getSelectedProducts(),
                'align'  => 'center',
                'index'  => 'entity_id',
            ]
        );
        $this->addColumn(
            'product_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'entity_id',
                'type' => 'number',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'product_name',
            [
                'header' => __('Name'),
                'index' => 'product_name',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'type',
            [
                'header' => __('Type'),
                'index' => 'type_id',
                'type' => 'options',
                'options' => $this->type->getOptionArray(),
                'header_css_class' => 'col-type',
                'column_css_class' => 'col-type'
            ]
        );
        /** @var \Magento\Catalog\Model\ResourceModel\Product $resource */
        $resource = $this->productFactory->create()->getResource();
        $sets = $this->setCollectionFactory->create()->setEntityTypeFilter(
            $resource->getTypeId()
        )->load()->toOptionHash();

        $this->addColumn(
            'set_name',
            [
                'header' => __('Attribute Set'),
                'index' => 'attribute_set_id',
                'type' => 'options',
                'options' => $sets,
                'header_css_class' => 'col-attr-name',
                'column_css_class' => 'col-attr-name'
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $this->status->getOptionArray(),
                'header_css_class' => 'col-status',
                'column_css_class' => 'col-status'
            ]
        );

        $this->addColumn(
            'visibility',
            [
                'header' => __('Visibility'),
                'index' => 'visibility',
                'type' => 'options',
                'options' => $this->visibility->getOptionArray(),
                'header_css_class' => 'col-visibility',
                'column_css_class' => 'col-visibility'
            ]
        );

        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'index' => 'sku',
                'header_css_class' => 'col-sku',
                'column_css_class' => 'col-sku'
            ]
        );

        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'type' => 'currency',
                'currency_code' => (string)$this->_scopeConfig->getValue(
                    Currency::XML_PATH_CURRENCY_BASE,
                    ScopeInterface::SCOPE_STORE
                ),
                'index' => 'price',
                'header_css_class' => 'col-price',
                'column_css_class' => 'col-price'
            ]
        );
    }
    protected function _getSelectedProducts()
    {
        $selected = $this->getArpConditionProduct();
        
        return $selected;
    }
    // @codingStandardsIgnoreEnd
    /**
     * Retrieve selected products
     *
     * @return array
     */
    public function getSelectedProducts()
    {
        $selected = $this->getArpConditionProduct();
        if (!is_array($selected)) {
            $selected = [];
        }
       
        return $selected;
    }

    public function getArpConditionProduct()
    {
        $ruleId = $this->getRequest()->getParam('id');
        $actAttribute = $this->getRequest()->getParam('act');
        $ruleModel   = $this->productruleFactory->create();
        if ($ruleId) {
            $ruleModel->load($ruleId);
        }
        if($actAttribute == 1) {
            $conditionsProductsIds = explode(',', $ruleModel->getProductsIdsConditions());
        } else {
            $conditionsProductsIds = explode(',', $ruleModel->getProductsIdsActions());
        }
        return $conditionsProductsIds;
    }
    
    /**
     * @param \Magento\Catalog\Model\Product|\Magento\Framework\Object $item
     * @return string
     */
    public function getRowUrl($item)
    {
        return '#';
    }
    /**
     * get grid url
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
            '*/*/productsGrid',
            [
                'id' => $this->getRequest()->getParam('id')
            ]
        );
    }
    /**
     * @return string
     */
    public function getTabLabel()
    {
        return __('Products');
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return string
     */
    public function getTabTitle()
    {
        return $this->getTabLabel();
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        return true;
    }

    /**
     * @return string
     */
    public function getTabUrl()
    {
        return $this->getUrl('faqs/faq/products', ['_current' => true]);
    }

    /**
     * @return string
     */
    public function getTabClass()
    {
        return 'ajax only';
    }
}
