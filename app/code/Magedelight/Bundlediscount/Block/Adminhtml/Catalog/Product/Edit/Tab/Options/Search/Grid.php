<?php

/**
 * Magedelight
 * Copyright (C) 2016 Magedelight <info@magedelight.com>.
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
 *
 * @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 */

namespace Magedelight\Bundlediscount\Block\Adminhtml\Catalog\Product\Edit\Tab\Options\Search;

/**
 * Adminhtml sales order create search products block.
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * Sales config.
     *
     * @var \Magento\Sales\Model\Config
     */
    protected $_salesConfig;

    /**
     * @var \Magento\Catalog\Model\Product\Type
     */
    protected $_type;

    /**
     * Session quote.
     *
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;

    /**
     * Catalog config.
     *
     * @var \Magento\Catalog\Model\Config
     */
    protected $_catalogConfig;

    /**
     * Customer factory.
     *
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $_customerFactory;

    /**
     * Customer Group factory.
     *
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $_customerGroupFactory;

    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * @var \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory]
     */
    protected $_setsFactory;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $_visibility;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data            $backendHelper
     * @param \Magento\Catalog\Model\ProductFactory   $productFactory
     * @param \Magento\Catalog\Model\Config           $catalogConfig
     * @param \Magento\Backend\Model\Session\Quote    $sessionQuote
     * @param \Magento\Sales\Model\Config             $salesConfig
     * @param \Magento\Framework\Module\Manager       $moduleManager
     * @param array                                   $data
     */
    public function __construct(
    \Magento\Backend\Block\Template\Context $context, \Magento\Backend\Helper\Data $backendHelper, \Magento\Catalog\Model\ProductFactory $productFactory, \Magento\Catalog\Model\Config $catalogConfig, \Magento\Backend\Model\Session\Quote $sessionQuote, \Magento\Sales\Model\Config $salesConfig, \Magento\Catalog\Model\Product\Type $type, \Magento\Framework\Module\Manager $moduleManager, \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $setsFactory, \Magento\Catalog\Model\Product\Visibility $visibility, array $data = []
    ) {
        $this->_productFactory = $productFactory;
        $this->_catalogConfig = $catalogConfig;
        $this->_sessionQuote = $sessionQuote;
        $this->_salesConfig = $salesConfig;
        $this->_type = $type;
        $this->moduleManager = $moduleManager;
        $this->_setsFactory = $setsFactory;
        $this->_visibility = $visibility;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Constructor.
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('bundlediscount_selection_search_grid');
        $this->setRowClickCallback('pbSelection.productGridRowClick.bind(pbSelection)');
        $this->setCheckboxCheckCallback('pbSelection.productGridCheckboxCheck.bind(pbSelection)');
        $this->setRowInitCallback('pbSelection.productGridRowInit.bind(pbSelection)');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
        if ($this->getRequest()->getParam('collapse')) {
            $this->setIsCollapsed(true);
        }
    }

    protected function _beforeToHtml()
    {
        $index = $this->getRequest()->getParam('index');
        $this->setIndex($index);
        $this->setId($this->getId().'_'.$this->getIndex());

        return parent::_beforeToHtml();
    }

    /**
     * Retrieve quote store object.
     *
     * @return \Magento\Store\Model\Store
     */
    public function getStore()
    {
        return $this->_sessionQuote->getStore();
    }

    /**
     * Retrieve quote object.
     *
     * @return \Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        return $this->_sessionQuote->getQuote();
    }

    /**
     * Add column filter to collection.
     *
     * @param \Magento\Backend\Block\Widget\Grid\Column $column
     *
     * @return $this
     */
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
     * @return Store
     */
    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);

        return $this->_storeManager->getStore($storeId);
    }

    /**
     * Prepare collection to be displayed in the grid.
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $this->_currentProductId = $this->getRequest()->getParam('id', null);
        $attributes = $this->_catalogConfig->getProductAttributes();
        $store = $this->_getStore();
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $collection = $this->_productFactory->create()->getCollection();
        $collection->setStore($this->getStore())
                ->addAttributeToSelect($attributes)
                ->addAttributeToSelect('sku')
                ->addStoreFilter()
                ->addAttributeToFilter('type_id', array('simple', 'downloadable', 'configurable', 'bundle', 'virtual'))
                ->addAttributeToFilter('entity_id', array('neq' => $this->_currentProductId));

        if ($this->moduleManager->isEnabled('Magento_CatalogInventory')) {
            $collection->joinField(
                    'qty', 'cataloginventory_stock_item', 'qty', 'product_id=entity_id', '{{table}}.stock_id=1', 'left'
            );
        }

        $collection->joinAttribute(
                'visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', $store->getId()
        );

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * Prepare columns.
     *
     * @return $this
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
                'in_products', [
            'type' => 'checkbox',
            'name' => 'in_products',
            'values' => $this->_getSelectedProducts(),
            'align' => 'center',
            'index' => 'entity_id',
            'header_css_class' => 'col-select',
            'column_css_class' => 'col-select',
                ]
        );
        $this->addColumn(
                'entity_id', [
            'header' => __('ID'),
            'sortable' => true,
            'header_css_class' => 'col-id',
            'column_css_class' => 'col-id',
            'width' => '60px',
            'index' => 'entity_id',
                ]
        );
        $this->addColumn(
                'name', [
            'header' => __('Product Name'),
            'index' => 'name',
            'column_css_class' => 'name',
                ]
        );

        $sets = $this->_setsFactory->create()->setEntityTypeFilter(
                        $this->_productFactory->create()->getResource()->getTypeId()
                )->load()->toOptionHash();

        $this->addColumn(
                'set_name', [
            'header' => __('Attribute Set'),
            'index' => 'attribute_set_id',
            'type' => 'options',
            'options' => $sets,
            'header_css_class' => 'col-attr-name',
            'column_css_class' => 'col-attr-name',
                ]
        );

        $this->addColumn(
                'type', [
            'header' => __('Type'),
            'index' => 'type_id',
            'type' => 'options',
            'options' => $this->_type->getOptionArray(),
                ]
        );
        $this->addColumn('sku', [
            'header' => __('SKU'),
            'index' => 'sku',
            'column_css_class' => 'sku',
        ]);
        $this->addColumn(
                'price', [
            'header' => __('Price'),
            'column_css_class' => 'price',
            'type' => 'currency',
            'currency_code' => $this->getStore()->getCurrentCurrencyCode(),
            'rate' => $this->getStore()->getBaseCurrency()->getRate($this->getStore()->getCurrentCurrencyCode()),
            'index' => 'price',
            'renderer' => 'Magedelight\Bundlediscount\Block\Adminhtml\Catalog\Price\Search\Grid\Renderer\Price',
                ]
        );
        $this->addColumn(
                'visibility', [
            'header' => __('Visibility'),
            'index' => 'visibility',
            'type' => 'options',
            'options' => $this->_visibility->getOptionArray(),
            'header_css_class' => 'col-visibility',
            'column_css_class' => 'col-visibility',
                ]
        );

        $this->addColumn(
                'qty', [
            'header' => __('Quantity to Add'),
            'filter' => false,
            'sortable' => false,
            'name' => 'qty',
            'type' => 'input',
            'validate_class' => 'validate-number',
            //'index'     => 'qty',
            'inline_css' => 'qty',
                ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Get grid url.
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl(
                        'md_bundlediscount/*/loadblock', ['block' => 'product_grid', 'index' => $this->getIndex(), '_current' => true, 'collapse' => null]
        );
    }

    /**
     * Get selected products.
     *
     * @return mixed
     */
    protected function _getSelectedProducts()
    {
        $products = $this->getRequest()->getPost('products', []);

        return $products;
    }

    /**
     * Add custom options to product collection.
     *
     * @return $this
     */
    protected function _afterLoadCollection()
    {
        $this->getCollection();

        return parent::_afterLoadCollection();
    }
}
