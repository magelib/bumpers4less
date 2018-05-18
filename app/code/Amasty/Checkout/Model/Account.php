<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Checkout
 */


namespace Amasty\Checkout\Model;

class Account
{
    /**
     * @var \Magento\Sales\Api\OrderCustomerManagementInterface
     */
    protected $orderCustomerService;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    public function __construct(
        \Magento\Sales\Api\OrderCustomerManagementInterface $orderCustomerService,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Checkout\Model\Session $checkoutSession
    ) {
        $this->orderCustomerService = $orderCustomerService;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @return void
     */
    public function create()
    {
        if ($this->customerSession->isLoggedIn()) {
            $this->customerSession->setAmcheckoutRegisterMessage(['type' => 'error',
                'text' => __('Customer is already registered')]);
        }
        $orderId = $this->checkoutSession->getLastOrderId();
        if (!$orderId) {
            $this->customerSession->setAmcheckoutRegisterMessage(['type' => 'error',
                'text' => __('Your session has expired')]);
        }
        try {
            $this->orderCustomerService->create($orderId);
            $this->customerSession->setAmcheckoutRegisterMessage(['type' => 'success',
                'text' => __('Registration: A letter with further instructions will be sent to your email.')]);
        } catch (\Exception $e) {
            $this->customerSession->setAmcheckoutRegisterMessage(['type' => 'error',
                'text' =>  __('Something went wrong with the registration.')]);
            $this->messageManager->addExceptionMessage($e, __('Something went wrong with the registration.'));
        }
    }
}
