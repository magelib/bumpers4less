<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

use Firebear\ImportExport\Controller\Adminhtml\Job as JobController;
use Firebear\ImportExport\Helper\Data;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;

class Run extends JobController
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
     * @var \Magento\Framework\Json\DecoderInterface
     */
    protected $jsonDecoder;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $url;

    /**
     * Run constructor.
     * @param Context $context
     * @param Registry $coreRegistry
     * @param JobFactory $jobFactory
     * @param JobRepositoryInterface $repository
     * @param JsonFactory $jsonFactory
     * @param \Magento\Framework\Json\DecoderInterface $jsonDecoder
     * @param Data $helper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository,
        JsonFactory $jsonFactory,
        \Magento\Framework\Json\DecoderInterface $jsonDecoder,
        Data $helper
    ) {
        parent::__construct($context, $coreRegistry, $jobFactory, $repository);
        $this->jsonFactory = $jsonFactory;
        $this->helper = $helper;
        $this->jsonDecoder = $jsonDecoder;
        $this->url = $context->getBackendUrl();
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $result = true;
        if ($this->getRequest()->isAjax()) {
            try {
                $pattern = '/({.+})/i';
                preg_match($pattern, $this->getRequest()->getContent(), $json);
                if (isset($json[0])) {
                    session_write_close();
                    ignore_user_abort(true);
                    set_time_limit(0);
                    ob_implicit_flush();
                    $data = $this->jsonDecoder->decode($json[0]);
                    $id = $data['id'];
                    $file = $data['file'];
                    $exportFile = $this->helper->runExport($id, $file);
                    $result = $this->helper->getResultProcessor();
                }
            } catch (\Exception $e) {
                $result = false;
            }

            return $resultJson->setData([
                'result' => $result,
                'file' => $this->url->getUrl(
                    'import/export_job/download',
                    ['file' => \Zend\Serializer\Serializer::serialize($exportFile)]
                )
            ]);
        }
    }
}
