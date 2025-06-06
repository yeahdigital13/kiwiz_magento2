<?php
namespace Kwz\Certification\Model\ResourceModel\Queue;

/**
 * Class Collection
 */
class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Init
     */
    protected function _construct() // phpcs:ignore PSR2.Methods.MethodDeclaration
    {
        $this->_init(
            \Kwz\Certification\Model\Queue::class,
            \Kwz\Certification\Model\ResourceModel\Queue::class
        );
    }
}
