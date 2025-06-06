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

use Magento\Backend\Block\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Kwz\Certification\Model\Quota;

class Advanced extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_template = 'system/config/advanced/quota.phtml';
    protected $quota;
    protected $storeManager;

    public function __construct(Quota $quota, StoreManagerInterface $storeManager, Context $context, array $data = [])
    {
        $this->quota = $quota;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        $columns = $this->getRequest()->getParam('website') || $this->getRequest()->getParam('store') ? 5 : 4;
        return $this->_decorateRowHtml($element, "<td colspan='{$columns}'>" . $this->toHtml() . '</td>');
    }

    public function getQuotas()
    {
        $stores = $this->storeManager->getStores();
        $quotas = [];
        foreach ($stores as $store) {
            try {
                $quota = $this->quota->getQuota($store->getId());
                $quota['label'] = $store->getName();
                $quotas[] = $quota;
            } catch (\Exception $e) {}
        }
        return $quotas;
    }
}
