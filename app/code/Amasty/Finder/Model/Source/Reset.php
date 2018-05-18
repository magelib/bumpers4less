<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Finder\Model\Source;


class Reset implements \Magento\Framework\Option\ArrayInterface
{
    const VALUE_HOME = 'home';
    const VALUE_CURRENT = 'current';
    const VALUE_DEFAULT = 'default';

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::VALUE_HOME,
                'label' => __('To Home Page')
            ],
            [
                'value' => self::VALUE_CURRENT,
                'label' => __('To The Same Page')
            ],
            [
                'value' => self::VALUE_DEFAULT,
                'label' => __('To The Result Page')
            ]
        ];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [
            self::VALUE_HOME => __('To Home Page'),
            self::VALUE_CURRENT => __('To The Same Page'),
            self::VALUE_DEFAULT => __('To The Result Page')
        ];
    }
}
