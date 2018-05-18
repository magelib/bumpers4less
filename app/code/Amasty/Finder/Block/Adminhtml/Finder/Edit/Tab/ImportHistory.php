<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Block\Adminhtml\Finder\Edit\Tab;

use Magento\Backend\Block\Widget\Tab\TabInterface;

class ImportHistory extends \Magento\Backend\Block\Widget\Grid\Container implements TabInterface
{
    use \Amasty\Finder\MyTrait\FinderTab;

    public function __construct(\Magento\Backend\Block\Widget\Context $context,\Magento\Framework\Registry $registry, array $data)
    {
        $this->_model = $registry->registry('current_amasty_finder_finder');
        parent::__construct($context, $data);
        $this->_controller = 'finder';
        $this->_headerText = __('Import History');
        $this->_tabLabel = __('Import History');

        $this->removeButton('add');
    }

    public function getButtonsHtml($region = null)
    {
        return null;
    }
}
