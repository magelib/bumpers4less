<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Block\Adminhtml\Finder\Edit\Tab\Products\Grid;

class ColumnSet extends \Magento\Backend\Block\Widget\Grid\ColumnSet
{
    /**
     * Core registry
     *
     * @var \Amasty\Finder\Model\Finder $finder
     */
    protected $_finder;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Backend\Model\Widget\Grid\Row\UrlGeneratorFactory $generatorFactory,
        \Magento\Backend\Model\Widget\Grid\SubTotals $subtotals,
        \Magento\Backend\Model\Widget\Grid\Totals $totals,
        \Magento\Framework\Registry $registry,
        array $data
    ) {
        /**
         * @var \Amasty\Finder\Model\Finder $finder
         */
        $this->_finder = $registry->registry('current_amasty_finder_finder');
        parent::__construct(
            $context, $generatorFactory, $subtotals, $totals, $data
        );

    }

    protected function _prepareLayout()
    {

        foreach($this->_finder->getDropdowns() as $d) {
            $i = $d->getPos();
            $this->addColumn("name$i", array(
                'header'    => $d->getName(),
                'index'     => "name$i",
                'filter_index'     => "d$i.name",
            ));
        }

        $this->addColumn('sku', array(
            'header'    => __('SKU'),
            'index'     => 'sku',
        ));

        $this->addColumn('action', array(
            'header'    => __('Action'),
            'width'     => '50px',
            'type'      => 'action',
            'getter'    => 'getVid',
            'actions'   => array(
                array(
                    'caption' => __('Delete'),
                    'url'     => array('base'=>'amasty_finder/value/delete', 'params'=>array('finder_id'=>$this->_finder->getId())),
                    'field'   => 'id',
                    //'extraParamsTemplate' => ['finder_id'=>'getFinderId'],

                    'confirm' => __('Are you sure?')
                )
            ),
            'filter'    => false,
            'sortable'  => false,
            'is_system' => true,
        ));
        return parent::_prepareLayout();
    }


    public function addColumn($title, $data)
    {
        $column = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Grid\Column', $title)->addData($data);
        $this->setChild($title, $column);
    }

/*
    public function getRowUrl($item)
    {
        return $this->getUrl('amasty_finder/value/edit', ['id' => $item->getVid(), 'finder_id' => $this->_finder->getId()]);
    }
*/

}
