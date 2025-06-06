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
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Controller\Guest\OrderLoader;
use Magento\Sales\Controller\Guest\OrderViewAuthorization;
use Kwz\Certification\Model\Documents\Creditmemo;

class PrintCreditmemo extends \Magento\Sales\Controller\Guest\PrintCreditmemo
{
    protected $creditmemo;
    protected $printer;
    protected $helper;

    public function __construct(
        Context $context,
        OrderViewAuthorization $orderAuthorization,
        \Magento\Framework\Registry $registry,
        PageFactory $resultPageFactory,
        CreditmemoRepositoryInterface $creditmemoRepository,
        OrderLoader $orderLoader,
        Creditmemo $creditmemo,
        Printer $printer,
        Kiwiz $helper
    ) {
        $this->creditmemo = $creditmemo;
        $this->printer = $printer;
        $this->helper = $helper;
        parent::__construct(
            $context,
            $orderAuthorization,
            $registry,
            $resultPageFactory,
            $creditmemoRepository,
            $orderLoader
        );
    }

    public function execute()
    {
        try {
            $result = $this->orderLoader->load($this->_request);
            if ($result instanceof \Magento\Framework\Controller\ResultInterface) {
                return $result;
            }
            $creditmemoId = (int)$this->getRequest()->getParam('creditmemo_id');
            if ($creditmemoId) {
                $creditmemo = $this->creditmemoRepository->get($creditmemoId);
                $order = $creditmemo->getOrder();
            } else {
                $order = $this->_coreRegistry->registry('current_order');
            }
            if ($this->orderAuthorization->canView($order)) {
                if (isset($creditmemo)) {
                    return $this->printer->printDocument(Printer::TYPE_CREDITMEMO, $order, $creditmemo);
                }
                return $this->printer->printDocument(Printer::TYPE_CREDITMEMO, $order);
            } else {
                return $this->resultRedirectFactory->create()->setPath('sales/guest/form');
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error has occurred, please try again later'));
            $this->_redirect('sales/order/history/');
        }
    }
}
