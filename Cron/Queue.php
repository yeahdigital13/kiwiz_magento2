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

namespace Kwz\Certification\Cron;

use Kwz\Certification\Helper\Kiwiz;

class Queue
{
    protected $queue;
    protected $helper;
    public function __construct(\Kwz\Certification\Model\Documents\Queue $queue, Kiwiz $helper)
    {
        $this->queue = $queue;
        $this->helper = $helper;
    }

    public function execute()
    {
        if ($this->helper->isCronEnabled()) {
            $this->helper->logInfo(__('Queue cron started'));
            $this->queue->processQueue();
            $this->helper->logInfo(__('Queue cron finished'));

        }
    }
}
