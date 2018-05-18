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

/**
 * Customers defined options.
 */

namespace Magedelight\Bundlediscount\Block\Adminhtml\Catalog\Product\Edit\Tab\Options;

use Magento\Backend\Block\Widget;
use Magento\Catalog\Model\Product;

class Option extends Widget
{
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
    protected $_template = 'catalog/product/edit/tab/bundles/option.phtml';

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
     * Customer Group factory.
     *
     * @var \Magento\Customer\Model\GroupFactory
     */
    protected $_customerGroupFactory;

    /**
     * @var \Magento\Store\Model\System\Store
     */
    protected $_systemStore;

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
        \Magento\Config\Model\Config\Source\Yesno $configYesNo,
        \Magento\Catalog\Model\Config\Source\Product\Options\Type $optionType,
        Product $product,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Model\GroupFactory $customerGroupFactory,
        \Magento\Store\Model\System\Store $systemStore,
        \Magento\Catalog\Model\ProductOptions\ConfigInterface $productOptionConfig,
        array $data = []
    ) {
        $this->_optionType = $optionType;
        $this->_configYesNo = $configYesNo;
        $this->_product = $product;
        $this->_productOptionConfig = $productOptionConfig;
        $this->_coreRegistry = $registry;
        $this->_customerGroupFactory = $customerGroupFactory;
        $this->_systemStore = $systemStore;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve options field name prefix.
     *
     * @return string
     */
    public function getFieldName()
    {
        return 'bundlediscount_options';
    }

    /**
     * Retrieve options field id prefix.
     *
     * @return string
     */
    public function getFieldId()
    {
        return 'bundlediscount_option';
    }

    /**
     * @return $this
     */
    protected function _prepareLayout()
    {
        $this->addChild(
            'add_selection_button',
            'Magento\Backend\Block\Widget\Button',
            ['label' => __('Add Product'), 'class' => 'add', 'id' => $this->getFieldId().'_{{index}}_add_button']
        );

        $this->addChild(
            'close_search_button',
            'Magento\Backend\Block\Widget\Button',
            ['label' => __('Close'), 'class' => 'back no-display', 'id' => $this->getFieldId().'_{{index}}_close_button']
        );

        $this->addChild(
            'option_delete_button',
            'Magento\Backend\Block\Widget\Button',
            ['label' => __('Delete Bundle'), 'class' => 'delete delete-product-option']
        );
        $this->addChild('md_bundlediscount_selection_template', 'Magedelight\Bundlediscount\Block\Adminhtml\Catalog\Product\Edit\Tab\Options\Selection');

        return parent::_prepareLayout();
    }

    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }

    public function getCloseSearchButtonHtml()
    {
        return $this->getChildHtml('close_search_button');
    }

    public function getAddSelectionButtonHtml()
    {
        return $this->getChildHtml('add_selection_button');
    }
    public function getOptionDeleteButtonHtml()
    {
        return $this->getChildHtml('option_delete_button');
    }

    public function getSelectionHtml()
    {
        return $this->getChildHtml('md_bundlediscount_selection_template');
    }

    public function getBundles()
    {
        $data = array();
        $productId = $this->getRequest()->getParam('id');

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $bundles = $objectManager->create('Magedelight\Bundlediscount\Model\Bundlediscount')->getCollection()
                ->addFieldToSelect('*')->addFieldToFilter('product_id', array('eq' => $productId))
                ->setOrder('sort_order', 'ASC');

        foreach ($bundles as $bundle) {
            $bundleId = $bundle->getId();

            $items = $objectManager->create('Magedelight\Bundlediscount\Model\Bundleitems')->getCollection()
                        ->addFieldToFilter('bundle_id', array('eq' => $bundleId))
                        ->setOrder('sort_order', 'ASC');

            foreach ($items as $item) {
                $productId = $item->getProductId();
                $productModel = $objectManager->create('Magento\Catalog\Model\Product');
                $product = $productModel->load($productId);

                $items->getItemByColumnValue('item_id', $item->getId())
                        ->setName($product->getName())
                        ->setSku($product->getSku());
            }
            $bundle->setData('selections', $items);
            $data[$bundleId] = $bundle;
        }

        return $data;
    }

    public function getStoreViewOptions()
    {
        $storeOptions = $this->_systemStore->getStoreValuesForForm();
        $optionString = '';
        foreach ($storeOptions as $options) {
            if (!is_array($options['value'])) {
                $optionString .= '<option value="'.$options['value'].'">'.$options['label'].'   </option>';
            } else {
                $optionString .= '<optgroup label="'.$options['label'].'">';
                foreach ($options['value'] as $suboptions) {
                    $optionString .= '<option value="'.$suboptions['value'].'">'.$suboptions['label'].'</option>';
                }
                $optionString .= '</optgroup>';
            }
        }

        return $optionString;
    }

    public function getCustomerGroupsOptions()
    {
        $groupCollection = $this->_customerGroupFactory->create()->getCollection()
            ->load()
            ->toOptionHash();
        $optionString = '';
        foreach ($groupCollection as $groupId => $code) {
            $optionString .= '<option value="'.$groupId.'">'.$code.'</option>';
        }

        return $optionString;
    }
}
