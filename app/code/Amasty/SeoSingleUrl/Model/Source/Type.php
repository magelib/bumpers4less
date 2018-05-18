<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_SeoSingleUrl
 */


namespace Amasty\SeoSingleUrl\Model\Source;

class Type implements \Magento\Framework\Option\ArrayInterface
{
    const SHORTEST = 'shortest';
    const LONGEST = 'longest';
    const DEFAULT_RULES = 'default';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::SHORTEST,
                'label' => __('Shortest Path')
            ],
            [
                'value' => self::LONGEST,
                'label' => __('Longest Path')
            ],
            [
                'value' => self::DEFAULT_RULES,
                'label' => __('Default Rules')
            ],
        ];
    }
}
