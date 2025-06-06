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

namespace Kwz\Certification\Controller\Adminhtml;

use Kwz\Certification\Exception\DocumentException;
use Kwz\Certification\Helper\Kiwiz;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Sales\Controller\Adminhtml\Invoice\AbstractInvoice\PrintAction;
use Kwz\Certification\Model\Documents\Invoice;

class PrintInvoice extends PrintAction
{
    protected $invoice;
    protected $helper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        Invoice $invoice,
        Kiwiz $helper
    ) {
        $this->invoice = $invoice;
        $this->helper = $helper;
        parent::__construct($context, $fileFactory, $resultForwardFactory);
    }

    public function execute()
    {
        $invoiceId = $this->getRequest()->getParam('invoice_id');
        if ($invoiceId) {
            $invoice = $this->_objectManager->create(
                \Magento\Sales\Api\InvoiceRepositoryInterface::class
            )->get($invoiceId);
            if ($invoice) {
                if ($this->helper->isDocumentKiwizable($invoice)) {
                    if (!empty($invoice->getKiwizBlockHash()) && !empty($invoice->getKiwizFileHash())) {
                        try {
                            $renderPdf = $this->invoice->loadDocument($invoiceId)->get();
                        } catch (\Exception $e) {
                            $this->messageManager->addErrorMessage($e->getMessage());
                            return $this->_redirect('sales/invoice/view', ['invoice_id' => $invoiceId]);
                        }
                        $date = $invoice->getCreatedAt();
                    } else {
                        $this->messageManager->addErrorMessage('The document has not been certified yet');
                        return $this->_redirect('sales/invoice/view', ['invoice_id' => $invoiceId]);
                    }
                } else {
                    $pdf = $this->_objectManager->create(\Magento\Sales\Model\Order\Pdf\Invoice::class)
                        ->getPdf([$invoice]);
                    $renderPdf = $pdf->render();
                    $date = $this->_objectManager->get(
                        \Magento\Framework\Stdlib\DateTime\DateTime::class
                    )->date('Y-m-d_H-i-s');
                }

                $fileContent = ['type' => 'string', 'value' => $renderPdf, 'rm' => true];

                return $this->_fileFactory->create(
                    'invoice' . $date . '.pdf',
                    $fileContent,
                    DirectoryList::VAR_DIR,
                    'application/pdf'
                );
            }
        } else {
            return $this->resultForwardFactory->create()->forward('noroute');
        }
    }
}
