<?php
namespace Kwz\Certification\Plugin;

use Kwz\Certification\Helper\Status;
use Magento\Sales\Api\OrderRepositoryInterface;

class OrderRepositorySave
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

    public function beforeSave(OrderRepositoryInterface $subject, $order)
    {
        $orderStatus = $this->statusHelper->getOrderStatus($order);
        $order->setKiwizIsSynchronized($orderStatus);
    }
}