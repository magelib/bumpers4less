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

//app/code/Itoris/DynamicProductOptions/Block/Adminhtml/Product/Options/Template/Edit.php
namespace Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options\Template;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{
    public function _construct() {
        parent::_construct();
        $this->_objectId = 'template_id';
        $this->_blockGroup = 'Itoris_DynamicProductOptions';
        $this->_controller = 'adminhtml_product_options_template';
        $this->_headerText = $this->escapeHtml(__('Edit Template'));

        $this->buttonList->remove('reset');
        $this->buttonList->update('save', 'class', 'save');
        $this->addButton('saveandcontinue', [
            'label'     => $this->escapeHtml(__('Save and Continue Edit')),
            'onclick'   => 'jQuery(\'#edit_form\').attr({\'action\': \''.$this->_getSaveAndContinueUrl().'\'}); jQuery(\'#edit_form\').form().submit()',
            'class'     => 'save primary',
        ], -1);
        if ($this->getRequest()->getParam('id')) {
            $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
            $con = $res->getConnection('read');
            $hasProducts = (int) $con->fetchOne("select count(`config_id`) from {$res->getTableName('itoris_dynamicproductoptions_template_product')} where `template_id`={$this->getRequest()->getParam('id')}");
            if ($hasProducts) {
                $this->addButton('saveandapply', [
                    'label'     => $this->escapeHtml(__('Apply to Products')),
                    'onclick'   => 'jQuery(\'#edit_form\').attr({\'action\': \''.$this->_getSaveAndApplyUrl().'\'}); jQuery(\'#edit_form\').form().submit()',
                    'class'     => 'save primary',
                ], -1);
            }
        } else {
            $this->getLayout()->unsetElement('dpo.store.switcher');
        }
    }

    protected function _getSaveAndContinueUrl() {
        return $this->getUrl('*/*/save', [
            '_current'  => true,
            'back'      => 'edit',
        ]);
    }
    
    protected function _getSaveAndApplyUrl() {
        return $this->getUrl('*/*/save', [
            '_current'  => true,
            'back'      => 'edit',
            'apply'     => true
        ]);
    }
}