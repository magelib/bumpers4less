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

namespace Itoris\DynamicProductOptions\Helper;

class ProductType extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ){
        $this->_storeManager = $storeManager;
        $this->_objectManager = $objectManager;
        parent::__construct($context);
    }

    public function checkDynamicOptions($product, $preparedOptionValues = null) {
        $config = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Options')
            ->setStoreId($this->_storeManager->getStore()->getId())
            ->load($product->getId());
        if (!$config->getId()) {
            $config->setStoreId(0)->load($product->getId());
        }
        if (!$config->getId()) {
            return $this;
        }
        $originalIds = [];
        $requiredOptions = [];
        $optionValues = [];
        foreach ($product->getProductOptionsCollection() as $option) {
            $originalIds[] = $option->getId();
            if ($option->getIsRequire()) {
                $requiredOptions[$option->getId()] = $option;
            }
            $customOption = $product->getCustomOption('option_' . $option->getId());
            if ($customOption && $customOption->getValue()) {
                $optionValues[$option->getId()] = $this->prepareOptionValue($option, $customOption->getValue());
            }
        }
        if (is_array($preparedOptionValues)) {
            $optionValues = $preparedOptionValues;
        }
        if (!empty($requiredOptions)) {
            $dynamicOptions = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option')->getCollection()
                ->addFieldToFilter('product_id', ['eq' => $product->getId()])
                ->addFieldToFilter('store_id', ['eq' => $config->getStoreId()])
                ->addFieldToFilter('orig_option_id', ['in' => $originalIds]);
            if (count($dynamicOptions)) {
                $optionsByInternalId = [];
                $valuesByInternalId = [];
                foreach ($dynamicOptions as $dynamicOption) {
                    if ($dynamicOption->getConfiguration()) {
                        $configuration = \Zend_Json::decode($dynamicOption->getConfiguration());
                        if (isset($configuration['internal_id'])) {
                            $dynamicOption->setConfiguration($configuration);
                            $optionsByInternalId[$configuration['internal_id']] = $dynamicOption;
                            if (isset($optionValues[$dynamicOption->getOrigOptionId()])) {
                                $valuesByInternalId[$configuration['internal_id']] = $optionValues[$dynamicOption->getOrigOptionId()];
                            }
                        }
                    }
                }
                $sections = $config->getSections();
                foreach ($sections as $section) {
                    if (isset($section['visibility_condition'])) {
                        $condition = \Zend_Json::decode($section['visibility_condition']);
                        $skipSectionRequiredOptions = false;
                        if ($this->getConditionHelper()->isConditionCorrect($condition, $valuesByInternalId)) {
                            if ($section['visibility'] == 'visible') {
                                $skipSectionRequiredOptions = true;
                            }
                        } else {
                            if ($section['visibility_action'] == 'visible') {
                                $skipSectionRequiredOptions = true;
                            }
                        }
                        if ($skipSectionRequiredOptions) {
                            foreach ($section['fields'] as $field) {
                                if (isset($field['option_id'])) {
                                    $product->setData('skip_required_option' . $field['option_id'], 1);
                                }
                            }
                        }
                    }
                }
                $currentCustomerGroup = (int)$this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Data')->getCustomerGroupId();
                foreach ($optionsByInternalId as $_option) {
                    if (isset($requiredOptions[$_option->getOrigOptionId()])) {
                        $configuration = $_option->getConfiguration();
                        if (@$configuration['visibility_condition']) {
                            $condition = \Zend_Json::decode($configuration['visibility_condition']);
                            if ($this->getConditionHelper()->isConditionCorrect($condition, $valuesByInternalId)) {
                                if ($configuration['visibility'] == 'visible') {
                                    $product->setData('skip_required_option' . $_option->getOrigOptionId(), 1);
                                }
                            } else {
                                if ($configuration['visibility_action'] == 'visible') {
                                    $product->setData('skip_required_option' . $_option->getOrigOptionId(), 1);
                                }
                            }
                        } elseif ($configuration['visibility'] == 'hidden' || $configuration['visibility'] == 'disabled') {
                            $product->setData('skip_required_option' . $_option->getOrigOptionId(), 1);
                        } elseif (isset($configuration['customer_group']) && $configuration['customer_group'] != '' && intval($configuration['customer_group']) != $currentCustomerGroup) {
                            $product->setData('skip_required_option' . $_option->getOrigOptionId(), 1);
                        } elseif (isset($configuration['type']) && $configuration['type'] == 'drop_down') {
                            $optionValueCollection = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Option\Value')->getCollection();
                            $optionValueCollection->addFieldToFilter('orig_value_id', ['in' => $this->_getOptionValueIds($_option->getOrigOptionId())]);
                            $hasVisibleItems = false;
                            if (count($optionValueCollection)) {
                                foreach ($optionValueCollection as $_optionValue) {
                                    $valueConfiguration = $_optionValue->getConfiguration();
                                    if (is_string($valueConfiguration)) {
                                        $valueConfiguration = \Zend_Json::decode($valueConfiguration);
                                    }
                                    if (isset($valueConfiguration['visibility_condition']) && $valueConfiguration['visibility_condition']) {
                                        $valueCondition = \Zend_Json::decode($valueConfiguration['visibility_condition']);
                                        if ($this->getConditionHelper()->isConditionCorrect($valueCondition, $valuesByInternalId)) {
                                            if ($valueConfiguration['visibility_action'] == 'visible') {
                                                $hasVisibleItems = true;
                                                break;
                                            }
                                        } elseif ($valueConfiguration['visibility'] == 'visible') {
                                            $hasVisibleItems = true;
                                            break;
                                        }
                                    } elseif (!isset($valueConfiguration['visibility']) || $valueConfiguration['visibility'] == 'visible' || !$valueConfiguration['visibility']) {
                                        $hasVisibleItems = true;
                                        break;
                                    }
                                }
                            }
                            if (!$hasVisibleItems) {
                                $product->setData('skip_required_option' . $_option->getOrigOptionId(), 1);
                            }
                        }
                    }
                }
            }
        }
        return $this;
    }

    protected function _getOptionValueIds($optionId) {
        /** @var \Magento\Framework\App\ResourceConnection $res */
        $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $con = $res->getConnection('read');
        $optionId = intval($optionId);
        $tableOptionTypeValue = $res->getTableName('catalog_product_option_type_value');
        return $con->fetchCol("select e.option_type_id from {$tableOptionTypeValue} as e where e.option_id = {$optionId}");
    }

    /**
     * @param $option \Magento\Catalog\Model\Product\Option
     * @param $value string
     * @return mixed
     */
    public function prepareOptionValue($option, $value) {
        $selectTypes = ['drop_down', 'multiple', 'radio', 'checkbox'];
        if (in_array($option->getType(), $selectTypes)) {
            $values = explode(',', $value);
            if (!empty($values)) {
                $valuesCollection = $option->getOptionValuesByOptionId($values, $option->getStoreId())->addTitlesToResult($option->getStoreId())->load();
                foreach ($values as $key => $_value) {
                    $valueObj = $valuesCollection->getItemById($_value);
                    $values[$key] = $valueObj ? $valueObj->getTitle() : null;
                }
                if ($option->getType() == 'multiple' || $option->getType() == 'checkbox') {
                    return $values;
                }
                return $values[0];
            }
        }

        return $value;
    }

    protected function getConditionHelper(){
        return $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Condition');
    }
}