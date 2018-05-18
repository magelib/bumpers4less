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

class Import extends \Magento\Backend\Block\Template implements TabInterface
{
    use \Amasty\Finder\MyTrait\FinderTab;

    public function __construct(\Magento\Backend\Block\Template\Context $context,\Magento\Framework\Registry $registry, array $data)
    {
        $this->_model = $registry->registry('current_amasty_finder_finder');
        parent::__construct($context, $data);
        $this->_tabLabel = __('Import');
    }

    public function getFinderId()
    {
        return $this->_model->getId();
    }
}
