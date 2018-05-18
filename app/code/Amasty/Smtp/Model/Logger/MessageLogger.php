<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Model\Logger;

use Amasty\Smtp\Model\Log;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\MessageInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\ScopeInterface;

class MessageLogger
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Amasty\Smtp\Model\Logger\DebugLogger
     */
    protected $debugLogger;

    /**
     * Core Date
     *
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $coreDate;

    /**
     * @var \Amasty\Smtp\Helper\Data
     */
    protected $helper;

    /**
     * MessageLogger constructor.
     *
     * @param ObjectManagerInterface   $objectManager
     * @param ScopeConfigInterface     $scopeConfig
     * @param DebugLogger              $debugLogger
     * @param \Amasty\Smtp\Helper\Data $helper
     * @param DateTime                 $coreDate
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ScopeConfigInterface $scopeConfig,
        DebugLogger $debugLogger,
        \Amasty\Smtp\Helper\Data $helper,
        DateTime $coreDate
    ) {
        $this->objectManager = $objectManager;
        $this->scopeConfig = $scopeConfig;
        $this->coreDate = $coreDate;
        $this->debugLogger = $debugLogger;
        $this->helper = $helper;
    }

    public function log(MessageInterface $message)
    {
        $storeId = $this->helper->getCurrentStore();

        if ($this->scopeConfig->isSetFlag(
            'amsmtp/general/log', ScopeInterface::SCOPE_STORE, $storeId
        )) {
            $recipients = $message->getRecipients();
            $recipient = reset($recipients);

            /** @var Log $logMessage */
            $logMessage = $this->objectManager->create('Amasty\Smtp\Model\Log');
            $logMessage->setData([
                'created_at'        => $this->coreDate->gmtDate(),
                'subject'           => $message->getSubject(),
                'body'              => $message->getBody()->getRawContent(),
                'recipient_email'   => $recipient,
                'status'            => Log::STATUS_PENDING
            ]);

            $logMessage->save();

            return $logMessage->getId();
        }
        else
            return false;
    }

    public function updateStatus($logId, $status)
    {
        $storeId = $this->helper->getCurrentStore();

        if ($this->scopeConfig->isSetFlag(
            'amsmtp/general/log', ScopeInterface::SCOPE_STORE, $storeId)
        ) {
            /** @var Log $logMessage */
            $logMessage = $this->objectManager->create('Amasty\Smtp\Model\Log');

            $logMessage->load($logId);

            if ($logMessage->getId()) {
                $logMessage
                    ->setStatus($status)
                    ->save();
            }
        }
    }

    public function autoClear()
    {
        $days = $this->scopeConfig->getValue('amsmtp/clear/email');
        if ($days) {
            $this->debugLogger->log(__('Starting to auto clear debug log (after %1 days)', $days));

            $logModel = $this->objectManager->get('Amasty\Smtp\Model\ResourceModel\Log');
            $logModel->clear($days);
        }
    }
}
