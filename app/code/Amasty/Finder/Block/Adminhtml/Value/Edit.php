<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Block\Adminhtml\Value;

class Edit extends \Magento\Backend\Block\Widget\Form\Container
{

    /**
     * Initialize form
     * Add standard buttons
     * Add "Save and Continue" button
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_objectId = 'id';
        $this->_controller = 'adminhtml_value';
        $this->_blockGroup = 'Amasty_Finder';

        parent::_construct();

        $chainId = $this->getRequest()->getParam('id');

    }

    /**
     * Getter for form header text
     *
     * @return \Magento\Framework\Phrase
     */
    public function getHeaderText()
    {
        return __("Edit product");
    }

    public function getBackUrl()
    {
        return $this->getUrl('amasty_finder/finder/edit', array('id'=>$this->getRequest()->getParam('finder_id')));
    }

    public function getDeleteUrl()
    {
        return $this->getUrl('*/*/delete', ['id'=>$this->getRequest()->getParam('id'), 'finder_id'=>$this->getRequest()->getParam('finder_id')]);
    }
}
