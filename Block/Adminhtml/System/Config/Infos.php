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

namespace Kwz\Certification\Block\Adminhtml\System\Config;

use Kwz\Certification\Helper\Kiwiz;

class Infos extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_template = 'system/config/advanced/infos.phtml';

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return $this->_decorateRowHtml($element, $this->toHtml());
    }

    public function getLogoUrl()
    {
        return $this->getViewFileUrl('Kwz_Certification::images/logo.png');
    }

    public function getKiwizUrl()
    {
        return Kiwiz::KIWIZ_URL;
    }
}
