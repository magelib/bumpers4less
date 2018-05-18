<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Meta
 */

namespace Amasty\Meta\Plugin\Catalog\Block\Product;

class Image
{

    /**
     * @var \Amasty\Meta\Helper\Data
     */
    private $data;

    public function __construct(
        \Amasty\Meta\Helper\Data $data
    ) {
        $this->data = $data;
    }


    public function aroundGetData(
        $subject,
        $proceed,
        $key,
        $index
    ) {
        $data = $proceed($key, $index);

        if ($key == 'label') {
            $replacedHeading = $this->data->getReplaceData('image_alt');
            if ($replacedHeading) {
                return $replacedHeading;
            }
        }
        return $data;
    }
}