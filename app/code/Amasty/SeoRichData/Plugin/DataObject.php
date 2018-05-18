<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_SeoRichData
 */


namespace Amasty\SeoRichData\Plugin;

class DataObject
{
    public function aroundGetData(
        $subject,
        callable $proceed,
        $key = '',
        $index = null
    ) {
        if ($subject instanceof \Magento\Framework\Pricing\Render\Amount && $key == 'schema') {
            return false;
        }

        return $proceed($key, $index);
    }
}

