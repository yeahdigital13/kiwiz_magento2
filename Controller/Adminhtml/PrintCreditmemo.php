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

use Kwz\Certification\Helper\Kiwiz;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Creditmemo\PrintAction;
use Kwz\Certification\Model\Documents\Creditmemo;

class PrintCreditmemo extends PrintAction
{
    protected $creditmemo;
    protected $helper;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        CreditmemoRepositoryInterface $creditmemoRepository,
        Creditmemo $creditmemo,
        Kiwiz $helper
    ) {
        $this->creditmemo = $creditmemo;
        $this->helper = $helper;
        parent::__construct($context, $fileFactory, $resultForwardFactory, $creditmemoRepository);
    }

    public function execute()
    {
        /** @see \Magento\Sales\Controller\Adminhtml\Order\Invoice */
        $creditmemoId = $this->getRequest()->getParam('creditmemo_id');
        if ($creditmemoId) {
            $creditmemo = $this->creditmemoRepository->get($creditmemoId);
            if ($creditmemo) {
                if ($this->helper->isDocumentKiwizable($creditmemo)) {
                    if (!empty($creditmemo->getKiwizBlockHash()) && !empty($creditmemo->getKiwizFileHash())) {
                        try {
                            $renderPdf = $this->creditmemo->loadDocument($creditmemoId)->get();
                        } catch (\Exception $e) {
                            $this->messageManager->addErrorMessage($e->getMessage());
                            return $this->_redirect('sales/creditmemo/view', ['creditmemo_id' => $creditmemoId]);
                        }
                        $date = $creditmemo->getCreatedAt();

                    } else {
                        $this->messageManager->addErrorMessage('The document has not been certified yet');
                        return $this->_redirect('sales/creditmemo/view', ['creditmemo_id' => $creditmemoId]);
                    }

                } else {
                    $pdf = $this->_objectManager->create(
                        \Magento\Sales\Model\Order\Pdf\Creditmemo::class
                    )->getPdf(
                        [$creditmemo]
                    );
                    $date = $this->_objectManager->get(
                        \Magento\Framework\Stdlib\DateTime\DateTime::class
                    )->date('Y-m-d_H-i-s');
                    $renderPdf = $pdf->render();
                }

                $fileContent = ['type' => 'string', 'value' => $renderPdf, 'rm' => true];

                return $this->_fileFactory->create(
                    'creditmemo' . $date . '.pdf',
                    $fileContent,
                    DirectoryList::VAR_DIR,
                    'application/pdf'
                );
            }
        } else {
            $resultForward = $this->resultForwardFactory->create();
            $resultForward->forward('noroute');
            return $resultForward;
        }
    }
}
