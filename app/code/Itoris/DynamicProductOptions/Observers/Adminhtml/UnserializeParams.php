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

namespace Itoris\DynamicProductOptions\Observers\Adminhtml;

use Magento\Framework\Event\ObserverInterface;

class UnserializeParams implements ObserverInterface
{
    protected $isEnabledFlag = false;
    /**
     * @var \Magento\Framework\ObjectManagerInterface|null
     */
    protected $_objectManager = null;
    /**
     * @var \Magento\Framework\App\RequestInterface|null
     */
    protected $_request = null;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->_objectManager = $objectManager;
        $this->_request = $request;
        $params = $this->_request->getParams();
        //extract compressed post
        if (isset($params['itoris_dynamicproductoptions']['postcompressed']) && $params['itoris_dynamicproductoptions']['postcompressed'] != "{}") {
            $optionPost = json_decode($params['itoris_dynamicproductoptions']['postcompressed'], true);
            foreach($optionPost as &$level1) {
                if (is_array($level1)) foreach($level1 as &$level2) {
                    if (is_array($level2)) foreach($level2 as &$level3) {
                        if (is_array($level3)) {
                            if (isset($level3['values'])) {
                                foreach($level3['values'] as &$value) {
                                    if (isset($value['is_delete']) && (int)$value['is_delete'] == 1) {
                                        //fix of Magento2 bug with options deletion
                                        $value['is_delete'] = 0;
                                        $value['itoris_is_delete'] = 1;
                                    }
                                    unset($value['option_type_id']);
                                }
                            }
                        } else if(is_numeric($level3)) $level3 = floatval($level3);
                    }
                }
            }
            unset($params['itoris_dynamicproductoptions']['postcompressed']);
            $params['product']['options'] = $optionPost['product']['options'];
            $request->setPostValue('product', array_merge($request->getPostValue('product'), ['options' => $optionPost['product']['options']]));
            $this->_request->setParams($params);
        }
        try {
            $this->isEnabledFlag = $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Data')->getSettings(true)->getEnabled();
        } catch (\Exception $e) {/** save store model */}
    }

    public function execute(\Magento\Framework\Event\Observer $observer) {

        $paramsStr = $this->_request->getParam('itoris_dynamicproductoptions_serialized');
        if ($paramsStr) {
            $params = [];
            parse_str($paramsStr, $params);

            if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() && isset($params['itoris_dynamicproductoptions']['configuration'])) {
                $params['itoris_dynamicproductoptions']['configuration'] = stripslashes($params['itoris_dynamicproductoptions']['configuration']);
                if (isset($params['product']['options'])) {
                    foreach ($params['product']['options'] as &$option) {
                        if (isset($option['static_text'])) {
                            $option['static_text'] = stripslashes($option['static_text']);
                        }
                    }
                }
            }

            $this->_addParams($params, $this->_request->getPost());
        }
    }

    protected function _addParams($params, &$toArray) {
        foreach ($params as $key => $value) {
            if (array_key_exists($key, $toArray)) {
                if (is_array($toArray[$key]) && is_array($value)) {
                    $this->_addParams($value, $toArray[$key]);
                }
            } else {
                $toArray[$key] = $value;
            }
        }
    }
}