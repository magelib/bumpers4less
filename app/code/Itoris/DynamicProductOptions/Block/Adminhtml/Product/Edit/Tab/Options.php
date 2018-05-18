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

//app/code/Itoris/DynamicProductOptions/Block/Adminhtml/Product/Edit/Tab/Options.php
namespace Itoris\DynamicProductOptions\Block\Adminhtml\Product\Edit\Tab;

class Options extends \Magento\Catalog\Block\Adminhtml\Product\Edit\Tab\Options
{
    protected $isEnabledDynamicOptions = false;
    protected $existingTemplates = null;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    public $_objectManager = null;
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;


    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Backend\Block\Template\Context $context,
        array $data = []
    ){
        $this->_objectManager = $objectManager;
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }
    public function _construct() {
        parent::_construct();
        $this->isEnabledDynamicOptions = $this->getDataHelper()->isAdminRegistered() && $this->getDataHelper()->getSettings(true)->getEnabled();
        if ($this->isEnabledDynamicOptions) {
            $this->setTemplate('product/edit/options.phtml');
        } else {
            $this->setTemplate('Magento_Catalog::catalog/product/edit/options.phtml');
        }
    }

    /*protected function _prepareLayout() {
        if ($this->isEnabledDynamicOptions) {
            $dynamicOptions = $this->getLayout()->createBlock('Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options');
            $this->setChild('dynamic_options', $dynamicOptions);
        }
        return parent::_prepareLayout();
    }*/
    
    public function getOptionsHtml() {
        $block = $this->_objectManager->create('Itoris\DynamicProductOptions\Block\Adminhtml\Product\Options');
        return $block->toHtml();
    }

    public function getTemplatesConfigJson() {
        $config = [
            'urls' => [
                'create' => $this->getUrl('dynamicproductoptions/product_options_template/create'),
                'delete' => $this->getUrl('dynamicproductoptions/product_options_template/deleteAjax'),
                'update' => $this->getUrl('dynamicproductoptions/product_options_template/update'),
                'load'   => $this->getUrl('dynamicproductoptions/product_options_template/load'),
            ],
            'templates'   => []
        ];
        $templates = $this->getExistingTemplates();
        foreach ($templates as $template) {
            $config['templates'][] = $template->getName();
        }
        return \Zend_Json::encode($config);
    }

    public function getStoreId() {
        return (int)$this->_request->getParam('store');
    }

    public function getProductId() {
        return $this->_coreRegistry->registry('current_product')->getId();
    }

    public function getExistingTemplates() {
        if (is_null($this->existingTemplates)) {
            $templates = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Template')->getCollection()
                ->addFieldToSelect('template_id')
                ->addFieldToSelect('name')
                ->addFieldToFilter('store_id', array('eq' => 0));
            if (count($templates)) {
                $this->existingTemplates = $templates;
            } else {
                $this->existingTemplates = [];
            }
        }
        return $this->existingTemplates;
    }

    public function escapeJsHtml($text) {
        return addslashes($this->escapeHtml($text));
    }

    public function isBundleWithDynamicPrice() {
        return $this->_coreRegistry->registry('current_product')->getTypeId() == 'bundle' && !$this->_coreRegistry->registry('current_product')->getPriceType();
    }

    /**
     * @return \Itoris\DynamicProductOptions\Helper\Data
     */
    protected function getDataHelper() {
        return $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Data');
    }
}