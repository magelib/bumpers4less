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

//app/code/Itoris/DynamicProductOptions/Controller/Adminhtml/Product/Options.php
namespace Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options;

abstract class Template extends \Magento\Backend\App\Action
{
    protected $_coreRegistry;

    public function __construct(
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\App\Action\Context $context
    )
    {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    protected function _getMassActionIds() {
        $ids = $this->getRequest()->getParam('template', []);
        if (!is_array($ids)) {
            $ids = [$ids];
        }
        return $ids;
    }

    /**
     * Prepare current template
     *
     * @return \Magento\Framework\Model\AbstractModel
     */
    protected function initCurrentTemplate() {
        $storeId = (int)$this->getRequest()->getParam('store');
        $id = (int) $this->getRequest()->getParam('id');
        if ($storeId) {
            $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
            $con = $res->getConnection('read');
            $_id = (int) $con->fetchOne("select `template_id` from {$res->getTableName('itoris_dynamicproductoptions_template')} where `store_id`={$storeId} and `parent_id`={$id}");
            if ($_id) $id = $_id;
        }
        $template = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Template')->load($id);
        $this->_coreRegistry->register('current_template', $template);
        return $template;
    }


    protected function _isAllowed() {
        return $this->_authorization->isAllowed('Itoris_DynamicProductOptions::dynamicproductoptions_templates');
    }
}