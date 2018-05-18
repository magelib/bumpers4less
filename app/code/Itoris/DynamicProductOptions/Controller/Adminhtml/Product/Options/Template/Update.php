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

class Update extends \Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options\Template
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $result = ['error' => false];
        $error = null;
        $templateId = $this->getRequest()->getParam('template_id');
        $templateData = $this->getRequest()->getParam('template', []);
        $sections = json_decode($templateData['configuration'], true);
        try {
            $template = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Template')->load($templateId);
            foreach($sections as $key => $section) unset($sections[$key]['template_id']);
            $templateData['configuration'] = json_encode($sections);
            if ($template->getId()) {
                $template->addData($templateData)
                    ->save();
                $result['message'] = __('Template %1 has been updated', $template->getName());
            } else {
                $error = __('Template not found');
            }
        } catch (\Exception $e) {
            $error = __('Template has not been updated');
        }

        if ($error) {
            $result['error'] = $error;
        }

        $this->getResponse()->setBody(\Zend_Json::encode($result));
    }
}