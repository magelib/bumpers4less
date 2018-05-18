<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */

namespace Amasty\Smtp\Model;

use Amasty\Smtp\Model\Log;
use Amasty\Smtp\Model\Logger\DebugLogger;
use Amasty\Smtp\Model\Logger\MessageLogger;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\Mail\TransportInterface;
use Magento\Store\Model\ScopeInterface;

class Transport extends \Zend_Mail_Transport_Smtp implements TransportInterface
{
    /**
     * @var MessageInterface
     */
    protected $_message;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Amasty\Smtp\Model\Logger\MessageLogger
     */
    protected $messageLogger;

    /**
     * @var \Amasty\Smtp\Model\Logger\DebugLogger
     */
    protected $debugLogger;

    /**
     * @var \Amasty\Smtp\Helper\Data
     */
    protected $helper;

    /**
     * @param MessageInterface         $message
     * @param ScopeConfigInterface     $scopeConfig
     *
     * @param Logger\MessageLogger     $messageLogger
     * @param DebugLogger              $debugLogger
     * @param \Amasty\Smtp\Helper\Data $helper
     * @param  string                  $host OPTIONAL (Default: 127.0.0.1)
     * @param array                    $parameters
     *
     */
    public function __construct(
        MessageInterface $message,
        ScopeConfigInterface $scopeConfig,
        MessageLogger $messageLogger,
        DebugLogger $debugLogger,
        \Amasty\Smtp\Helper\Data $helper,
        $host = '127.0.0.1',
        array $parameters = array()
    ) {
        if (!$message instanceof \Zend_Mail) {
            throw new \InvalidArgumentException('The message should be an instance of \Zend_Mail');
        }
        parent::__construct($host, $parameters);
        $this->_message = $message;
        $this->scopeConfig = $scopeConfig;
        $this->messageLogger = $messageLogger;
        $this->debugLogger = $debugLogger;
        $this->helper = $helper;
    }

    /**
     * Send a mail using this transport
     *
     * @return void
     * @throws \Magento\Framework\Exception\MailException
     */
    public function sendMessage()
    {
        $this->debugLogger->log(__('Ready to send e-mail at amsmtp/transport::sendMessage()'));
        try {
            $logId = $this->messageLogger->log($this->_message);
            $storeId = $this->helper->getCurrentStore();
            if (!$this->scopeConfig->isSetFlag(
                'amsmtp/general/disable_delivery', ScopeInterface::SCOPE_STORE, $storeId
            )) {
                parent::send($this->_message);
                $this->debugLogger->log(__('E-mail sent successfully at amsmtp/transport::sendMessage().'));
            } else {
                $this->debugLogger->log(__('Actual delivery disabled under settings.'));
            }
            $this->messageLogger->updateStatus($logId, Log::STATUS_SENT);
        } catch (\Exception $e) {
            $this->debugLogger->log(__('Error sending e-mail: %1', $e->getMessage()));
            $this->messageLogger->updateStatus($logId, Log::STATUS_FAILED);
            throw new \Magento\Framework\Exception\MailException(new \Magento\Framework\Phrase($e->getMessage()), $e);
        }
    }

    public function runTest($testEmail)
    {
        if (fsockopen($this->_host, $this->_port, $errno, $errstr, 20))
        {
            $this->debugLogger->log(__('Connection test successful: connected to %1', $this->_host . ':' . $this->_port));

            if ($testEmail)
            {
                $from = $this->scopeConfig->getValue('trans_email/ident_general');
                $this->debugLogger->log(__('Preparing to send test e-mail to %1 from %2', $testEmail, $from['email']));

                $this->_message
                    ->setMessageId()
                    ->addTo($testEmail)
                    ->setSubject(__('Amasty SMTP Email Test Message'))
                    ->setBodyText(__('If you see this e-mail, your configuration is OK.'))
                    ->setFrom($from['email'], $from['name'])
                ;

                try
                {
                    $this->sendMessage();
                    $this->debugLogger->log(__('Test e-mail was sent successfully!'));
                } catch (\Exception $e)
                {
                    $this->debugLogger->log(__('Test e-mail failed: %1', $e->getMessage()));
                    throw $e;
                }
            }
        }
        else {
            $this->debugLogger->log(__(
                'Connection test failed: connection to %1 failed. Error: %2',
                $this->_host . ':' . $this->_port, $errstr . ' (' . $errno . ')'
            ));

            throw new \Exception(__('Connection failed'));
        }
    }

    public function getMessage()
    {
        return $this->_message;
    }
}
