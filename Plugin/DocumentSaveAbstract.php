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

namespace Kwz\Certification\Plugin;

use Kwz\Certification\Exception\NotConfiguredException;
use Kwz\Certification\Helper\Status;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;

abstract class DocumentSaveAbstract
{
    protected $messageManager;
    protected $document;

    public function __construct(ManagerInterface $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    protected function setDocument($document)
    {
        $this->document = $document;
    }

    protected function _canSend($document)
    {
        if($document->getKiwizStatusUpdate()) return false;
        if($document->getKiwizIsSynchronized() == Status::OK) return false;
        return true;
    }

    protected function _sendDocument($document)
    {
        if($this->_canSend($document)) {
            $response = $this->document->setDocument($document)->send();
            if (isset($response['file_hash']) && isset($response['block_hash'])) {
                $this->messageManager->addSuccessMessage(__(
                    'Document has been sent to Kiwiz'
                ));
            } else {
                throw new \Exception(__('Error during synchronizing to Kiwiz: Empty response from API'));//phpcs:ignore
            }
        }
    }

    protected function _afterSave($document)
    {
        try {
            $this->_sendDocument($document);
        } catch (NotConfiguredException $e) {
            $this->messageManager->addWarningMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
    }
}
