<?php

namespace Kwz\Certification\Plugin;

use Kwz\Certification\Model\Documents\Invoice;
use Kwz\Certification\Model\Documents\InvoiceFactory;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\ObjectManagerInterface;

class InvoiceView
{
    protected $object_manager;
    protected $_backendUrl;
    protected $_invoiceFactory;

    public function __construct(
        ObjectManagerInterface $om,
        UrlInterface $backendUrl,
        InvoiceFactory $invoiceFacctory
    ) {
        $this->object_manager = $om;
        $this->_backendUrl = $backendUrl;
        $this->_invoiceFactory = $invoiceFacctory;
    }

    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\Invoice\View $subject)
    {
        $invoice = $subject->getInvoice();
        if (!$invoice->getKiwizIsSynchronized()) {
            /** @var Invoice $invoiceKw */
            $invoiceKw = $this->_invoiceFactory->create();
            $invoiceKw->loadDocument($invoice->getId());

            if ($invoiceKw->canBeSent()) {
                $sendOrder = $this->_backendUrl->getUrl('kiwiz/send/invoice/', ['id' => $subject->getInvoice()->getId()]);
                $subject->addButton(
                    'sendkiwiz',
                    [
                        'label' => __('Send to Kiwiz'),
                        'onclick' => "setLocation('" . $sendOrder. "')",
                        'class' => 'ship primary'
                    ]
                );
            }
            return null;
        }
    }
}
