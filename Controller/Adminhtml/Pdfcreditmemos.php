<?php

namespace Kwz\Certification\Controller\Adminhtml;

use Kwz\Certification\Helper\Kiwiz;
use Kwz\Certification\Helper\Printer;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order\Pdf\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;

class Pdfcreditmemos extends \Magento\Sales\Controller\Adminhtml\Creditmemo\Pdfcreditmemos
{
    protected $helper;
    protected $printer;
    protected $collection = [];
    public function __construct(
        Context $context,
        Filter $filter,
        Creditmemo $pdfCreditmemo,
        DateTime $dateTime,
        FileFactory $fileFactory,
        CollectionFactory $collectionFactory,
        Kiwiz $helper,
        Printer $printer
    ) {
        $this->helper = $helper;
        $this->printer = $printer;
        parent::__construct($context, $filter, $pdfCreditmemo, $dateTime, $fileFactory, $collectionFactory);
    }

    public function massAction(AbstractCollection $collection)
    {
        try {
            return $this->printer->printDocument(
                Printer::TYPE_CREDITMEMO,
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
