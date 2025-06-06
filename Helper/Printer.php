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

use Kwz\Certification\Exception\DocumentException;
use Kwz\Certification\Model\Documents\Creditmemo;
use Kwz\Certification\Model\Documents\Invoice;
use Magento\Backend\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

use Magento\Sales\Controller\Adminhtml\Invoice\AbstractInvoice\Pdfinvoices;
use Magento\Sales\Model\Order\Pdf\Invoice as PdfInvoice;
use Magento\Sales\Model\Order\Pdf\Creditmemo as PdfCreditmemo;

class Printer extends AbstractHelper
{
    const TYPE_INVOICE = 'invoice';
    const TYPE_CREDITMEMO = 'creditmemo';

    protected $document;
    protected $creditmemo;
    protected $invoice;
    protected $fileFactory;
    protected $order;
    protected $helper;

    protected $pdfInvoice;
    protected $pdfCreditmemo;


    public function __construct(
        Context $context,
        Invoice $invoice,
        Creditmemo $creditmemo,
        Kiwiz $helper,
        FileFactory $fileFactory,
        Pdfinvoice $pdfInvoice,
        PdfCreditmemo $pdfCreditmemo
    ) {
        $this->invoice = $invoice;
        $this->creditmemo = $creditmemo;
        $this->helper = $helper;
        $this->fileFactory = $fileFactory;
        $this->pdfInvoice = $pdfInvoice;
        $this->pdfCreditmemo = $pdfCreditmemo;
        parent::__construct($context);
    }

    protected function getDocumentCollection($typeDocument, $returnCollection = true)
    {
        switch ($typeDocument) {
            case self::TYPE_CREDITMEMO:
                $this->document = clone($this->creditmemo);
                if ($returnCollection && !empty($this->order)) {
                    return $this->order->getCreditmemosCollection();
                }
                break;

            case self::TYPE_INVOICE:
                $this->document = clone($this->invoice);
                if ($returnCollection && !empty($this->order)) {
                    return $this->order->getInvoiceCollection();
                }
                break;

            default:
                throw new DocumentException(__('Provided document type is not covered by kiwiz: %s', $typeDocument));
                break;
        }

        return null;
    }

    protected function getOrigDocumentHash($typeDocument, $document)
    {
        switch ($typeDocument) {
            case self::TYPE_CREDITMEMO:
                return [
                    'hash' => $this->pdfCreditmemo->getPdf([$document])->render(),
                    'date' => $document->getCreatedAt(),
                    'id' => $document->getIncrementId()
                ];
                break;

            case self::TYPE_INVOICE:
                return [
                    'hash' => $this->pdfInvoice->getPdf([$document])->render(),
                    'date' => $document->getCreatedAt(),
                    'id' => $document->getIncrementId()
                ];
                break;
        }

        return null;
    }

    protected function getPdfName($typedoc, $id, $date)
    {
        $date = new \DateTime($date);
        return implode(
                '_',
                [
                    $typedoc,
                    $id,
                    $date->format('Y_m_d_H_i_s')
                ]
            ).'.pdf';
    }

    protected function getZipName($typedoc)
    {
        return implode(
            '_',
            [
                $typedoc,
                'documents'
            ]
        ).'.zip';
    }

    protected function printFile($documentHashs, $typeDocument)
    {
        if (count($documentHashs) == 1) {
            $date = $documentHashs[0]['date'];
            $id = $documentHashs[0]['id'];
            $fileContent = ['type' => 'string', 'value' => $documentHashs[0]['hash'], 'rm' => true];

            return $this->fileFactory->create(
                $this->getPdfName($typeDocument, $id, $date),
                $fileContent,
                DirectoryList::VAR_DIR,
                'application/pdf'
            );
        } elseif (count($documentHashs) > 1) {
            if (!class_exists('\ZipArchive')) {
                $this->helper
                    ->logError(__('Impossible to send multiple invoices, please enable ZipArchive extension'));
            } else {
                $archive = new \ZipArchive();
                $path = DirectoryList::VAR_DIR;
                $archive->open($path  ."/".$this->getZipName($typeDocument), \ZipArchive::CREATE);
                foreach ($documentHashs as $documentHash) {
                    $date = $documentHash['date'];
                    $id = $documentHash['id'];
                    $archive->addFromString($this->getPdfName($typeDocument, $id, $date), $documentHash['hash']);
                }
                $archive->close();
                return $this->fileFactory->create(
                    $this->getZipName($typeDocument),
                    [
                        'type' => 'filename',
                        'value' => $this->getZipName($typeDocument),
                        'rm' => true
                    ],
                    DirectoryList::VAR_DIR,
                    'application/zip'
                );
            }
        }
    }

    protected function throwNotSyncException()
    {
        // TODO ?
    }

    public function printDocument($typeDocument, $order = null, $document = null, $documents = null)
    {
        $documentHashs = [];

        // Collection
        if (!empty($documents)) {
            foreach ($documents as $doc) {
                if($this->helper->isDocumentKiwizable($doc)) {
                    if (!empty($doc->getKiwizBlockHash()) && !empty($doc->getKiwizFileHash())) {
                        $this->getDocumentCollection($typeDocument, false);
                        $documentHashs[] = [
                            'hash' => $this->document->loadDocument($doc->getId())->get(),
                            'date' => $doc->getCreatedAt(),
                            'id' => $doc->getIncrementId()
                        ];
                    } else {
                        $this->throwNotSyncException();
                    }
                } else {
                    $origDocumentHash = $this->getOrigDocumentHash($typeDocument, $doc);
                    if($origDocumentHash) {
                        $documentHashs[] = $origDocumentHash;
                    }
                }
            }
            return $this->printFile($documentHashs, $typeDocument);
        }

        // Single
        if (!empty($document)) {
            $this->getDocumentCollection($typeDocument);
            if($this->helper->isDocumentKiwizable($document)) {
                if (!empty($document->getKiwizBlockHash()) && !empty($document->getKiwizFileHash())) {
                    $documentHashs[] = [
                        'hash' => $this->document->loadDocument($document->getId())->get(),
                        'date' => $document->getCreatedAt(),
                        'id' => $document->getIncrementId()
                    ];
                } else {
                    $this->throwNotSyncException();
                }
            } else {
                $origDocumentHash = $this->getOrigDocumentHash($typeDocument, $document);
                if($origDocumentHash) {
                    $documentHashs[] = $origDocumentHash;
                }
            }
            return $this->printFile($documentHashs, $typeDocument);
        }

        // From order
        $this->order = $order;
        $collection = $this->getDocumentCollection($typeDocument);
        if($collection) {
            foreach ($this->getDocumentCollection($typeDocument) as $document) {
                if($this->helper->isDocumentKiwizable($document)) {
                    if (!empty($document->getKiwizBlockHash()) && !empty($document->getKiwizFileHash())) {
                        $documentObject = clone($this->document);
                        $documentHashs[] = [
                            'hash' => $documentObject->loadDocument($document->getId())->get(),
                            'date' => $document->getCreatedAt(),
                            'id' => $document->getIncrementId()
                        ];
                    } else {
                        $this->throwNotSyncException();
                    }
                } else {
                    $origDocumentHash = $this->getOrigDocumentHash($typeDocument, $document);
                    if($origDocumentHash) {
                        $documentHashs[] = $origDocumentHash;
                    }
                }
            }
            return $this->printFile($documentHashs, $typeDocument);
        }

        return null;
    }
}
