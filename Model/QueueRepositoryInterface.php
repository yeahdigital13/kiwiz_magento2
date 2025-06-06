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

namespace Kwz\Certification\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Kwz\Certification\Api\Data\QueueInterface;
use Kwz\Certification\Api\Data\QueueSearchResultInterface;

interface QueueRepositoryInterface
{
    /**
     * @param int $id
     * @return \Kwz\Certification\Api\Data\QueueInterface
     * @return mixed
     */
    public function getById($id);

    /**
     * @param \Kwz\Certification\Api\Data\QueueInterface $queue
     * @return \Kwz\Certification\Api\Data\QueueInterface
     */
    public function save(QueueInterface $queue);

    /**
     * @param \Kwz\Certification\Api\Data\QueueInterface $queue
     * @return void
     */
    public function delete(QueueInterface $queue);

    /**
     * @param SearchCriteriaInterface $searchCriteria
     * @return QueueSearchResultInterface []
     */
    public function getList(SearchCriteriaInterface $searchCriteria);
}
