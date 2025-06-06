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

namespace Kwz\Certification\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Sales\Model\EntityInterface;
use Magento\Sales\Model\ResourceModel\Order\Tax\Item;
use Magento\Tax\Api\Data\OrderTaxDetailsItemInterface;
use Magento\Tax\Api\OrderTaxManagementInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Weee\Helper\Data as WeeeHelper;

class Tax extends AbstractHelper
{
    const TYPE_TAX_PRODUCT = 'product';
    const TYPE_TAX_SHIPPING = 'shipping';
    const TYPE_TAX_TOTAL = 'total';
    const TYPE_TAX_TOTAL_TAX = 'tax';
    const TYPE_TAX_TOTAL_FIXED = 'fixed_tax';


    /**
     * @var TaxHelper
     */
    protected $_taxHelper;

    /**
     * @var WeeeHelper
     */
    protected $_weeeHelper;

    /**
     * @var OrderTaxManagementInterface
     */
    protected $orderTaxManagement;

    /**
     * @var Json
     */
    protected $serializer;

    /**
     * Tax constructor.
     * @param TaxHelper $taxHelper
     * @param OrderTaxManagementInterface $orderTaxManagement
     * @param Json $serializer
     * @param Context $context
     */
    public function __construct(
        TaxHelper $taxHelper,
        WeeeHelper $weeHelper,
        OrderTaxManagementInterface $orderTaxManagement,
        Json $serializer,
        Context $context
    ) {
        $this->_taxHelper = $taxHelper;
        $this->_weeeHelper = $weeHelper;
        $this->orderTaxManagement = $orderTaxManagement;
        $this->serializer = $serializer;
        parent::__construct($context);
    }

    public function getTaxInfo(EntityInterface $source)
    {

        $salesItem = $source;
        $order = $source->getOrder();

        $taxTotal = [
            self::TYPE_TAX_TOTAL_TAX => array_values($this->_taxHelper->getCalculatedTaxes($source)),
            self::TYPE_TAX_TOTAL_FIXED => []
        ];

        $taxClassAmount = [];
        $shippingTaxClassAmount = [];

        $orderTaxDetails = $this->orderTaxManagement->getOrderTaxDetails($order->getId());

        // Apply any taxes for the items
        /** @var $item \Magento\Sales\Model\Order\Invoice\Item|\Magento\Sales\Model\Order\Creditmemo\Item */
        foreach ($salesItem->getItems() as $item) {
            $salesItemId = $item->getId();
            if (!isset($taxClassAmount[$salesItemId])) {
                $taxClassAmount[$salesItemId] = [];
            }
            $orderItem = $item->getOrderItem();
            $orderItemId = $orderItem->getId();
            $orderItemTax = $orderItem->getTaxAmount();
            $itemTax = $item->getTaxAmount();
            if (!$itemTax || !(float)$orderItemTax) {
                continue;
            }
            //An invoiced item or credit memo item can have a different qty than its order item qty
            $itemRatio = $itemTax / $orderItemTax;
            $itemTaxDetails = $orderTaxDetails->getItems();
            foreach ($itemTaxDetails as $itemTaxDetail) {
                //Aggregate taxable items associated with an item
                if ($itemTaxDetail->getItemId() == $orderItemId) {
                    $taxClassAmount[$salesItemId] = $this->_aggregateTaxes($taxClassAmount[$salesItemId], $itemTaxDetail, $itemRatio);
                } elseif ($itemTaxDetail->getAssociatedItemId() == $orderItemId) {
                    $taxableItemType = $itemTaxDetail->getType();
                    $ratio = $itemRatio;
                    if ($item->getTaxRatio()) {
                        $taxRatio = $this->serializer->unserialize($item->getTaxRatio());
                        if (isset($taxRatio[$taxableItemType])) {
                            $ratio = $taxRatio[$taxableItemType];
                        }
                    }
                    $taxClassAmount[$salesItemId] = $this->_aggregateTaxes($taxClassAmount[$salesItemId], $itemTaxDetail, $ratio);
                }
            }

            // Weeeee
            $allWeeeApplied = $this->_weeeHelper->getApplied($item);
            foreach($allWeeeApplied as $weeeApplied) {
                $weeeAmount = $weeeApplied['row_amount'];
                $weeeBaseAmount = $weeeApplied['base_row_amount'];
                if (0 == $weeeAmount && 0 == $weeeBaseAmount) {
                    continue;
                }
                $weeeCode = $weeeApplied['title'];

                if (!isset($taxClassAmount[$salesItemId][$weeeCode])) {
                    $taxClassAmount[$salesItemId][$weeeCode] = [
                        'title' => $weeeCode,
                        'tax_amount' => $weeeAmount,
                        'base_tax_amount' => $weeeBaseAmount
                    ];
                } else {
                    $taxClassAmount[$salesItemId][$weeeCode]['tax_amount'] += $weeeAmount;
                    $taxClassAmount[$salesItemId][$weeeCode]['base_tax_amount'] += $weeeBaseAmount;
                }

                if (!isset($taxTotal[self::TYPE_TAX_TOTAL_FIXED][$weeeCode])) {
                    $taxTotal[self::TYPE_TAX_TOTAL_FIXED][$weeeCode] = [
                        'title' => $weeeCode,
                        'tax_amount' => $weeeAmount,
                        'base_tax_amount' => $weeeBaseAmount
                    ];
                } else {
                    $taxTotal[self::TYPE_TAX_TOTAL_FIXED][$weeeCode]['tax_amount'] += $weeeAmount;
                    $taxTotal[self::TYPE_TAX_TOTAL_FIXED][$weeeCode]['base_tax_amount'] += $weeeBaseAmount;
                }
            }


            $taxClassAmount[$salesItemId] = array_values($taxClassAmount[$salesItemId]);
        }
        $taxTotal[self::TYPE_TAX_TOTAL_FIXED] = array_values($taxTotal[self::TYPE_TAX_TOTAL_FIXED]);

        // Apply any taxes for shipping
        $shippingTaxAmount = $salesItem->getShippingTaxAmount();
        $originalShippingTaxAmount = $order->getShippingTaxAmount();
        if ($shippingTaxAmount && $originalShippingTaxAmount &&
            $shippingTaxAmount != 0 && (float)$originalShippingTaxAmount
        ) {
            //An invoice or credit memo can have a different qty than its order
            $shippingRatio = $shippingTaxAmount / $originalShippingTaxAmount;
            $itemTaxDetails = $orderTaxDetails->getItems();
            foreach ($itemTaxDetails as $itemTaxDetail) {
                //Aggregate taxable items associated with shipping
                if ($itemTaxDetail->getType() == \Magento\Quote\Model\Quote\Address::TYPE_SHIPPING) {
                    $shippingTaxClassAmount = $this->_aggregateTaxes($shippingTaxClassAmount, $itemTaxDetail, $shippingRatio);
                }
            }
        }
        $shippingTaxClassAmount = array_values($shippingTaxClassAmount);

        return [
            self::TYPE_TAX_PRODUCT =>  $taxClassAmount,
            self::TYPE_TAX_SHIPPING => $shippingTaxClassAmount,
            self::TYPE_TAX_TOTAL => $taxTotal
        ];
    }

    protected function _aggregateTaxes($taxClassAmount, OrderTaxDetailsItemInterface $itemTaxDetail, $ratio)
    {
        $itemAppliedTaxes = $itemTaxDetail->getAppliedTaxes();
        foreach ($itemAppliedTaxes as $itemAppliedTax) {
            $taxAmount = $itemAppliedTax->getAmount() * $ratio;
            $baseTaxAmount = $itemAppliedTax->getBaseAmount() * $ratio;

            if (0 == $taxAmount && 0 == $baseTaxAmount) {
                continue;
            }
            $taxCode = $itemAppliedTax->getCode();
            if (!isset($taxClassAmount[$taxCode])) {
                $taxClassAmount[$taxCode]['title'] = $itemAppliedTax->getTitle();
                $taxClassAmount[$taxCode]['percent'] = $itemAppliedTax->getPercent();
                $taxClassAmount[$taxCode]['tax_amount'] = $taxAmount;
                $taxClassAmount[$taxCode]['base_tax_amount'] = $baseTaxAmount;
            } else {
                $taxClassAmount[$taxCode]['tax_amount'] += $taxAmount;
                $taxClassAmount[$taxCode]['base_tax_amount'] += $baseTaxAmount;
            }
        }

        return $taxClassAmount;
    }
}
