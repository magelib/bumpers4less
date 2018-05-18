<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Meta
 */

namespace Amasty\Meta\Block\Adminhtml\System;

class Button extends \Magento\Config\Block\System\Config\Form\Field
{
    protected function _getElementHtml(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $buttonBlock = $this->getLayout()->createBlock('Magento\Backend\Block\Widget\Button');

        $params = [
            'store_key' => $this->_storeManager->getStore()->getCode()
        ];

        $initUrl = $this->getUrl('amasty_meta/meta/init', $params);
        $processUrl = $this->getUrl('amasty_meta/meta/process', $params);

        $updateParams = [
            'init_url' => $initUrl,
            'process_url' => $processUrl,
            'conclude_url' => $this->getUrl("amasty_meta/meta/done")
        ];

        $encodedParams = \Zend_Json::encode($updateParams);

        $data = array(
            'label'     => __('Apply Template For Product URLs'),
            'id'        => 'ammeta_apply_templates',
            'class'     => '',
        );

        $buttonBlock->setData($data);

        $applyBlock = $this->getLayout()->createBlock('Magento\Framework\View\Element\Template');

        $applyBlock
            ->setTemplate('Amasty_Meta::System/Button/apply.phtml')
            ->setButton($buttonBlock)
            ->setConfig($encodedParams)
        ;

        return $applyBlock->toHtml();
    }
}