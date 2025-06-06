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

namespace Kwz\Certification\Controller;

use Kwz\Certification\Helper\Kiwiz;
use Kwz\Certification\Helper\Printer;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Controller\AbstractController\OrderViewAuthorizationInterface;
use Kwz\Certification\Model\Documents\Creditmemo;

class PrintCreditmemo extends \Magento\Sales\Controller\Order\PrintCreditmemo
{
    protected $creditmemo;
    protected $fileFactory;
    protected $helper;
    protected $printer;

    public function __construct(
        Context $context,
        OrderViewAuthorizationInterface $orderAuthorization,
        \Magento\Framework\Registry $registry,
        PageFactory $resultPageFactory,
        CreditmemoRepositoryInterface $creditmemoRepository,
        Creditmemo $creditmemo,
        FileFactory $fileFactory,
        Kiwiz $helper,
        Printer $printer
    ) {
        $this->fileFactory = $fileFactory;
        $this->creditmemo = $creditmemo;
        $this->helper = $helper;
        $this->printer = $printer;
        parent::__construct($context, $orderAuthorization, $registry, $resultPageFactory, $creditmemoRepository);
    }

    public function execute()
    {
        try {
            $creditmemoId = (int)$this->getRequest()->getParam('creditmemo_id');
            if ($creditmemoId) {
                $creditmemo = $this->creditmemoRepository->get($creditmemoId);
                $order = $creditmemo->getOrder();
            } else {
                $orderId = (int)$this->getRequest()->getParam('order_id');
                $order = $this->_objectManager->create(\Magento\Sales\Model\Order::class)->load($orderId);
            }

            if ($this->orderAuthorization->canView($order)) {
                if (isset($creditmemo)) {
                    return $this->printer->printDocument(Printer::TYPE_CREDITMEMO, $order, $creditmemo);
                }
                return $this->printer->printDocument(Printer::TYPE_CREDITMEMO, $order);
            } else {
                /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
                $resultRedirect = $this->resultRedirectFactory->create();
                if ($this->_objectManager->get(\Magento\Customer\Model\Session::class)->isLoggedIn()) {
                    $resultRedirect->setPath('*/*/history');
                } else {
                    $resultRedirect->setPath('sales/guest/form');
                }
                return $resultRedirect;
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error has occurred, please try again later'));
            $this->_redirect('sales/order/history/');
        }
    }
}
