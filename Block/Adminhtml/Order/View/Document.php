<?php
/**
 * *
 *  * Kiwiz
 *  *
 *  * NOTICE OF LICENSE
 *  *
 *  * This source file is subject to the Open Software License (OSL 3.0)
 *  * that is available through the world-wide-web at the following URI:
 *  * https://opensource.org/licenses/osl-3.0.php
 *  * If you did not receive a copy of the PHP License and are unable to
 *  * obtain it through the web, please send a note to contact@kiwiz.io
 *  * so we can mail you a copy immediately.
 *  *
 *  * @author     Kiwiz <contact@kiwiz.io>
 *  * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 */

namespace Kwz\Certification\Block\Adminhtml\Order\View;

use Kwz\Certification\Helper\Status;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Block\Adminhtml\Order\View\Info;
use Kwz\Certification\Model\Documents\Creditmemo;
use Kwz\Certification\Model\Documents\Invoice;
use Kwz\Certification\Model\QueueFactory;
use Magento\Sales\Model\Order\Address;
use Kwz\Certification\Model\QueueRepository;

class Document extends Info
{
    protected $order;

    protected $queueFactory;
    protected $searchCriteriaBuilder;
    protected $queueRepository;
    protected $statusHelper;
    protected $statusDocument;
    const DOCUMENT_INVOICE = Invoice::class;
    const DOCUMENT_CREDITMEMO = Creditmemo::class;

    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Sales\Helper\Admin $adminHelper,
        \Magento\Customer\Api\GroupRepositoryInterface $groupRepository,
        \Magento\Customer\Api\CustomerMetadataInterface $metadata,
        \Magento\Customer\Model\Metadata\ElementFactory $elementFactory,
        Address\Renderer $addressRenderer,
        QueueFactory $queueFactory,
        QueueRepository $queueRepository,
        SearchCriteriaBuilder $searchCriteria,
        Status $statusHelper,
        array $data = []
    ) {
        $this->queueFactory = $queueFactory;
        $this->searchCriteriaBuilder = $searchCriteria;
        $this->queueRepository = $queueRepository;
        $this->statusHelper = $statusHelper;
        parent::__construct(
            $context,
            $registry,
            $adminHelper,
            $groupRepository,
            $metadata,
            $elementFactory,
            $addressRenderer,
            $data
        );
    }

    protected function _beforeToHtml()
    {
        return $this;
    }

    public function getQueuedInvoice($idInvoice)
    {
        return $this->getQueueDocument(self::DOCUMENT_INVOICE, $idInvoice);
    }

    public function getQueuedCreditmemo($idCreditmemo)
    {
        return $this->getQueueDocument(self::DOCUMENT_CREDITMEMO, $idCreditmemo);
    }

    protected function getQueueDocument($document, $idDoc)
    {
        $this->searchCriteriaBuilder->addFilter('document', $document, 'eq');
        $this->searchCriteriaBuilder->addFilter('id_doc', $idDoc, 'eq');
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $queue = $this->queueRepository->getList($searchCriteria)->getItems();
        if (count($queue) == 1) {
            $queue = array_shift($queue);
        } else {
            $queue = $this->queueFactory->create();
        }
        return $queue;
    }

    public function getStatusOrder($order)
    {
        $this->statusDocument = $order->getKiwizIsSynchronized();
        $img = $this->statusHelper->getImgByStatus($this->statusDocument);
        return $this->_assetRepo->getUrl('Kwz_Certification::images/grid/' . $img);
    }

    public function getLogo()
    {
        return $this->_assetRepo->getUrl('Kwz_Certification::images/logo.png');
    }

    public function getImgByStatus($status)
    {
        $img = $this->statusHelper->getImgByStatus($status);
        return $this->_assetRepo->getUrl('Kwz_Certification::images/grid/' . $img);
    }

    public function getTextCertified($status = null)
    {
        if ($status !== null) {
            $this->statusDocument = $status;
        }
        return $this->statusHelper->getLabelByStatus($this->statusDocument);
    }

    public function getInvoice()
    {
        return $this->_coreRegistry->registry('current_invoice');
    }

    public function getCreditmemo()
    {
        return $this->_coreRegistry->registry('current_creditmemo');
    }
}
