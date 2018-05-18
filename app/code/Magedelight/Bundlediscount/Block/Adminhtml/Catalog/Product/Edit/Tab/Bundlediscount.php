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

namespace Magedelight\Bundlediscount\Block\Adminhtml\Catalog\Product\Edit\Tab;

use Magento\Catalog\Model\Locator\LocatorInterface;

/**
 * Products mass update inventory tab.
 */
class Bundlediscount extends \Magento\Backend\Block\Widget implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    protected $_template = 'catalog/product/edit/tab/bundles.phtml';

    /**
     * @var LocatorInterface
     */
    protected $locator;
    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array                                   $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
            LocatorInterface $locator,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->locator = $locator;
    }

    /**
     * Retrieve field suffix.
     *
     * @return string
     */
    public function getFieldSuffix()
    {
        return 'bundlediscount';
    }

    /**
     * Retrieve current store id.
     *
     * @return int
     */
    public function getStoreId()
    {
        $storeId = $this->getRequest()->getParam('store');

        return intval($storeId);
    }

    /**
     * Tab settings.
     *
     * @return \Magento\Framework\Phrase
     */
    public function getTabLabel()
    {
        return __('Bundle Promotions');
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getTabTitle()
    {
        return __('Bundle Promotions');
    }

    /**
     * @return bool
     */
    public function canShowTab()
    {
        $id = $this->getRequest()->getParam('id');
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($id);
        if ($product->getTypeId() != 'grouped') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return false;
    }

    /**
     * @return Widget
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'add_button',
            'Magento\Backend\Block\Widget\Button',
            ['label' => __('Add New Bundle'), 'class' => 'add action-default primary', 'id' => 'add_new_defined_bundle', 'on_click' => 'pbOption.add()']
        );
        $this->addChild('md_bundlediscount_options_box', 'Magedelight\Bundlediscount\Block\Adminhtml\Catalog\Product\Edit\Tab\Options\Option');

        return parent::_prepareLayout();
    }

    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }

    public function getOptionsBoxHtml()
    {
        return $this->getChildHtml('md_bundlediscount_options_box');
    }

    /**
     * Retrieve currently edited product object.
     *
     * @return Product
     */
    public function getProduct()
    {
        $product_id = $this->getRequest()->getParam('id');

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->create('Magento\Catalog\Model\Product')->load($product_id);

        return $product;
    }
}
