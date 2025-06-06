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
use Kwz\Certification\Exception\InstallException;
use Kwz\Certification\Exception\NotConfiguredException;
use Kwz\Certification\Exception\QueueException;
use Kwz\Certification\Helper\Kiwiz;
use Kwz\Certification\Helper\Status;
use Kwz\Certification\Helper\Tax;
use Kwz\Certification\Model\Client;
use Kwz\Certification\Model\Flag\KiwizConfigured;
use Kwz\Certification\Model\Queue;
use Kwz\Certification\Model\QueueFactory;
use Kwz\Certification\Model\QueueRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Sales\Model\Order\AddressRepository;
use Zend\Json\Json;

abstract class DocumentAbstract implements DocumentInterface
{
    const KIWIZ_EVENT = 'kiwiz_event_document_sent';

    protected $document;
    protected $documentPdf;
    protected $documentData = [];
    protected $queueFactory;
    protected $queueRepository;
    protected $addressRepository;
    protected $client;
    protected $searchCriteriaBuilder;
    protected $errorMessage;
    protected $response;
    protected $eventManager;
    protected $flag;
    protected $zendDate;
    protected $kiwizHelper;
    protected $taxHelper;
    protected $statusHelper;

    public function __construct(
        AddressRepository $addressRepository,
        Client $client,
        QueueRepository $queueRepository,
        QueueFactory $queueFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Kiwiz $kiwizHelper,
        Status $statusHelper,
        Tax $taxHelper,
        EventManager $eventManager,
        KiwizConfigured $flag,
        \Zend_Date $zendDate
    ) {
        $this->addressRepository = $addressRepository;
        $this->client = $client;
        $this->queueRepository = $queueRepository;
        $this->queueFactory = $queueFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->kiwizHelper = $kiwizHelper;
        $this->statusHelper = $statusHelper;
        $this->taxHelper = $taxHelper;
        $this->eventManager = $eventManager;
        $this->flag = $flag->loadSelf();
        $this->zendDate = $zendDate;
    }

    abstract protected function getDocumentData();
    abstract protected function getDocumentName();
    abstract protected function getDocumentType();

    protected function actionNameGet()
    {
        return 'get'.ucfirst(mb_strtolower($this->getDocumentType())).'Get';
    }

    protected function actionNameSave()
    {
        return 'post'.ucfirst(mb_strtolower($this->getDocumentType())).'Save';
    }

    public function setDocument($document, $documentPdf=null)
    {
        $this->document = $document;
        if($documentPdf !== null)
            $this->documentPdf = $documentPdf;
        return $this;
    }

    protected function _updateStatus($status, $blockHash, $fileHash)
    {
        // Update document
        // 2 methodes d'enregistrement pour prendre en compte
        // les difference entre creditmemo (repository save method)
        // et invoice (object save method)
        // qui ont un comportement different durant la transaction sql

        //#1
        $this->document->setKiwizStatusUpdate(true);
        $this->document->setKiwizBlockHash($blockHash);
        $this->document->setKiwizFileHash($fileHash);
        $this->document->setKiwizIsSynchronized($status);
        $this->document->save();

        //#2
        $this->statusHelper->updateDocumentStatus(
            $this->document->getId(),
            $this->getDocumentType(),
            $status,
            $blockHash ?: false,
            $fileHash ?: false
        );

        // Update order
        $this->statusHelper->updateOrderStatus($this->document->getOrderId());
    }

    public function send()
    {
        try {
            if (!$this->canBeSent()) {
                $this->removeFromQueue();
                if (!$this->kiwizHelper->isDocumentKiwizable($this->document)) {
                    if($this->document->getKiwizIsSynchronized() != Status::WARNING) {
                        $this->_updateStatus(
                            Status::WARNING,
                            null,
                            null
                        );
                    }
                    throw new NotConfiguredException(__('Kiwiz is still not configured'));
                }
                throw new DocumentException(__(
                    'The %1 can\'t be certified. Reason: %2',
                    $this->getDocumentName(),
                    $this->errorMessage
                ));
            }
        } catch (QueueException $queueException) {
            throw $queueException;
        }

        try {
            $documentData = Json::encode($this->getDocumentData());
            $response =  $this->client->{$this->actionNameSave()}(
                ['document' => $this->getPdfHash()],
                ['data' => $documentData],
                [Client::STORE_FIELD => $this->document->getStoreId()]
            );
            if (isset($response['file_hash']) && isset($response['block_hash'])) {
                $this->response = $response;
                $this->removeFromQueue();

                $this->_updateStatus(
                    Status::OK,
                    $this->response['block_hash'],
                    $this->response['file_hash']
                );

                $eventData = [
                    'order' => $this->document->getOrder(),
                    'document_type' => $this->getDocumentType(),
                    'document' => $this->document
                ];
                $this->eventManager->dispatch(self::KIWIZ_EVENT, $eventData);
                $this->eventManager->dispatch(self::KIWIZ_EVENT.'_'.$this->getDocumentType(), $eventData);
                return $this->response;
            }
        } catch (QueueException $queueException) {
            throw $queueException;
        } catch (\Exception $e) {

            $this->_updateStatus(
                Status::KO,
                null,
                null
            );

            $this->saveIntoQueue();
            throw $e;
        }
    }

    public function get($savePdf = false)
    {
        //TODO: Handle save of PDF if $savePdf = true for CLI
        $blockHash = $this->document->getKiwizBlockHash();
        return $this->client->{$this->actionNameGet()}(
            ['block_hash' => $blockHash],
            [Client::STORE_FIELD => $this->document->getStoreId()]
        );
    }

    public function getPdfHash()
    {
        return $this->documentPdf->getPdf([$this->document]);
    }

    public function loadDocument(string $id, bool $isIncrementId = false)
    {
        if (!$isIncrementId) {
            $this->document = $this->document->get($id);
        } else {
            $this->document = $this->document->loadByIncrementId($id);
        }
        if (is_array($this->document) || !$this->document->getId()) {
            throw new DocumentException(__(
                'Unknown %1 for id %2',
                $this->getDocumentName(),
                $id
            ));
        }
        return $this;
    }

    public function canBeSent()
    {
        if (!empty($this->document->getKiwizBlockHash()) || !empty($this->document->getKiwizFileHash())) {
            $this->errorMessage = __(
                '%1 have already been certified. File Hash: %2, Block Hash: %3',
                $this->getDocumentName(),
                $this->document->getKiwizFileHash(),
                $this->document->getKiwizBlockHash()
            );
            return false;
        }

        if(!$this->kiwizHelper->isDocumentKiwizable($this->document)) {
            return false;
        }

        return true;
    }

    protected function getNameDocument($nameDocument)
    {
        return $nameDocument . '_certified.pdf';
    }

    public function getDocument()
    {
        return $this->document;
    }

    public function saveIntoQueue()
    {
        $this->searchCriteriaBuilder->addFilter(Queue::ID_DOC, $this->document->getId(), 'eq');
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $queue = $this->queueRepository->getList($searchCriteria)->getItems();
        if (count($queue) == 1) {
            $queue = array_shift($queue);
        } else {
            $queue = $this->queueFactory->create();
        }
        $queue->setDocument(get_class($this));
        $queue->setLastResponse($this->client->getHttpClient()->getLastRawResponse());
        $queue->setNbSync($queue->getNbSync()+1);
        $queue->setIdDoc($this->document->getId() ? $this->document->getId() : false);
        $this->queueRepository->save($queue);
    }

    public function removeFromQueue()
    {
        $this->searchCriteriaBuilder->addFilter(Queue::ID_DOC, $this->document->getId(), 'eq');
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $queue = $this->queueRepository->getList($searchCriteria)->getItems();
        if (count($queue) == 1) {
            $queue = array_shift($queue);
            $queue->delete();
        } elseif (count($queue) > 1) {
            throw new QueueException(__('The item has duplicate entries in queue table. 
            Please clean the table kiwiz_queue'));
        }
    }

    protected function getInfoAddress($addressModel)
    {
        $address['firstname'] = $addressModel->getFirstname();
        $address['lastname'] = $addressModel->getLastname();
        $address['company'] = $addressModel->getCompany();
        $address['street'] = implode(',', $addressModel->getStreet());
        $address['postcode'] = $addressModel->getPostcode();
        $address['city'] = $addressModel->getCity();
        $address['country_code'] = $addressModel->getCountryId();
        return $address;
    }
}
