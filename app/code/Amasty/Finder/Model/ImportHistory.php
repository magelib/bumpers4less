<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Model;


class ImportHistory extends \Amasty\Finder\Model\Import\ImportLogAbstract
{
    /**
     * @var \Amasty\Finder\Helper\Data
     */
    protected $helper;

    /**
     * ImportHistory constructor.
     *
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\ObjectManagerInterface                    $objectManager
     * @param \Amasty\Finder\Helper\Data                                   $helper
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Amasty\Finder\Helper\Data $helper,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct(
            $context, $registry, $objectManager, $resource, $resourceCollection,
            $data
        );
    }


    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Amasty\Finder\Model\ResourceModel\ImportHistory');
        parent::_construct();
    }

    public function getFileState()
    {
        return \Amasty\Finder\Helper\Data::FILE_STATE_ARCHIVE;
    }

    public function getFieldInErrorLog()
    {
        return 'import_file_log_history_id';
    }


    public function clearArchive()
    {
        $lifetime = $this->helper->getArchiveLifetime();
        $date = strftime('%Y-%m-%d %H:%M:%S', strtotime("-{$lifetime} days"));
        $list = $this->getCollection()->addFieldToFilter('ended_at', array("lteq" => $date));
        $list->walk('delete');
    }

    public function afterDelete()
    {
        $file = $this->helper->getImportArchiveDir().$this->getId().'.csv';

        if(is_file($file)) {
            unlink($file);
        }
        return parent::afterDelete();
    }
}
