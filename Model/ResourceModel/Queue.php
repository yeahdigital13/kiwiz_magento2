<?php
namespace Kwz\Certification\Model\ResourceModel;

/**
 * Class Queue
 */
class Queue extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Init
     */
    protected function _construct() // phpcs:ignore PSR2.Methods.MethodDeclaration
    {
        $this->_init('kiwiz_queue', 'value_id');
    }
}
