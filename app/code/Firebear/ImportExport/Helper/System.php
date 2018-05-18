<?php
/**
* @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
* @author   : Firebear Studio <fbeardev@gmail.com>
*/

namespace Firebear\ImportExport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

/**
 * Class System
 * @package Firebear\GoogleClient\Helper
 */
class System extends AbstractHelper
{
    public function getGoogleApi()
    {
        return $this->scopeConfig->getValue(
            'firebear_importexport/firebear_google/google_api',
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getUrlDriveApi()
    {
        return $this->scopeConfig->getValue(
            'firebear_importexport/firebear_google/drive_api',
            ScopeInterface::SCOPE_STORE
        );
    }
}