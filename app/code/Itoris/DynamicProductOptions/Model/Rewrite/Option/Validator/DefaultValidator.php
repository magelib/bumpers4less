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
 
namespace Itoris\DynamicProductOptions\Model\Rewrite\Option\Validator;

class DefaultValidator extends \Magento\Catalog\Model\Product\Option\Validator\DefaultValidator
{
    /**
     * Returns true if and only if $value meets the validation requirements
     *
     * If $value fails validation, then this method returns false, and
     * getMessages() will return an array of messages that explain why the
     * validation failed.
     *
     * @param  \Magento\Catalog\Model\Product\Option $value
     * @return boolean
     * @throws \Zend_Validate_Exception If validation of $value is impossible
     */
    public function isValid($value)
    {
        $messages = [];
        /*
        if (!$this->validateOptionRequiredFields($value)) {
            $messages['option required fields'] = 'Missed values for option required fields';
        }
        */

        if (!$this->validateOptionType($value)) {
            $messages['option type'] = 'Invalid option type';
        }
        /*
        if (!$this->validateOptionValue($value)) {
            $messages['option values'] = 'Invalid option value';
        }
        */

        $this->_addMessages($messages);

        return empty($messages);
    }
}
