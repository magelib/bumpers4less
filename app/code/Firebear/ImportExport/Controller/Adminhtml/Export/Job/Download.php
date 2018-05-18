<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Helper\Additional;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

/**
 * Class Download
 * @package Firebear\ImportExport\Controller\Adminhtml\Export\Job
 */
class Download extends JobController
{
    /**
     * @var FileFactory
     */
    private $fileFactory;

    /**
     * @var Filesystem\Directory\ReadInterface
     */
    protected $directory;

    /**
     * Download constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param Filesystem $filesystem
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        Filesystem $filesystem,
        FileFactory $fileFactory
    ) {
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
        $this->fileFactory = $fileFactory;
        $this->directory = $filesystem->getDirectoryRead(DirectoryList::ROOT);

    }

    /**
     * Execute action
     *
     * @return void
     */
    public function execute()
    {
        $file = $this->getRequest()->getParam('file');

        return $this->downloadFile(\Zend\Serializer\Serializer::unserialize($file));
    }

    /**
     * @param $file
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    protected function downloadFile($file)
    {
        $file = $this->directory->getAbsolutePath() . $file;

        return $this->fileFactory->create(basename($file), file_get_contents($file), DirectoryList::LOG);
    }
}
