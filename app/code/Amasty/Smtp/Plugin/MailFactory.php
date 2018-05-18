<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\TransportInterfaceFactory;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;

class MailFactory {

    protected $_instanceName = null;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;
    /**
     * @var \Amasty\Smtp\Helper\Data
     */
    protected $helper;

    /**
     * MailFactory constructor.
     *
     * @param ScopeConfigInterface     $scopeConfig
     * @param ObjectManagerInterface   $objectManager
     * @param \Amasty\Smtp\Helper\Data $helper
     * @param string                   $instanceName
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        ObjectManagerInterface $objectManager,
        \Amasty\Smtp\Helper\Data $helper,
        $instanceName = 'Amasty\Smtp\Model\Transport'
    ) {
        $this->_instanceName = $instanceName;
        $this->scopeConfig = $scopeConfig;
        $this->_objectManager = $objectManager;
        $this->helper = $helper;
    }

    public function aroundCreate(
        TransportInterfaceFactory $subject,
        \Closure $proceed,
        array $data = []
    ) {
        $storeId = $this->helper->getCurrentStore();
        
        $smtpEnabled = $this->scopeConfig->isSetFlag(
            'amsmtp/general/enable', ScopeInterface::SCOPE_STORE, $storeId
        );

        if ($smtpEnabled) {
            $config = $this->scopeConfig->getValue(
                'amsmtp/smtp', ScopeInterface::SCOPE_STORE, $storeId
            );

            $data['host'] = $config['server'];
            $data['parameters'] = [
                'port' => $config['port'],
                'auth' => $config['auth'],
                'ssl'  => $config['sec'],

                'username' => trim($config['login']),
                'password' => trim($config['passw']),
            ];

            if (!$data['parameters']['ssl']) {
                unset($data['parameters']['ssl']);
            }

            return $this->_objectManager->create($this->_instanceName, $data);
        }
        else {
            return $proceed($data);
        }
    }
}
