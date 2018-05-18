<?php
/**
* Magedelight
* Copyright (C) 2016 Magedelight <info@magedelight.com>
*
* @category Magedelight
* @package Magedelight_Arp
* @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
* @author Magedelight <info@magedelight.com>
*/

namespace Magedelight\Arp\Observer;

use Magento\Framework\Event\ObserverInterface;

class Mdfrd implements ObserverInterface
{
    /**
     * Core store config.
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var \Magento\Framework\Url\ScopeResolverInterface
     */
    protected $_context;

    protected $helper;

    /**
     * @var \Magento\Framework\UrlInterface
     */
    protected $_urlBuilder;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magedelight\Arp\Helper\Mddata $helper,
        \Magento\Framework\App\Action\Context $context
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->messageManager = $context->getMessageManager();
        $this->_urlBuilder = $context->getUrl();
        $this->helper = $helper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $event = $observer->getEvent()->getName();
        if ($_SERVER['SERVER_NAME'] != 'localhost' && $_SERVER['SERVER_ADDR'] != '127.0.0.1') {
            $keys['serial_key'] = $this->_scopeConfig->getValue('arp_products/license/serial_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $keys['activation_key'] = $this->_scopeConfig->getValue('arp_products/license/activation_key', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            $url = $this->_urlBuilder->getCurrentUrl();
            $parsedUrl = parse_url($url);
            $keys['host'] = $parsedUrl['host'];
            $keys['ip'] = $_SERVER['SERVER_ADDR'];
            $keys['product_name'] = 'Automatic Related Products';
            $keys['new_mechanism'] = 1;
            $keys['extension_key'] = $this->helper->getExtensionKey();
            $field_string = http_build_query($keys);
            $ch = curl_init('https://www.magedelight.com/ktplsys/?'.$field_string);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            try {
                curl_exec($ch);
                curl_close($ch);
            } catch (\Exception $e) {
                $this->messageManager->addError($e->getMessage());
            }
        }
    }
}
