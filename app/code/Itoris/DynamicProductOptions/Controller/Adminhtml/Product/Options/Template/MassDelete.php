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

class MassDelete extends \Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options\Template
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $ids = $this->_getMassActionIds();
        $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $con = $res->getConnection('write');
        try {
            foreach ($ids as $id) {
                $template = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Template')->load($id);
                if ($template->getId()) {
                    $con->query("delete from {$res->getTableName('itoris_dynamicproductoptions_template')} where `parent_id`={$template->getId()}");                
                    $template->delete();
                    $session = $this->_objectManager->get('Magento\Backend\Model\Session');
                    $_ids = (array)$session->getDpoTemplateUpdateProducts();
                    $_ids[] = $id;
                    $session->setDpoTemplateUpdateProducts(['templates' => $_ids]);
                }
            }
            $this->messageManager->addSuccess(__('Selected templates have been deleted'));
        } catch (\Exception $e) {
            $this->messageManager->addError(__('Some selected templates have not been deleted'));
        }

        $this->_redirect('*/*/');
    }
}