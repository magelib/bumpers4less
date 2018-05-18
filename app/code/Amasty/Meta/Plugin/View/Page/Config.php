<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Meta
 */


namespace Amasty\Meta\Plugin\View\Page;

class Config
{
    /**
     * @var \Amasty\Meta\Helper\Data
     */
    private $data;

    public function __construct(
        \Amasty\Meta\Helper\Data $dataHelper
    ) {
        $this->data = $dataHelper;
    }

    public function afterGetKeywords(
        $pageConfig,
        $metaKeywords
    ) {
        $replacedMetaKeywords = $this->data->getReplaceData('meta_keywords');
        if ($replacedMetaKeywords) {
            return $replacedMetaKeywords;
        }

        return $metaKeywords;
    }

    public function afterGetDescription(
        $pageConfig,
        $metaDescription
    ) {
        $replacedMetaKeywords = $this->data->getReplaceData('meta_description');
        if ($replacedMetaKeywords) {
            return $replacedMetaKeywords;
        }

        return $metaDescription;
    }

    public function afterGetRobots(
        $pageConfig,
        $metaRobots
    ) {
        $replacedMetaKeywords = $this->data->getReplaceData('meta_robots');
        if ($replacedMetaKeywords) {
            return $replacedMetaKeywords;
        }

        return $metaRobots;
    }
}