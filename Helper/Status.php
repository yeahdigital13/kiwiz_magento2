<?php
/**
 * Status
 *
 * @copyright Copyright © 2020 Staempfli AG. All rights reserved.
 * @author    juan.alonso@staempfli.com
 */

namespace Kwz\Certification\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\ResourceConnection;

class Status extends AbstractHelper
{
    const KO = 0;
    const OK = 1;
    const WARNING = 2;

    /**
     * @var OrderRepositoryInterface
     */
    protected $orderRepository;

    /**
     * @var Kiwiz
     */
    protected $helper;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        Kiwiz $helper,
        ResourceConnection $resourceConnection
    ) {
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
        $this->resourceConnection = $resourceConnection;
        parent::__construct($context);
    }

    public function getOrderStatus($order)
    {
        $status = self::WARNING;
        if($order->getId()) {

            $invoices = $order->getInvoiceCollection();
            $creditmemo = $order->getCreditmemosCollection();

            if ($invoices->count() || $creditmemo->count()) {
                $status = self::OK;
            }

            // Check Invoices
            foreach ($invoices as $document) {
                if (!$this->helper->isDocumentKiwizable($document)) {
                    $status = Status::WARNING;
                    continue;
                }

                if (empty($document->getKiwizBlockHash())) {
                    return Status::KO;
                }
            }

            //  Check Creditmemos
            foreach ($creditmemo as $document) {
                if (!$this->helper->isDocumentKiwizable($document)) {
                    $status = Status::WARNING;
                    continue;
                }

                if (empty($document->getKiwizBlockHash())) {
                    return Status::KO;
                }
            }
        }

        return $status;
    }

    public function getImgByStatus($status)
    {
        switch ($status) {
            case self::WARNING:
                return 'warning.png';
                break;

            case self::OK:
                return 'ok.png';

            case self::KO:
                return 'ko.png';
        }
    }

    public function getLabelByStatus($status)
    {
        switch ($status) {
            case self::WARNING:
                return __('Warning');
                break;

            case self::OK:
                return __('Certified');

            case self::KO:
                return __('Not certified');

            default:
                return '';
                break;
        }
    }

    public function toOptionArray()
    {
        return [
            [
                'label' => $this->getLabelByStatus(self::OK),
                'value' => self::OK
            ],
            [
                'label' => $this->getLabelByStatus(self::KO),
                'value' => self::KO
            ],
            [
                'label' => $this->getLabelByStatus(self::WARNING),
                'value' => self::WARNING
            ]
        ];
    }

    public function updateDocumentStatus($documentId, $documentType, $status, $blockHash=null, $fileHash=null)
    {
        $connection  = $this->resourceConnection->getConnection();

        // UPDATE DOCUMENT
        $documentTable = $connection->getTableName('sales_'.$documentType);
        $documentTableGrid = $documentTable.'_grid';
        $connection->query("
            UPDATE $documentTable SET
                ".($blockHash !== null ? $blockHash !== false ? "kiwiz_block_hash = '$blockHash'," : "kiwiz_block_hash = NULL," : "")."
                ".($fileHash !== null ? $fileHash !== false ? "kiwiz_file_hash = '$fileHash'," : "kiwiz_file_hash = NULL," : "")."
                kiwiz_is_synchronized = '$status'
            WHERE 
                entity_id='$documentId'
        ");
        $connection->query("
            UPDATE $documentTableGrid SET
                ".($blockHash !== null ? $blockHash !== false ? "kiwiz_block_hash = '$blockHash'," : "kiwiz_block_hash = NULL," : "")."
                ".($fileHash !== null ? $fileHash !== false ? "kiwiz_file_hash = '$fileHash'," : "kiwiz_file_hash = NULL," : "")."
                kiwiz_is_synchronized = '$status'
            WHERE 
                entity_id='$documentId'
        ");
    }

    public function updateOrderStatus($order)
    {
        try {
            if(!is_object($order))
                $order = $this->orderRepository->get($order);
            $orderId = $order->getId();
            $orderStatus = $this->getOrderStatus($order);
            if($order->getKiwizIsSynchronized() != $orderStatus) {
                $connection  = $this->resourceConnection->getConnection();
                $orderTable = $connection->getTableName('sales_order');
                $orderTableGrid = $connection->getTableName('sales_order_grid');
                $connection->query("UPDATE $orderTable SET kiwiz_is_synchronized = '$orderStatus' WHERE entity_id='$orderId'");
                $connection->query("UPDATE $orderTableGrid SET kiwiz_is_synchronized = '$orderStatus' WHERE entity_id='$orderId'");
            }
        } catch (NoSuchEntityException $e) {}
    }
}
