<?php
namespace Kwz\Certification\Model;


/**
 * Class Queue
 */
class Queue extends \Magento\Framework\Model\AbstractModel implements
    \Kwz\Certification\Api\Data\QueueInterface,
    \Magento\Framework\DataObject\IdentityInterface
{
    const CACHE_TAG = 'kwz_certification_queue';
    const LAST_RESPONSE = 'last_response';
    const NB_SYNC = 'nb_sync';
    const DOCUMENT = 'document';
    const ID_DOC = 'id_doc';

    /**
     * Init
     */
    protected function _construct() // phpcs:ignore PSR2.Methods.MethodDeclaration
    {
        $this->_init(\Kwz\Certification\Model\ResourceModel\Queue::class);
    }

    /**
     * @inheritDoc
     */
    public function getIdentities()
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * @inheritDoc
     */
    public function getLastResponse()
    {
        return $this->_getData(self::LAST_RESPONSE);
    }

    /**
     * @inheritDoc
     */
    public function setLastResponse($response)
    {
        $this->setData(self::LAST_RESPONSE, $response);
    }

    /**
     * @inheritDoc
     */
    public function getNbSync()
    {
        return $this->_getData(self::NB_SYNC);
    }

    /**
     * @inheritDoc
     */
    public function setNbSync($nbSync)
    {
        $this->setData(self::NB_SYNC, $nbSync);
    }

    /**
     * @inheritDoc
     */
    public function getDocument()
    {
        return $this->_getData(self::DOCUMENT);
    }

    /**
     * @inheritDoc
     */
    public function setDocument($document)
    {
        $this->setData(self::DOCUMENT, $document);
    }

    /**
     * @inheritDoc
     */
    public function getIdDoc()
    {
        return $this->_getData(self::ID_DOC);
    }

    /**
     * @inheritDoc
     */
    public function setIdDoc($idDoc)
    {
        $this->setData(self::ID_DOC, $idDoc);
    }

}
