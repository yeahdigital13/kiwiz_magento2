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

namespace Kwz\Certification\Ui\Component\Listing\Column;

use Kwz\Certification\Helper\Status;
use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class KiwizSynchronization extends Column
{
    protected $escaper;

    protected $systemStore;
    protected $storeManager;
    protected $assetRepo;
    protected $statusHelper;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        \Magento\Framework\View\Asset\Repository $assetRepo,
        Status $statusHelper,
        array $components = [],
        array $data = []
    ) {
        $this->escaper = $escaper;
        $this->assetRepo = $assetRepo;
        $this->statusHelper = $statusHelper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return string
     */
    public function getAlt($isSynchronized)
    {
        if ($isSynchronized == 'warning') {
            return __('ongoing synchronization');
        }
        return $isSynchronized ? __('synchronized'): __('not synchronized');
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            $fieldName = $this->getData('name');
            foreach ($dataSource['data']['items'] as & $item) {
                $item[$fieldName . '_src'] = $this->assetRepo->getUrl(
                    'Kwz_Certification::images/grid/' . $this->statusHelper->getImgByStatus($item[$this->getData('name')])
                );
                $item[$fieldName . '_alt'] = $this->getAlt(
                    $this->statusHelper->getLabelByStatus($item[$this->getData('name')])
                );
            }
        }
        return $dataSource;
    }
}
