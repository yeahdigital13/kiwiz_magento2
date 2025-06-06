<?php
namespace Kwz\Certification\Plugin;

use Kwz\Certification\Helper\Status;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderSave
{
    /**
     * @var Status
     */
    protected $statusHelper;

    public function __construct(
        Status $statusHelper
    ) {
        $this->statusHelper = $statusHelper;
    }

    public function beforeSave(OrderInterface $order)
    {
        $orderStatus = $this->statusHelper->getOrderStatus($order);
        $order->setKiwizIsSynchronized($orderStatus);
    }
}