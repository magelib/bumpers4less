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

class DeleteAjax extends \Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options\Template
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $result = ['error' => false];
        $error = null;
        try {
            $template = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Template')->load($this->getRequest()->getParam('template_id'));
            if ($template->getId()) {
                $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
                $con = $res->getConnection('write');
                $con->query("delete from {$res->getTableName('itoris_dynamicproductoptions_template')} where `parent_id`={$template->getId()}");
                
                $message = __(sprintf('Template %s has been deleted', $template->getName()));
                $template->delete();
                $result['message'] = $message;
            } else {
                $error = __('Template not found');
            }
        } catch (\Exception $e) {
            $error = __('Template has not been deleted');
        }

        if ($error) {
            $result['error'] = $error;
        }

        $this->getResponse()->setBody(\Zend_Json::encode($result));
    }
}