<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio GmbH. All rights reserved.
 * @author: Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Price\Rule;

class Condition extends \Magento\Rule\Model\Condition\AbstractCondition
{
    /**
     * @param $data
     *
     * @return bool
     */
    public function validatePriceRuleConditions($data)
    {
        $conditions = $data['conditions'];
        $rowData = $data['row'];
        $aggr = $data['aggregator'];
        $aggrValue = $data['value'];
        $categoryIds = $data['categories'];

        $applyRule = ($aggr == 'any') ? false : true;

        foreach ($conditions as $key => $condition) {
            if (strpos($key, '--') !== false) {
                $attribute = $condition['attribute'];
                $operator = $condition['operator'];
                $value = $condition['value'];
                $this->setData('value_parsed', $value);
                $this->setOperator($operator);

                switch ($attribute) {
                    case 'category_ids':
                        $validationResult = $this->validateAttribute($categoryIds);

                        break;
                    default:
                        $rowValue = $rowData[$attribute];
                        $validationResult = $this->validateAttribute($rowValue);

                        break;
                }

                if ($aggr == 'any') {
                    $applyRule = $aggrValue ? ($applyRule || $validationResult) : ($applyRule || !$validationResult);
                } else {
                    $applyRule = $aggrValue ? ($applyRule && $validationResult) : ($applyRule && !$validationResult);
                }
            }
        }

        return $applyRule;
    }
}