<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Controller\Adminhtml\Finder;


class RunFile extends \Amasty\Finder\Controller\Adminhtml\Finder
{
    protected $jsonEncoder;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder
    ) {
        parent::__construct(
            $context, $coreRegistry, $resultForwardFactory, $resultPageFactory
        );
        $this->jsonEncoder = $jsonEncoder;
    }

    protected function _sendResponse($response)
    {
        return $this->getResponse()->setBody(
            $this->jsonEncoder->encode($response)
        );
    }

    public function execute()
    {
        $fileId = (int) $this->getRequest()->getParam('file_id');
        $fileLog = $this->_objectManager->create('Amasty\Finder\Model\ImportLog')->load($fileId);

        if(!$fileLog->getId()) {
            $response = array(
                    'isCompleted'   => true,
                    'message'       => __('File not exists'),
                    'progress'      => $fileLog->getProgress(),
                );
            return $this->_sendResponse($response);
        }

        if($fileLog->getIsLocked()) {
            return $this->_sendResponse(array(
                'isCompleted'=>true,
                'message'=> __('File already running'),
                'progress'=> $fileLog->getProgress(),
            ));
        }
        $countProcessedRows = 0;
        $this->_objectManager->create('Amasty\Finder\Model\Import')->runFile($fileLog, $countProcessedRows);

        $data = array();
        $data['isCompleted'] = (bool)$fileLog->getEndedAt();
        if($data['isCompleted']) {
            if($countProcessedRows) {
                $data['message'] = __('File imported successfully');
                $data['message'] .= __(' with %1 errors', $fileLog->getCountErrors());
            } else {
                $data['message'] =
                    __('The file is invalid, please see <a class="show-import-errors" data-url="%1">errors log</a> for details.',
                        $this->getUrl('*/finder/errors', array('file_id'=>$fileLog->getFileLogHistoryId(), 'file_state'=>\Amasty\Finder\Helper\Data::FILE_STATE_ARCHIVE)));
            }


        } else {
            $data['message'] = __('Imported %1 rows of total %2 rows (%3%)', $fileLog->getCountProcessingLines(), $fileLog->getCountLines(), $fileLog->getProgress());
        }

        $data['progress'] = $fileLog->getProgress();

        return $this->_sendResponse($data);
    }
}
