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

namespace Kwz\Certification\Model\Documents;

use Kwz\Certification\Exception\DocumentException;
use Kwz\Certification\Exception\NotConfiguredException;
use Kwz\Certification\Exception\QueueException;
use Kwz\Certification\Helper\Kiwiz;
use Kwz\Certification\Model\QueueRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Setup\Exception;

class Queue
{
    protected $searchBuilderCriteria;
    protected $queueRepository;
    protected $invoice;
    protected $creditmemo;
    protected $helper;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        QueueRepository $queueRepository,
        Invoice $invoice,
        Creditmemo $creditmemo,
        Kiwiz $helper
    ) {
        $this->searchBuilderCriteria = $searchCriteriaBuilder;
        $this->queueRepository = $queueRepository;
        $this->invoice = $invoice;
        $this->creditmemo = $creditmemo;
        $this->helper = $helper;
    }

    public function processQueue()
    {
        $searchCriteria = $this->searchBuilderCriteria->create();
        $queueCollection = $this->queueRepository->getList($searchCriteria)->getItems();
        try {
            if (count($queueCollection) > 0) {
                $this->helper->logInfo(__('Queue processing started. %1 document to be processed', count($queueCollection)));
            }
            $success = 0;
            foreach ($queueCollection as $queue) {
                try {
                    switch ($queue->getDocument()) {
                        case Invoice::class:
                            $invoice = clone($this->invoice);
                            $invoice->loadDocument($queue->getIdDoc())->send();
                            $success++;
                            break;
                        case Creditmemo::class:
                            $creditmemo = clone($this->creditmemo);
                            $creditmemo->loadDocument($queue->getIdDoc())->send();
                            $success++;
                            break;
                        default:
                            throw new DocumentException(__('Unknown document type: %1 for queue id %2', $queue->getDocument(), $queue->getValueId()));
                    }
                } catch (NotConfiguredException $e) {
                    $this->helper->logError($e->getMessage(), $e->getTrace());
                } catch (DocumentException $documentException) {
                    $this->helper->logError($documentException->getMessage(), $documentException->getTrace());

                }
            }
        } catch (\Exception $e) {
            $this->helper->logError($e->getMessage(), $e->getTrace());
        }
        return __('%1 documents synchronized out of %2.', $success, count($queueCollection));
    }
}
