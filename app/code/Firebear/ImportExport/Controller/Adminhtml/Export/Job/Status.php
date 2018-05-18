<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Controller\Adminhtml\Export\Job as JobController;
use Firebear\ImportExport\Helper\Data;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Firebear\ImportExport\Model\ExportJobFactory;
use Firebear\ImportExport\Api\ExportJobRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Status extends JobController
{
    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $url;

    /**
     * Status constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param ExportJobFactory $jobFactory
     * @param ExportJobRepositoryInterface $repository
     * @param JsonFactory $jsonFactory
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        ExportJobFactory $jobFactory,
        ExportJobRepositoryInterface $repository,
        JsonFactory $jsonFactory,
        Data $helper
    ) {
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
        $this->jsonFactory = $jsonFactory;
        $this->helper = $helper;
        $this->url = $context->getBackendUrl();
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        if ($this->getRequest()->isAjax()) {
            //read required fields from xml file
            $file = $this->getRequest()->getParam('file');
            $counter = $this->getRequest()->getParam('number', 0);
            $console = $this->helper->scopeRun($file, $counter);
          
            return $resultJson->setData(
                [
                    'console' => $console
                ]
            );
        }
    }
}
