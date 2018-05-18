<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_SeoToolKit
 */


namespace Amasty\SeoToolKit\Helper;

use Magento\Store\Model\ScopeInterface;

class Config extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MODULE_NAME = 'amseotoolkit/';

    public function getModuleConfig($path)
    {
        return $this->scopeConfig->getValue(
            self::MODULE_NAME . $path,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function isPrevNextLinkEnabled()
    {
        return $this->getModuleConfig('general/prev_next');
    }

    public function isAddPageToMetaTitleEnabled()
    {
        return $this->getModuleConfig('general/meta_title');
    }

    public function isAddPageToMetaDescEnabled()
    {
        return $this->getModuleConfig('general/meta_description');
    }
}
