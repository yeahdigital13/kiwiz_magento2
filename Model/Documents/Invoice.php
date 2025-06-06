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

use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\Order\Pdf\Invoice as InvoicePdf;

class Invoice extends DocumentAbstract
{
    public function __construct(
        InvoiceRepository $invoice,
        InvoicePdf $invoicePdf,
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
        $this->setDocument($invoice, $invoicePdf);
    }

    protected function getDocumentName()
    {
        return 'Invoice document';
    }

    protected function getDocumentType()
    {
        return 'invoice';
    }

    protected function getDocumentData()
    {
        if (!$this->document->getId()) {
            throw new DocumentException(__('Invoice could not be loaded'));
        }

        $order = $this->document->getOrder();
        $taxInfo = $this->taxHelper->getTaxInfo($this->document);

        $this->documentData = [
            'increment_id' => $this->document->getIncrementId(),
            'date' => $this->document->getCreatedAt(),
            'email' => $order->getCustomerEmail(),
            'billing_address' => $this->getInfoAddress(
                $this->addressRepository->get($this->document->getBillingAddressId())
            ),
            'payment_method' => $order->getPayment()->getMethodInstance()->getTitle(),
            'items' => [],
            'grand_total_excl_tax' => ($this->document->getBaseGrandTotal() - $this->document->getBaseTaxAmount()) > 0
                ? number_format($this->document->getBaseGrandTotal() - $this->document->getBaseTaxAmount(), 4, '.', '')
                : 0,
            'grand_total_tax_amount' => []
        ];

        // Shipping ?
        if($this->document->getShippingAddressId()) {
            // shipping_address
            $this->documentData['shipping_address'] = $this->getInfoAddress(
                $this->addressRepository->get($this->document->getShippingAddressId())
            );

            // shipping_method
            $this->documentData['shipping_method'] = $order->getShippingDescription();

            // shipping_amount_excl_tax
            $this->documentData['shipping_amount_excl_tax'] = ($this->document->getBaseShippingAmount() + $this->document->getBaseShippingDiscountTaxCompensationAmnt()) > 0
                ? number_format($this->document->getBaseShippingAmount() + $this->document->getBaseShippingDiscountTaxCompensationAmnt(), 4, '.', '')
                : 0;

            // shipping_tax_amount
            $this->documentData['shipping_tax_amount'] = [];
            foreach ($taxInfo[Tax::TYPE_TAX_SHIPPING] as $tax) {
                $this->documentData['shipping_tax_amount'][] = [
                    'tax_name' => $tax['title'],
                    'tax_value' => $tax['base_tax_amount'] > 0
                        ? number_format($tax['base_tax_amount'], 4, '.', '')
                        : 0
                ];
            }
        }

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

        // items
        $items = $this->document->getAllItems();
        foreach ($items as $item) {
            if ($item->getOrderItem()->isDummy()) continue;

            $orderProduct = $item->getOrderItem()->getProduct();
            $itemData = [
                'sku' => $item->getSku(),
                'ean13' => $this->kiwizHelper->getAttributeText(
                    $orderProduct,
                    Kiwiz::CONFIG_PATH_ATTRIBUTES_EAN13
                ),
                'product_name' => $item->getName(),
                'manufacturer' => $this->kiwizHelper->getAttributeText(
                    $orderProduct,
                    Kiwiz::CONFIG_PATH_ATTRIBUTES_MANUFACTURER
                ),
                'qty' => $item->getQty(),
                'row_total_excl_tax' => ($item->getBaseRowTotal() - $item->getBaseDiscountAmount() + $item->getBaseDiscountTaxCompensationAmount() + $item->getBaseWeeeTaxAppliedRowAmnt()) > 0
                    ? number_format(
                        $item->getBaseRowTotal() - $item->getBaseDiscountAmount() + $item->getBaseDiscountTaxCompensationAmount() + $item->getBaseWeeeTaxAppliedRowAmnt(),
                        4,
                        '.',
                        '')
                    : 0,
                'row_total_tax_amount' => []
            ];

            // row_total_tax_amount
            if (isset($taxInfo[Tax::TYPE_TAX_PRODUCT][$item->getId()])) {
                foreach ($taxInfo[Tax::TYPE_TAX_PRODUCT][$item->getId()] as $tax) {
                    $itemData['row_total_tax_amount'][] = [
                        'tax_name' => $tax['title'],
                        'tax_value' => $tax['base_tax_amount'] > 0
                            ? number_format($tax['base_tax_amount'], 4, '.', '')
                            : 0
                    ];
                }
            }

            // Add ItemData
            $this->documentData['items'][] = $itemData;
        }

        // Return
        return $this->documentData;
    }
}
