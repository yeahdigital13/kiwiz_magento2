<?php

namespace Kwz\Certification\Controller\Adminhtml\Send;

use Kwz\Certification\Exception\NotConfiguredException;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;

class Creditmemo extends \Magento\Backend\App\Action implements HttpGetActionInterface
{
    protected $creditmemo;
    public function __construct(Action\Context $context, \Kwz\Certification\Model\Documents\Creditmemo $creditmemo)
    {
        $this->creditmemo = $creditmemo;
        parent::__construct($context);
    }

    public function execute()
    {
        $messageManager = $this->getMessageManager();
        $creditmemoId = $this->getRequest()->getParam('id');
        try {
            $response = $this->creditmemo->loadDocument($creditmemoId)->send();
            if (isset($response['file_hash']) && isset($response['block_hash'])) {
                $messageManager->addSuccessMessage(__(
                    'Document has been sent to Kiwiz'
                ));
                $messageManager->addSuccessMessage(__('Block Hash: %1', $response['block_hash']));
                $messageManager->addSuccessMessage(__('File Hash: %1', $response['file_hash']));
            } else {
                $messageManager->addErrorMessage(__('Error during synchronizing to Kiwiz: Empty response from API'));
            }
        } catch (NotConfiguredException $e) {
            $messageManager->addWarningMessage($e->getMessage());
        } catch (\Exception $e) {
            $messageManager->addErrorMessage($e->getMessage());
        }
        $this->_redirect($this->getUrl('sales/creditmemo/view', ['creditmemo_id' => $creditmemoId]));
    }
}
