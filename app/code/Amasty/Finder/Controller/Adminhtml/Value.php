<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Controller\Adminhtml;


abstract class Value extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Amasty\Finder\Model\Finder
     */
    protected $_model;


    /**
     * Initialize Group Controller
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry
    ) {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }


    /**
     * Initiate action
     *
     * @return this
     */
    protected function _initAction()
    {
        $this->_view->loadLayout();
        $this->_setActiveMenu('Amasty_Finder::finder')->_addBreadcrumb(__('Finder'), __('Finder'));
        return $this;
    }

    /**
     * Determine if authorized to perform group actions.
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_Finder::finder');
    }

    protected function _initModel()
    {
        $this->_model = $this->_objectManager->create('Amasty\Finder\Model\Finder');
        $id = $this->getRequest()->getParam('finder_id');
        $this->_model->load($id);
        if(!$this->_model->getId()) {
            $this->_redirect('amasty_finder/finder/');
            return;
        }
        $this->_coreRegistry->register('current_amasty_finder_finder', $this->_model);
    }
}
