<?php


namespace Kwz\Certification\Controller\Adminhtml;

use Kwz\Certification\Helper\Kiwiz;
use Kwz\Certification\Helper\Printer;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order\Pdf\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class Pdfinvoices extends \Magento\Sales\Controller\Adminhtml\Invoice\Pdfinvoices
{
    protected $helper;
    protected $printer;
    protected $collection = [];

    public function __construct(
        Context $context,
        Filter $filter,
        DateTime $dateTime,
        FileFactory $fileFactory,
        Invoice $pdfInvoice,
        CollectionFactory $collectionFactory,
        Kiwiz $helper,
        Printer $printer
    ) {
        $this->helper = $helper;
        $this->printer = $printer;
        parent::__construct($context, $filter, $dateTime, $fileFactory, $pdfInvoice, $collectionFactory);
    }

    public function massAction(AbstractCollection $collection)
    {
        try {
            return $this->printer->printDocument(
                Printer::TYPE_INVOICE,
                null,
                null,
                $collection
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_forward('noroute');
        }
    }
}
