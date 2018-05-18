<?php
/**
 * ITORIS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the ITORIS's Magento Extensions License Agreement
 * which is available through the world-wide-web at this URL:
 * http://www.itoris.com/magento-extensions-license.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to sales@itoris.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extensions to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to the license agreement or contact sales@itoris.com for more information.
 *
 * @category   ITORIS
 * @package    ITORIS_M2_DYNAMIC_PRODUCT_OPTIONS
 * @copyright  Copyright (c) 2016 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

namespace Itoris\DynamicProductOptions\Model\Rewrite\Option\Type\File;

class Validator extends \Magento\Catalog\Model\Product\Option\Type\File\Validator
{
    protected function getValidatorErrors($errors, $fileInfo, $option) {
        $result = parent::getValidatorErrors($errors, $fileInfo, $option);
        if (count($result) != count($errors)) {
            foreach ($errors as $errorCode) {
                if ($errorCode == \Zend_Validate_File_ImageSize::NOT_DETECTED) {
                    $result[] = __("The size of image '%s' could not be detected", $fileInfo['title']);
                }
            }
        }

        return $result;
    }
}