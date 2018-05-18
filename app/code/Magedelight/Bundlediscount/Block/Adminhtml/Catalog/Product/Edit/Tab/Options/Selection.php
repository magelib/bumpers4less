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

namespace Magedelight\Bundlediscount\Block\Adminhtml\Catalog\Product\Edit\Tab\Options;

use Magento\Backend\Block\Widget;
use Magento\Catalog\Model\Product;

class Selection extends Widget
{
    protected $_currentProductId = null;
    /**
     * @var Product
     */
    protected $_productInstance;

    /**
     * @var \Magento\Framework\DataObject[]
     */
    protected $_values;

    /**
     * @var int
     */
    protected $_itemCount = 1;

    /**
     * @var string
     */
    protected $_template = 'catalog/product/edit/tab/bundles/option/selection.phtml';

    /**
     * Core registry.
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var \Magento\Catalog\Model\ProductOptions\ConfigInterface
     */
    protected $_productOptionConfig;

    /**
     * @var Product
     */
    protected $_product;

    /**
     * @var \Magento\Config\Model\Config\Source\Yesno
     */
    protected $_configYesNo;

    /**
     * @var \Magento\Catalog\Model\Config\Source\Product\Options\Type
     */
    protected $_optionType;

    /**
     * @param \Magento\Backend\Block\Template\Context                   $context
     * @param \Magento\Config\Model\Config\Source\Yesno                 $configYesNo
     * @param \Magento\Catalog\Model\Config\Source\Product\Options\Type $optionType
     * @param Product                                                   $product
     * @param \Magento\Framework\Registry                               $registry
     * @param \Magento\Catalog\Model\ProductOptions\ConfigInterface     $productOptionConfig
     * @param array                                                     $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Retrieve options field name prefix.
     *
     * @return string
     */
    public function getFieldName()
    {
        return 'bundlediscount_selections';
    }

    /**
     * Retrieve options field id prefix.
     *
     * @return string
     */
    public function getFieldId()
    {
        return 'bundlediscount_selection';
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'selection_delete_button',
            'Magento\Backend\Block\Widget\Button',
            ['label' => __('Delete'), 'class' => 'delete icon-btn', 'id' => $this->getFieldId().'_{{index}}_add_button']
        );

        return parent::_prepareLayout();
    }

    public function getSelectionDeleteButtonHtml()
    {
        return $this->getChildHtml('selection_delete_button');
    }
    public function getSelectionSearchUrl()
    {
        $this->_currentProductId = $this->getRequest()->getParam('id');

        return $this->getUrl('md_bundlediscount/index/search', array('id' => $this->_currentProductId));
    }
}
