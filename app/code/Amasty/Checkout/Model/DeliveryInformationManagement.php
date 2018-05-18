<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Checkout
 */

namespace Amasty\Checkout\Model;

use Amasty\Checkout\Api\DeliveryInformationManagementInterface;

class DeliveryInformationManagement implements DeliveryInformationManagementInterface
{
    /**
     * @var ResourceModel\Delivery
     */
    protected $deliveryResource;
    /**
     * @var Delivery
     */
    protected $delivery;

    public function __construct(
        \Amasty\Checkout\Model\ResourceModel\Delivery $deliveryResource,
        \Amasty\Checkout\Model\Delivery $delivery
    ) {
        $this->deliveryResource = $deliveryResource;
        $this->delivery = $delivery;
    }

    public function update($cartId, $date, $time)
    {
        $delivery = $this->delivery->findByQuoteId($cartId);

        $delivery->addData([
            'date' => strtotime($date) ?: null,
            'time' => $time >= 0 ? $time : null,
        ]);

        if ($delivery->getData('date') === null && $delivery->getData('time') === null) {
            if ($delivery->getId()) {
                $this->deliveryResource->delete($delivery);
            }
        }
        else {
            $this->deliveryResource->save($delivery);
        }

        return true;
    }
}
