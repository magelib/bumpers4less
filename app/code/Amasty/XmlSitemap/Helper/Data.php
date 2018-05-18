<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */

namespace Amasty\XmlSitemap\Helper;

use Magento\Framework\App\Helper\AbstractHelper;

class Data extends AbstractHelper
{
    public function getFrequency()
    {
        return array(
            'always' => __('always'),
            'hourly' => __('hourly'),
            'daily' => __('daily'),
            'weekly' => __('weekly'),
            'monthly' => __('monthly'),
            'yearly' => __('yearly'),
            'never' => __('never'),
        );
    }

    public function getDateFormats()
    {
        return array(
            'Y-m-d H:i:s' => __('With time'),
            'Y-m-d' => __('Without time'),
        );
    }
}
