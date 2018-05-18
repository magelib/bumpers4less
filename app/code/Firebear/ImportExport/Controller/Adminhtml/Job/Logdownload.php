<?php

/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Filesystem\Directory\ReadFactory;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;

/**
 * Action Download
 */
class Logdownload extends Job
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
     * Logdownload constructor.
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
        $this->fileFactory = $fileFactory;
        $this->directory = $filesystem->getDirectoryRead(DirectoryList::LOG);
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
    }

    /**
     * Execute action
     *
     * @return void
     */
    public function execute()
    {
        $file = $this->getRequest()->getParam('file');

        return $this->downloadFile($file);
    }

    /**
     * @param $file
     *
     * @return ResponseInterface
     * @throws \Exception
     */
    protected function downloadFile($file)
    {
         $file = $this->directory->getAbsolutePath(). 'firebear/' . $file .".log" ;

        return $this->fileFactory->create(basename($file), file_get_contents($file), DirectoryList::LOG);
    }
}
