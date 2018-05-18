<?php
/**
 * ITORIS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the ITORIS's Magento Extensions License Agreement
 * which is available through the world-wide-web at this URL:
 * http://www.itoris.com/magento-extensions-license.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to sales@itoris.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extensions to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to the license agreement or contact sales@itoris.com for more information.
 *
 * @category   ITORIS
 * @package    ITORIS_M2_DYNAMIC_PRODUCT_OPTIONS
 * @copyright  Copyright (c) 2016 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

namespace Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options\Product;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    protected $_objectManager = null;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        //$this->_backendSession = $context->getBackendSession();
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->setId('dynamic_options_product_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
    }

    /**
     * Prepare collection to be displayed in the grid
     *
     * @return \Magento\Sales\Block\Adminhtml\Order\Create\Search
     */
    protected function _prepareCollection()
    {
        $attributes = $this->_objectManager->get('Magento\Catalog\Model\Config')->getProductAttributes();
        /* @var $collection \Magento\Catalog\Model\ResourceModel\Product\Collection */
        $collection = $this->_objectManager->create('Magento\Catalog\Model\Product')->getCollection();
        $collection
            /*->setStore($this->getStore())*/
            ->addAttributeToSelect($attributes)
            ->addAttributeToSelect('sku')
            ->addFieldToFilter('type_id', ['in' => ['simple', 'virtual']])
            ->addStoreFilter();

        //$this->_objectManager->get('Magento\Catalog\Model\Product\Status')->addSaleableFilterToCollection($collection);

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare columns
     *
     * @return \Magento\Sales\Block\Adminhtml\Order\Create\Search\Grid
     */
    protected function _prepareColumns()
    {
        $this->addColumn('entity_id', [
            'header'    => $this->escapeHtml(__('ID')),
            'sortable'  => true,
            'width'     => '60',
            'index'     => 'entity_id'
        ]);
        $this->addColumn('name', [
            'header'    => $this->escapeHtml(__('Product Name')),
            'index'     => 'name'
        ]);
        $this->addColumn('sku', [
            'header'    => $this->escapeHtml(__('SKU')),
            'width'     => '80',
            'index'     => 'sku'
        ]);
        $this->addColumn('price', [
            'header'    => $this->escapeHtml(__('Price')),
            'column_css_class' => 'price',
            'align'     => 'center',
            'type'      => 'currency',
            'currency_code' => $this->getStore()->getCurrentCurrencyCode(),
            'rate'      => $this->getStore()->getBaseCurrency()->getRate($this->getStore()->getCurrentCurrencyCode()),
            'index'     => 'price',
        ]);

        $this->addColumn('add_product', [
            'header'    => $this->escapeHtml(__('Select')),
            'header_css_class' => 'a-center',
            'type'      => 'text',
            'name'      => 'entity_id',
            'align'     => 'center',
            'index'     => 'entity_id',
            'sortable'  => false,
            'filter'    => false,
            'renderer'  => 'Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options\Product\Grid\Column\Link',
        ]);

        return parent::_prepareColumns();
    }

    public function getStore() {
        return $this->_storeManager->getStore($this->getRequest()->getParam('store'));
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/productGrid', ['_current' => true, 'collapse' => null]);
    }

    public function getRowClickCallback() {
        return "function() { return false; };";
    }

}