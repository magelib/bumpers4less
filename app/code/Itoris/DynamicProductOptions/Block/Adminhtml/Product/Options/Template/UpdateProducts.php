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

namespace Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options\Template;

use Magento\Framework\App\ResourceConnection;

class UpdateProducts extends \Magento\Backend\Block\Widget\Container
{	
	protected $_template = 'Itoris_DynamicProductOptions::catalog/template/products_update.phtml';
    
    public function needsProductUpdate(){
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->session = $this->_objectManager->get('Magento\Backend\Model\Session');
        $this->updateProductData = $this->session->getDpoTemplateUpdateProducts();
        $this->session->unsDpoTemplateUpdateProducts();
        return $this->updateProductData['templates'][0];
    }
    
    public function getConfigsToUpdate(){
        $configIds = [];
        if (isset($this->updateProductData['configs'])) {
            $configIds = (array) $this->updateProductData['configs'];
        } else if ($this->updateProductData['templates'][0]) {
            $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
            $con = $res->getConnection('read');
            $templateId = $this->updateProductData['templates'][0];
            $configIds = $con->fetchCol("select `config_id` from {$res->getTableName('itoris_dynamicproductoptions_template_product')} where `template_id`={$templateId}");
        }
        return $configIds;
    }
}