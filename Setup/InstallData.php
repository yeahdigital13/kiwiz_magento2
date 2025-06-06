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

namespace Kwz\Certification\Setup;

use Kwz\Certification\Model\Flag\Time;
use Kwz\Certification\Model\Flag\KiwizConfigured;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    protected $flagInstall;
    protected $flagConfigured;

    public function __construct(
        Time $flagInstall,
        KiwizConfigured $flagConfigured
    )
    {
        $this->flagInstall = $flagInstall;
        $this->flagConfigured = $flagConfigured;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $this->flagInstall->setFlagData(\Zend_Date::now()->get(\Zend_Date::ISO_8601))->save();
        $this->flagConfigured->setFlagData(null)->save();
    }
}
