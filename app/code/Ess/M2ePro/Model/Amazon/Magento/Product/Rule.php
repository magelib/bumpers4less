<?php

/*
 * @author     M2E Pro Developers Team
 * @copyright  2011-2015 ESS-UA [M2E Pro]
 * @license    Commercial use is forbidden
 */

namespace Ess\M2ePro\Model\Amazon\Magento\Product;

class Rule extends \Ess\M2ePro\Model\Magento\Product\Rule
{
    //########################################

    /**
     * @return string
     */
    public function getConditionClassName()
    {
        return 'Amazon\Magento\Product\Rule\Condition\Combine';
    }

    //########################################
}