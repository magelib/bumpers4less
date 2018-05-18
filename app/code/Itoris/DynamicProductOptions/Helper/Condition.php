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

class Condition extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $values = [];

    public function isConditionCorrect(array $condition, array $values) {
        $this->values = $values;
        return $this->_isConditionCorrect($condition);
    }

    public function getOptionValue($internalId) {
        return isset($this->values[$internalId]) ? $this->values[$internalId] : '';
    }

    protected function _isConditionCorrect($condition) {
        $isCorrect = true;
        if ($condition['type'] == 'field') {
            return $this->isCorrect($condition['value'], $condition['condition'], $this->getOptionValue($condition['field']));
        } else {
            for ($i = 0; $i < count($condition['conditions']); $i++) {
                if ($this->_isConditionCorrect($condition['conditions'][$i])) {
                    if ($condition['value']) {
                        if ($condition['type'] == 'any') {
                            return true;
                        }
                    } else {
                        if ($condition['type'] == 'all') {
                            return false;
                        } else {
                            $isCorrect = false;
                        }
                    }
                } else {
                    if ($condition['value']) {
                        if ($condition['type'] == 'all') {
                            return false;
                        } else {
                            $isCorrect = false;
                        }
                    } else {
                        if ($condition['type'] == 'any') {
                            return true;
                        }
                    }
                }
            }
        }
        return $isCorrect;
    }

    public function isCorrect($value, $condition, $optionValue) {
        if (is_array($optionValue)) {
            foreach ($optionValue as $_optionValue) {
                if ($condition == 'is_not') {
                    if ($this->isCorrect($value, 'is', $_optionValue)) {
                        return false;
                    }
                } else {
                    if ($this->isCorrect($value, $condition, $_optionValue)) {
                        return true;
                    }
                }
            }
            if ($condition == 'is_not') {
                return true;
            }
        } else {
            if (is_numeric($value)) $value = floatval($value);
            if (is_numeric($optionValue)) $optionValue = floatval($optionValue);
            switch ($condition) {
                case 'is':
                    return $value == $optionValue;
                case 'is_not':
                    return $value != $optionValue;
                case 'equal_greater':
                    return $optionValue >= $value;
                case 'equal_less':
                    return $optionValue <= $value;
                case 'greater':
                    return $optionValue > $value;
                case 'less':
                    return $optionValue < $value;
            }
        }
        return false;
    }

}