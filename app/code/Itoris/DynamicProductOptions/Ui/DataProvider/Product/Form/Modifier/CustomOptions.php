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

namespace Itoris\DynamicProductOptions\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier;
use Magento\Ui\Component\Form;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\Component\Container;

class CustomOptions extends \Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\CustomOptions
{
    protected function createCustomOptionsPanel()
    {
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        if (!$this->isEnabled()) return parent::createCustomOptionsPanel(); else parent::createCustomOptionsPanel();
        
        $block = $this->_objectManager->create('Itoris\DynamicProductOptions\Block\Adminhtml\Product\Edit\Tab\Options');
        $this->meta = array_replace_recursive(
            $this->meta,
            [
                static::GROUP_CUSTOM_OPTIONS_NAME => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'label' => __('Dynamic Product Options'),
                                'componentType' => Fieldset::NAME,
                                'dataScope' => static::GROUP_CUSTOM_OPTIONS_SCOPE,
                                'collapsible' => true,
                                'sortOrder' => $this->getNextGroupSortOrder(
                                    $this->meta,
                                    static::GROUP_CUSTOM_OPTIONS_PREVIOUS_NAME,
                                    static::GROUP_CUSTOM_OPTIONS_DEFAULT_SORT_ORDER
                                ),
                            ],
                        ],
                    ],
                    'children' => [
                        'DPO' => [
                            'arguments' => [
                                'data' => [
                                    'config' => [
                                        'label' => null,
                                        'formElement' => Container::NAME,
                                        'componentType' => Container::NAME,
                                        'template' => 'ui/form/components/complex',
                                        'sortOrder' => 10,
                                        'content' => $block->toHtml(),
                                    ],
                                ],
                            ],
                            'children' => [
                            ]
                        ]
                    ]
                ]
            ]
        );
        unset($this->meta['custom_options']['children']['container_header']);
        unset($this->meta['custom_options']['children']['affect_product_custom_options']);
        unset($this->meta['custom_options']['children']['options']);
        unset($this->meta['import_options_modal']);
        //print_r($this->meta); exit;
        return $this;
    }
    
    public function isEnabled() {
        return $this->getDataHelper()->isAdminRegistered() && $this->getDataHelper()->getSettings(true)->getEnabled();
    }
    
    protected function getDataHelper() {
        return $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Data');
    }
}