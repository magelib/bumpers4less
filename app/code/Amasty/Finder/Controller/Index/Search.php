<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Controller\Index;


use Magento\Framework\App\Action\Context;

class Search extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\Url\Decoder
     */
    protected $urlDecoder;

    /**
     * @var \Amasty\Finder\Helper\Url
     */
    protected $urlHelper;

    public function __construct(
        Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Url\Decoder $urlDecoder,
        \Amasty\Finder\Helper\Url $urlHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->urlDecoder = $urlDecoder;
        $this->urlHelper = $urlHelper;
        parent::__construct($context);
    }


    public function execute()
    {
        $finderId = $this->getRequest()->getParam('finder_id');
        /** @var \Amasty\Finder\Model\Finder $finder */
        $finder = $this->_objectManager->create('Amasty\Finder\Model\Finder')->load($finderId);
        $backUrl = $this->urlDecoder->decode($this->getRequest()->getParam('back_url'));
        $currentApplyUrl = $this->urlDecoder->decode($this->getRequest()->getParam('current_apply_url'));


        $baseBackUrl = explode('?', $backUrl);
        $baseBackUrl = array_shift($baseBackUrl);

        $dropdowns = $this->getRequest()->getParam('finder');
        if ($dropdowns){
            $finder->saveFilter($dropdowns, $this->getRequest()->getParam('category_id'), [$currentApplyUrl, $baseBackUrl]);
        }

        $backUrl = $this->urlHelper->getUrlWithFinderParam($backUrl, $finder->getUrlParam());


        if ($this->scopeConfig->getValue('amfinder/general/clear_other_conditions', \Magento\Store\Model\ScopeInterface::SCOPE_STORE)){

            $finders = $finder->getCollection()->addFieldToFilter('finder_id', array('neq' => $finder->getId()));
            foreach ($finders as $f) {
                $f->resetFilter();
            }
        }

        if ($this->getRequest()->getParam('reset')) {
            $finder->resetFilter();

            if ($this->scopeConfig->getValue('amfinder/general/reset_home', \Magento\Store\Model\ScopeInterface::SCOPE_STORE) == 'home') {
                $backUrl ='/';
            } else {
                $resetUrl = $this->urlDecoder->decode($this->getRequest()->getParam('reset_url'));
                $backUrl = $finder->removeGet($resetUrl, 'find');
            }
        }

        $this->getResponse()->setRedirect($backUrl);
    }
}
