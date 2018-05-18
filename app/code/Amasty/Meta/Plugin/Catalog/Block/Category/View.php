<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Meta
 */

namespace Amasty\Meta\Plugin\Catalog\Block\Category;

class View
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

    public function afterGetProductListHtml(
        $subject,
        $html
    ) {
        $replacedHeading = $this->data->getReplaceData('after_product_text');
        if ($replacedHeading) {
            $html =  $html . $replacedHeading;
        }

        return $html;
    }
}