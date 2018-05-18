<?php

namespace Itoris\DynamicProductOptions\Block\Adminhtml\Product;

use Magento\Framework\App\ResourceConnection;

class GridMassAction extends \Magento\Catalog\Block\Product\View\Options
{
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Pricing\Helper\Data $pricingHelper
     * @param \Magento\Catalog\Helper\Data $catalogData
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Catalog\Model\Product\Option $option
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Stdlib\ArrayUtils $arrayUtils
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Pricing\Helper\Data $pricingHelper,
        \Magento\Catalog\Helper\Data $catalogData,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\Product\Option $option,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        array $data = []
    ) {
        $this->_objectManager = $objectManager;
        parent::__construct($context, $pricingHelper, $catalogData, $jsonEncoder, $option, $registry, $arrayUtils, $data);
    }
    
    public function getTemplatesList() {
        $_templates = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Template')
                            ->getCollection()
                            ->addFieldToFilter('store_id', array('eq' => 0));
        $templates = [];
        foreach($_templates as $template) $templates[(int) $template->getTemplateId()] = $template->getName();
        asort($templates);
        return (array) $templates;
    }
    
    public function isEnabled() {
        return $this->getDataHelper()->isAdminRegistered() && $this->getDataHelper()->getSettings(true)->getEnabled();
    }
    
    protected function getDataHelper() {
        return $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Data');
    }
}