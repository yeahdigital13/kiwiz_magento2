<?php

namespace Kwz\Certification\Plugin;

use Kwz\Certification\Model\Documents\Creditmemo;
use Kwz\Certification\Model\Documents\CreditmemoFactory;
use Magento\Backend\Model\UrlInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\InvoiceRepository;

class CreditmemoView
{
    protected $object_manager;
    protected $_backendUrl;
    protected $_creditMemoFactory;

    public function __construct(
        ObjectManagerInterface $om,
        UrlInterface $backendUrl,
        CreditmemoFactory $creditmemoFactory
    ) {
        $this->object_manager = $om;
        $this->_backendUrl = $backendUrl;
        $this->_creditMemoFactory = $creditmemoFactory;
    }

    public function beforeSetLayout(\Magento\Sales\Block\Adminhtml\Order\Creditmemo\View $subject)
    {
        $creditmemo = $subject->getCreditmemo();
        if (!$creditmemo->getKiwizIsSynchronized()) {
            /** @var Creditmemo $creditmemoKw */
            $creditmemoKw = $this->_creditMemoFactory->create();
            $creditmemoKw->loadDocument($creditmemo->getId());

            if ($creditmemoKw->canBeSent()) {
                $sendOrder = $this->_backendUrl->getUrl(
                    'kiwiz/send/creditmemo/',
                    ['id' => $subject->getCreditmemo()->getId()]
                );
                $subject->addButton(
                    'sendkiwiz',
                    [
                        'label' => __('Send to Kiwiz'),
                        'onclick' => "setLocation('" . $sendOrder . "')",
                        'class' => 'ship primary'
                    ]
                );
            }
            return null;
        }
    }
}
