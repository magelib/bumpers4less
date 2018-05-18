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

class Create extends \Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options\Template
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $result = ['error' => false];
        $error = null;
        $templateData = $this->getRequest()->getParam('template', []);
        $sections = json_decode($templateData['configuration'], true);
        if (isset($templateData['name']) && strlen($templateData['name'])) {
            try {
                $template = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Template')->load($templateData['name'], 'name');
                foreach($sections as $key => $section) unset($sections[$key]['template_id']);
                $templateData['configuration'] = json_encode($sections);
                if ($template->getId()) {
                    $error = __('A template with such a name already exists');
                } else {
                    $template->setData($templateData)
                        ->setTemplateId(null)
                        ->save();
                    $result['message'] = __('New template has been created based on current settings');
                    $result['template_id'] = $template->getId();
                }
            } catch (\Exception $e) {
                $error = __('Template has not been created');
            }
        } else {
            $error = __('Please enter template name');
        }

        if ($error) {
            $result['error'] = $error;
        }

        $this->getResponse()->setBody(\Zend_Json::encode($result));
    }
}