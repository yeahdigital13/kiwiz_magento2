<?php
namespace Kwz\Certification\Model\Source;

use Kwz\Certification\Helper\Status;
use \Magento\Framework\Data\OptionSourceInterface;

class KiwizStatus implements OptionSourceInterface
{
    protected $_statusHelper;

    public function __construct(
        Status $statusHelper
    ) {
        $this->_statusHelper = $statusHelper;
    }

    public function toOptionArray()
    {
        return $this->_statusHelper->toOptionArray();
    }
}