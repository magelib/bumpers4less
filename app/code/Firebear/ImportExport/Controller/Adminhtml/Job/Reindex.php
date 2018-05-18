<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Helper\Data;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Reindex extends JobController
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
     * Status constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param JsonFactory $jsonFactory
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        JsonFactory $jsonFactory,
        Data $helper
        ) {
                parent::__construct($context, $coreRegistry, $jobFactory, $repository);
                $this->jsonFactory = $jsonFactory;
                $this->helper = $helper;
            }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
                $resultJson = $this->jsonFactory->create();
                if ($this->getRequest()->isAjax()) {
                        $file = $this->getRequest()->getParam('file');
                        $job = $this->getRequest()->getParam('job');

                        $result = $this->helper->processReindex($file, $job);

                        return $resultJson->setData(
                            [
                                    'result' => $result
                                    ]
                        );
        }
    }
}
