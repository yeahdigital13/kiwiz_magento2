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

namespace Kwz\Certification\Controller\Guest;

use Kwz\Certification\Helper\Kiwiz;
use Kwz\Certification\Helper\Printer;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Controller\Guest\OrderLoader;
use Magento\Sales\Controller\Guest\OrderViewAuthorization;
use Kwz\Certification\Model\Documents\Invoice;

class PrintInvoice extends \Magento\Sales\Controller\Guest\PrintInvoice
{
    protected $invoice;
    protected $helper;
    protected $printer;

    public function __construct(
        Context $context,
        OrderViewAuthorization $orderAuthorization,
        \Magento\Framework\Registry $registry,
        PageFactory $resultPageFactory,
        OrderLoader $orderLoader,
        Invoice $invoice,
        Kiwiz $helper,
        Printer $printer
    ) {
        $this->invoice = $invoice;
        $this->helper = $helper;
        $this->printer = $printer;
        parent::__construct($context, $orderAuthorization, $registry, $resultPageFactory, $orderLoader);
    }

    public function execute()
    {
        try{
            $result = $this->orderLoader->load($this->_request);
            if ($result instanceof \Magento\Framework\Controller\ResultInterface) {
                return $result;
            }

            $invoiceId = (int)$this->getRequest()->getParam('invoice_id');
            if ($invoiceId) {
                $invoice = $this->_objectManager->create(
                    \Magento\Sales\Api\InvoiceRepositoryInterface::class
                )->get($invoiceId);
                $order = $invoice->getOrder();
            } else {
                $order = $this->_coreRegistry->registry('current_order');
            }

            if ($this->orderAuthorization->canView($order)) {
                if (isset($invoice)) {
                    return $this->printer->printDocument(Printer::TYPE_INVOICE, $order, $invoice);
                }
                return $this->printer->printDocument(Printer::TYPE_INVOICE, $order);
            } else {
                return $this->resultRedirectFactory->create()->setPath('sales/guest/form');
            }
        }
        catch(\Exception $e){
            $this->messageManager->addErrorMessage(__('An error has occurred, please try again later'));
            $this->_redirect('sales/order/history/');
        }

    }
}
