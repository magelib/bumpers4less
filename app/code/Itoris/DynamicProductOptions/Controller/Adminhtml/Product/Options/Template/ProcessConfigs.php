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

namespace Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options\Template;

class ProcessConfigs extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {
        $configs = $this->getRequest()->getParam('configs');
        $templateId = (int)$this->getRequest()->getParam('template_id');
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $con = $res->getConnection('write');
        
        header('Content-Type: application/json');        
        if ($templateId && count($configs)) {
            $productIds = $con->fetchCol("select `product_id` from {$res->getTableName('itoris_dynamicproductoptions_options')} where `config_id` in (".implode(',', $configs).")");
            $this->getRequest()->setParam('product_ids', $productIds);
            $this->getRequest()->setParam('method', 3);
            $controller = $this->_objectManager->get('Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options\MassLoad');
            $error = $controller->execute();
            
            //invalidate FPC
            $cacheTypeList = $this->_objectManager->create('\Magento\Framework\App\Cache\TypeList');
            $cacheTypeList->invalidate('full_page');
            
            if ($error) {
                $this->getResponse()->setBody(json_encode([
                    'error' => $error
                ]));
            } else {
                $this->getResponse()->setBody(json_encode([
                    'success' => 1
                ]));
            }
            
            return;
        }
        
        $this->getResponse()->setBody(json_encode([
            'error' => __('Error updating products')
        ]));
    }
}