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
use Kwz\Certification\Helper\Status;
use Kwz\Certification\Model\Flag\KiwizConfigured;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Sales\Model\Order\AddressRepository;
use Kwz\Certification\Helper\Kiwiz;
use Kwz\Certification\Helper\Tax;
use Kwz\Certification\Model\Client;
use Kwz\Certification\Model\QueueFactory;
use Kwz\Certification\Model\QueueRepository;

use Magento\Sales\Model\Order\CreditmemoRepository;
use Magento\Sales\Model\Order\Pdf\Creditmemo as CreditmemoPdf;

class Creditmemo extends DocumentAbstract
{
    public function __construct(
        CreditmemoRepository $creditmemo,
        CreditmemoPdf $creditmemoPdf,
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
        \Zend_Date $zendDate)
    {
        parent::__construct(
            $addressRepository,
            $client,
            $queueRepository,
            $queueFactory,
            $searchCriteriaBuilder,
            $kiwizHelper,
            $statusHelper,
            $taxHelper,
            $eventManager,
            $flag,
            $zendDate
        );
        $this->setDocument($creditmemo, $creditmemoPdf);
    }

    protected function getDocumentName()
    {
        return 'Creditmemo document';
    }

    protected function getDocumentType()
    {
        return 'creditmemo';
    }


    protected function getDocumentData()
    {
        if (!$this->document->getId()) {
            throw new DocumentException('Creditmemo could not be loaded'); //phpcs:ignore
        }

        $order = $this->document->getOrder();
        $taxInfo = $this->taxHelper->getTaxInfo($this->document);

        $this->documentData = [
            'increment_id' => $this->document->getIncrementId(),
            'date' => $this->document->getCreatedAt(),
            'grand_total_excl_tax' => ($this->document->getBaseGrandTotal() - $this->document->getBaseTaxAmount()) > 0
                ? number_format($this->document->getBaseGrandTotal() - $this->document->getBaseTaxAmount(), 4, '.', '')
                : 0,
            'grand_total_tax_amount' => [],
            'email' => $order->getCustomerEmail()
        ];

        // grand_total_tax_amount
        $taxTotal = array_merge(
            $taxInfo[Tax::TYPE_TAX_TOTAL][Tax::TYPE_TAX_TOTAL_TAX],
            $taxInfo[Tax::TYPE_TAX_TOTAL][Tax::TYPE_TAX_TOTAL_FIXED]
        );
        foreach ($taxTotal as $tax) {
            $this->documentData['grand_total_tax_amount'][] = [
                'tax_name' => $tax['title'],
                'tax_value' => $tax['base_tax_amount'] > 0
                    ? number_format($tax['base_tax_amount'], 4, '.', '')
                    : 0
            ];
        }

        // Return
        return $this->documentData;
    }
}
