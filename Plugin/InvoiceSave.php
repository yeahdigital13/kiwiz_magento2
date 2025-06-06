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

use Magento\Framework\Message\ManagerInterface;
use Kwz\Certification\Model\Documents\Invoice;
use Magento\Sales\Api\Data\InvoiceInterface;

class InvoiceSave extends DocumentSaveAbstract
{
    protected $messageManager;

    public function __construct(Invoice $invoice, ManagerInterface $messageManager)
    {
        $this->setDocument($invoice);
        $this->messageManager = $messageManager;
        parent::__construct($this->messageManager);
    }

    public function afterSave(InvoiceInterface $document, $return)
    {
        parent::_afterSave($document);
        return $return;
    }
}
