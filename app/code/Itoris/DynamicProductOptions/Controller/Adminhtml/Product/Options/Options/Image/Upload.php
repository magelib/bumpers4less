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

namespace Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options\Options\Image;

class Upload extends \Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $str = '';
        try {
            $storeId = $this->getRequest()->getParam('store');
            $result = $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Image')->uploadFile('image');
            /** @var \Magento\Store\Model\StoreManagerInterface $storeManager */
            $storeManager = $this->_objectManager->get('Magento\Store\Model\StoreManagerInterface');
            $storeMediaUrl = $storeManager
                                    ->getStore($storeId)
                                    ->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA, true);

            $str .= '<div id="image_src">' . str_replace(['http://', 'https://'], '//', $storeMediaUrl) .'itoris/files' . $result['file'] . '</div>';
        } catch (\Exception $e) {
            $str .= '<div id="error">' . __('Image has not been uploaded') . '</div>';
            $str .= '<div id="error-debug">' . $e->getMessage() . '</div>';
        }
        
        $this->getResponse()->setBody($str);

    }
}