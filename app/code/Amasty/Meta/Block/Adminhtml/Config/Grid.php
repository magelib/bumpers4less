<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Meta
 */

namespace Amasty\Meta\Block\Adminhtml\Config;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{

    /**
     * @var \Amasty\Meta\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\Catalog\Model\Category
     */
    protected $_category;

    /**
     * @var \Amasty\Meta\Helper\Data
     */
    protected $_helper;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Catalog\Model\ResourceModel\Category $category,
        \Amasty\Meta\Helper\Data $helper,
        \Amasty\Meta\Model\Config $config,
        array $data = []
    ) {
        $this->_config = $config;
        $this->_storeManager = $context->getStoreManager();
        $this->_category = $category;
        $this->_helper = $helper;
        parent::__construct($context, $backendHelper, $data);
    }

    public function _construct()
    {
        parent::_construct();
        $this->setId('configGrid');
        $this->setDefaultSort('config_id');
    }

    protected function _prepareCollection()
    {
        $collection = $this->_config->getCollection();

        $root =  $collection->getConnection()->quote(' - ' . __('Root'));
        $title = $this->_category->getAttribute('name');


        $collection->getSelect()
            ->joinLeft(
                array('cce' => $collection->getTable('catalog_category_entity')),
                'cce.entity_id = main_table.category_id',
                []
            )
            ->joinLeft(
                array('att' => $title->getBackend()->getTable()),
                $collection->getConnection()->quoteInto('att.' . $title->getBackend()->getEntityIdField(
                    ) . ' = cce.entity_id AND
                    att.attribute_id = ?', $title->getId()
                ),
                array('category_name' => new \Zend_Db_Expr("COALESCE(value, $root)"))
            )
            ->group('main_table.config_id');

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('config_id',
            array(
                'header' => __('ID'),
                'align'  => 'right',
                'width'  => '50px',
                'index'  => 'config_id',
            ));

        $this->addColumn('category_id',
            array(
                'header'     => __('Category'),
                'index'      => 'category_id',
                'renderer'   => 'Amasty\Meta\Block\Adminhtml\Widget\Grid\Column\Renderer\Category',
                'filter'     => 'Magento\Backend\Block\Widget\Grid\Column\Filter\Select',
                'options'    => $this->_helper->getTree(true),
            ));


        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header'     => __('Store'),
                'index'      => 'store_id',
                'filter_index'      => 'main_table.store_id',
                'type'       => 'store',
                'renderer'   => 'Amasty\Meta\Block\Adminhtml\Widget\Grid\Column\Renderer\Store',
                'filter'     => 'Amasty\Meta\Block\Adminhtml\Widget\Grid\Column\Filter\Store',
                'store_view' => true,
                'sortable'   => false
            ));
        }

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('config_id');
        $this->getMassactionBlock()->setFormFieldName('configs');

        $this->getMassactionBlock()->addItem('delete',
            array(
                'label'   => __('Delete'),
                'url'     => $this->getUrl('*/*/massAction'),
                'confirm' => __('Are you sure?')
            ));

        return $this;
    }
}