<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2016 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Block\Adminhtml\Finder\Edit\Tab;


class AbstractGrid extends \Magento\Backend\Block\Widget\Grid
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Grid constructor.
     *
     * @param \Magento\Backend\Block\Template\Context   $context
     * @param \Magento\Backend\Helper\Data              $backendHelper
     * @param \Magento\Framework\Registry               $registry
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array                                     $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->objectManager = $objectManager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @return \Amasty\Finder\Model\Finder
     */
    protected function getFinder()
    {
        $finder = $this->coreRegistry->registry('current_amasty_finder_finder');
        return $finder;
    }
}
