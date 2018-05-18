<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Meta
 */

namespace Amasty\Meta\Plugin\Theme\Block\Html;

class Title
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

    public function aroundGetPageHeading(
        $subject,
        \Closure $proceed
    ){
        $title = $proceed($subject);

        $replacedHeading = $this->data->getReplaceData('h1_tag');

        if ($replacedHeading) {
            return $replacedHeading;
        }
        return $title;
    }
}