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

namespace Magedelight\Bundlediscount\Controller\Adminhtml\Index;

use Magento\Framework\App\ResponseInterface;
use Magento\Config\Controller\Adminhtml\System\ConfigSectionChecker;
use Magento\Framework\App\Filesystem\DirectoryList;

class Export extends \Magento\Config\Controller\Adminhtml\System\AbstractConfig
{
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $_fileFactory;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;
    protected $_bundleFactory;

    /**
     * @param \Magento\Backend\App\Action\Context                            $context
     * @param \Magento\Config\Model\Config\Structure                         $configStructure
     * @param ConfigSectionChecker                                           $sectionChecker
     * @param \Magento\Framework\App\Response\Http\FileFactory               $fileFactory
     * @param \Magedelight\Bundlediscount\Model\ResourceModel\Bundlediscount $bundleFactory
     * @param \Magento\Store\Model\StoreManagerInterface                     $storeManager
     */
    public function __construct(
    \Magento\Backend\App\Action\Context $context, \Magento\Config\Model\Config\Structure $configStructure, ConfigSectionChecker $sectionChecker, \Magento\Framework\App\Response\Http\FileFactory $fileFactory, \Magedelight\Bundlediscount\Model\ResourceModel\Bundlediscount $bundleFactory, \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->_storeManager = $storeManager;
        $this->_fileFactory = $fileFactory;
        $this->_bundleFactory = $bundleFactory;
        parent::__construct($context, $configStructure, $sectionChecker);
    }

    /**
     * Export shipping table rates in csv format.
     *
     * @return ResponseInterface
     */
    public function execute()
    {
        $fileName = 'Bundle_Promotions_Export.csv';
        $content = '';

        $exportData = $this->_bundleFactory->getExportData();

        foreach ($exportData as $data) {
            $content .= implode(',', $data)."\n";
        }

        return $this->_fileFactory->create($fileName, $content, DirectoryList::VAR_DIR, 'text/csv');
    }
}
