<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Controller\Adminhtml\Config;

use Magento\Backend\App\Action;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\ObjectManagerInterface;

class Check extends Action
{
    protected $_instanceName = null;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param ObjectManagerInterface              $objectManager
     * @param string                              $instanceName
     */
    public function __construct(
        Action\Context $context,
        $instanceName = 'Amasty\Smtp\Model\Transport'
    ) {
        parent::__construct($context);

        $this->_instanceName = $instanceName;
    }

    public function execute()
    {
        try {
            $data = [];

            $data['host'] = $this->getRequest()->getParam('server');

            $data['parameters'] = [
                'port' => +$this->getRequest()->getParam('port'),
                'auth' => $this->getRequest()->getParam('auth'),
                'ssl'  => $this->getRequest()->getParam('security'),

                'username' => trim($this->getRequest()->getParam('login')),
                'password' => trim($this->getRequest()->getParam('passw')),
            ];

            if (!$data['parameters']['ssl']) {
                unset($data['parameters']['ssl']);
            }

            $transport = $this->_objectManager->create($this->_instanceName, $data);

            $transport->runTest($this->getRequest()->getParam('test_email'));

            $responseContent = ['success' => true, 'message' => __('Connection Successful!')];
        } catch (LocalizedException $e) {
            $responseContent = ['success' => false, 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            $responseContent = [
                'success' => false,
                'message' => __('Connection Failed!'),
                'details' => [
                    'error_message' => $e->getMessage(),
                    'trace' => nl2br($e->getTraceAsString())
                ]
            ];
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData($responseContent);
        return $resultJson;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_Smtp::config');
    }
}
