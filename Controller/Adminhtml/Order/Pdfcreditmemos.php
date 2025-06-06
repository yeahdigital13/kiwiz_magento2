<?php
/**
 * Pdfcreditmemos
 *
 * @copyright Copyright © 2020 Staempfli AG. All rights reserved.
 * @author    juan.alonso@staempfli.com
 */

namespace Kwz\Certification\Controller\Adminhtml\Order;


use Kwz\Certification\Helper\Kiwiz;
use Kwz\Certification\Helper\Printer;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order\Pdf\Creditmemo;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Pdfcreditmemos extends \Magento\Sales\Controller\Adminhtml\Order\Pdfcreditmemos
{
    protected $helper;
    protected $printer;
    protected $collection = [];

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        DateTime $dateTime,
        FileFactory $fileFactory,
        Creditmemo $pdfCreditmemo,
        Kiwiz $helper,
        Printer $printer
    ) {
        $this->helper = $helper;
        $this->printer = $printer;
        parent::__construct($context, $filter, $collectionFactory, $dateTime, $fileFactory, $pdfCreditmemo);
    }

    public function massAction(AbstractCollection $collection)
    {
        try{
            $creditmemoCollection = $this->collectionFactory->create()->setOrderFilter(['in' => $collection->getAllIds()]);
            if (!$creditmemoCollection->getSize()) {
                $this->messageManager->addErrorMessage(__('There are no printable documents related to selected orders.'));
                return $this->resultRedirectFactory->create()->setPath($this->getComponentRefererUrl());
            }

            return $this->printer->printDocument(
                Printer::TYPE_CREDITMEMO,
                null,
                null,
                $creditmemoCollection
            );
        }
        catch(\Exception $e){
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->_forward('noroute');
        }

    }
}
