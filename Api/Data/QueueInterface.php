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

namespace Kwz\Certification\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * Interface QueueInterface
 *
 * @api
 */
interface QueueInterface extends ExtensibleDataInterface
{
    /**
     * @return int
     */
    public function getId();

    /**
     * @param int $id
     * @return void
     */
    public function setId($id);

    /**
     * @return string $lastResponse
     */
    public function getLastResponse();

    /**
     * @param string $response
     * @return void
     */
    public function setLastResponse($response);

    /**
     * @return int
     */
    public function getNbSync();

    /**
     * @param: int $nbSync
     * @return void
     */
    public function setNbSync($nbSync);

    /**
     * @return string
     */
    public function getDocument();

    /**
     * @param string $document
     * @return void
     */
    public function setDocument($document);

}
