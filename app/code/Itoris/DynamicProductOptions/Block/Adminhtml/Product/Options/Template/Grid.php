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

/**
 * @method setOptionsConfig()
 * @method \Itoris\DynamicProductOptions\Model\Options getOptionsConfig()
 */
//app/code/Itoris/DynamicProductOptions/Block/Adminhtml/Product/Options/Template/Grid.php
namespace Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options\Template;

class Grid extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        parent::__construct($context, $backendHelper, $data);
    }

    protected  function _construct() {
        parent::_construct();
        $this->setId('template_grid');
        $this->setDefaultSort('template_id');
        $this->setDefaultDir('ASC');
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
        $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $con = $res->getConnection('read');

        $collection = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Template')
                            ->getCollection()
                            ->addFieldToFilter('store_id', array('eq' => 0));
        foreach($collection as $template) {
            $productCount = (int) $con->fetchOne("select count(`config_id`) from {$res->getTableName('itoris_dynamicproductoptions_template_product')} where `template_id`={$template->getId()}");
            $template->setAssociated($productCount);
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Add grid columns
     *
     * @return \Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options\Template\Grid
     */
    protected function _prepareColumns() {
        $this->addColumn('template_id', [
            'header' => $this->escapeHtml(__('ID')),
            'type' => 'range',
            'index' => 'template_id',
            'sortable'  => true,
        ]);

        $this->addColumn('name', [
            'header' => $this->escapeHtml(__('Template Name')),
            'index' => 'name',
            'sortable'  => true,
        ]);
        
        $this->addColumn('associated', [
            'header' => $this->escapeHtml(__('Products Associated')),
            'index' => 'associated',
            'sortable'  => true,
        ]);       
        
        $this->addColumn('action',
            [
                'header'    =>  $this->escapeHtml(__('Action')),
                'width'     => '100',
                'type'      => 'action',
                'getter'    => 'getId',
                'actions'   => [
                    [
                        'caption'   => $this->escapeHtml(__('Edit')),
                        'url'       => ['base'=> '*/*/edit'],
                        'field'     => 'id'
                    ],
                    [
                        'caption'   => $this->escapeHtml(__('Clone')),
                        'url'       => ['base'=> '*/*/clonetemplate'],
                        'field'     => 'id'
                    ],
                    [
                        'caption'   => $this->escapeHtml(__('Delete')),
                        'url'       => ['base'=> '*/*/delete'],
                        'field'     => 'id'
                    ]
                ],
                'filter'    => false,
                'sortable'  => false,
                'is_system' => true,
            ]);

        parent::_prepareColumns();
        return $this;
    }

    protected function _prepareMassaction() {return;
        $this->setMassactionIdField('template_id');
        $this->getMassactionBlock()->setFormFieldName('template');

        $this->getMassactionBlock()->addItem('delete', [
            'label'    => $this->escapeHtml(__('Delete')),
            'url'      => $this->getUrl('*/*/massDelete'),
            'confirm'  => $this->escapeHtml(__('Are you sure want to delete selected templates?'))
        ]);

        return $this;
    }

    /**
     * Retrieve row click URL
     *
     * @param \Magento\Framework\Object $row
     *
     * @return string
     */
    public function getRowUrl($row) {
        return $this->getUrl('*/*/edit', ['id' => $row->getTemplateId()]);
    }

}