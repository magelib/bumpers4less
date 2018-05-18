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

class CloneTemplate extends \Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options\Template
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $template = $this->initCurrentTemplate();
        if ($template->getId()) {
            try {
                $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
                $con = $res->getConnection('read');
                $templateIds = $con->fetchCol("select `template_id` from {$res->getTableName('itoris_dynamicproductoptions_template')} where `parent_id`={$template->getId()}");
                
                $name = __('Copy of ').$template->getName();
                $template->setId(null)->setTemplateId(null)->setName($name)->save();
                $newId = $template->getId();
                foreach($templateIds as $templateId) {
                    $template->load($templateId)->setId(null)->setTemplateId(null)->setParentId($newId)->setName($name)->save();
                }
                
                $this->messageManager->addSuccess(__('Template has been cloned'));
            } catch (\Exception $e) {
                $this->messageManager->addError(__('An error occurred'));
            }
        } else $this->messageManager->addError(__('Template was not found'));
        $this->_redirect('*/*');
    }
}