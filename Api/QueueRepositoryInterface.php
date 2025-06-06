<?php
namespace Kwz\Certification\Api;

use Kwz\Certification\Api\Data\QueueInterface;
use Magento\Framework\Api\SearchCriteriaInterface;

/**
 * Interface QueueRepositoryInterface
 *
 * @api
 */
interface QueueRepositoryInterface
{
    /**
     * Create or update a Queue.
     *
     * @param QueueInterface $page
     * @return QueueInterface
     */
    public function save(QueueInterface $page);

    /**
     * Get a Queue by Id
     *
     * @param int $id
     * @return QueueInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If Queue with the specified ID does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($id);

    /**
     * Retrieve Queues which match a specified criteria.
     *
     * @param SearchCriteriaInterface $criteria
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * Delete a Queue
     *
     * @param QueueInterface $page
     * @return QueueInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If Queue with the specified ID does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(QueueInterface $page);

    /**
     * Delete a Queue by Id
     *
     * @param int $id
     * @return QueueInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If customer with the specified ID does not exist.
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);
}
