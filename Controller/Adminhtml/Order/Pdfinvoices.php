<?php


namespace Kwz\Certification\Controller\Adminhtml\Order;

use Kwz\Certification\Helper\Kiwiz;
use Kwz\Certification\Helper\Printer;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order\Pdf\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class Pdfinvoices extends \Magento\Sales\Controller\Adminhtml\Order\Pdfinvoices
{
    protected $helper;
    protected $printer;
    protected $collection = [];

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        DateTime $dateTime,
        FileFactory $fileFactory,
        Invoice $pdfInvoice,
        Kiwiz $helper,
        Printer $printer
    ) {
        $this->helper = $helper;
        $this->printer = $printer;
        parent::__construct($context, $filter, $collectionFactory, $dateTime, $fileFactory, $pdfInvoice);
    }

    public function massAction(AbstractCollection $collection)
    {
        try {
            $invoicesCollection = $this->collectionFactory->create()->setOrderFilter(['in' => $collection->getAllIds()]);
            if (!$invoicesCollection->getSize()) {
                $this->messageManager->addErrorMessage(__('There are no printable documents related to selected orders.'));
                return $this->resultRedirectFactory->create()->setPath($this->getComponentRefererUrl());
            }

            return $this->printer->printDocument(
                Printer::TYPE_INVOICE,
                null,
                null,
                $invoicesCollection
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_forward('noroute');
        }
    }
}
