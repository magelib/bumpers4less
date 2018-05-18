<?php

/**
 * Magedelight
 * Copyright (C) 2016 Magedelight <info@magedelight.com>.
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @category Magedelight
 *
 * @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 */

namespace Magedelight\Bundlediscount\Block;

class Toplink extends \Magento\Framework\View\Element\Html\Link
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    protected $_template = 'Magedelight_Bundlediscount::link.phtml';

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array                                            $data
     */
    public function __construct(
    \Magento\Framework\View\Element\Template\Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->scopeConfig = $context->getScopeConfig();
    }

    public function getHref()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $urlKey = trim($this->scopeConfig->getValue('bundlediscount/general/url_key', $storeScope), '/');
        $suffix = trim($this->scopeConfig->getValue('bundlediscount/general/url_suffix', $storeScope), '/');
        $urlKey .= (strlen($suffix) > 0 || $suffix != '') ? '.'.str_replace('.', '', $suffix) : '/';

        return $this->_storeManager->getStore()->getBaseUrl().$urlKey;
    }

    public function getLabel()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $label = $this->scopeConfig->getValue('bundlediscount/general/link_title', $storeScope);

        return __($label);
    }

    /**
     * {@inheritdoc}
     */
    protected function _toHtml()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $isEnable = $this->scopeConfig->getValue('bundlediscount/others/enable_frontend', $storeScope);
        $isTopLink = $this->scopeConfig->getValue('bundlediscount/general/enabled_bundle_on', $storeScope) == 'toplink' ? true : false;
        if ($isEnable && $isTopLink) {
            return parent::_toHtml();
        }

        return '';
    }
}
