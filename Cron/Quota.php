<?php
/**
 * Kiwiz
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at the following URI:
 * https://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the PHP License and are unable to
 * obtain it through the web, please send a note to contact@kiwiz.io
 * so we can mail you a copy immediately.
 *
 * @author     Kiwiz <contact@kiwiz.io>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Kwz\Certification\Cron;

use Kwz\Certification\Exception\QuotaException;
use Kwz\Certification\Helper\Email;
use Kwz\Certification\Helper\Kiwiz;
use Kwz\Certification\Model\Client;
use Magento\Store\Model\StoreManagerInterface;

class Quota
{
    protected $helper;
    protected $email;
    protected $storeManager;
    protected $quota;
    protected $configurations = [];

    public function __construct(
        Kiwiz $helper,
        Email $email,
        StoreManagerInterface $storeManager,
        \Kwz\Certification\Model\Quota $quota
    ) {
        $this->email = $email;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->quota = $quota;
        $this->configurations = $this->getDifferentStoresConfigurations();
    }

    public function execute()
    {
        try {
            if ($this->helper->isCronQuotaEnabled()) {
                $this->helper->logInfo(__('Queue cron started'));
                foreach ($this->configurations as $configuration) {
                    $response = $this->quota->getQuota($configuration);
                    if (!empty($response) && isset($response['used']) && isset($response['limit'])) {
                        $this->email->setStoreId($configuration)->notify($response['used'], $response['limit']);
                    } else {
                        throw new QuotaException(__('Response from API was empty. Check log files'));
                    }
                }
                $this->helper->logInfo(__('Queue cron finished'));
            }
        } catch (\Exception $e) {
            $this->helper->logError($e->getMessage(), $e->getTrace());
        }
    }

    protected function getDifferentStoresConfigurations()
    {
        $storeManagerDataList = $this->storeManager->getStores();
        foreach ($storeManagerDataList as $key => $value) {
            $helper = clone($this->helper);
            $helper->setStoreId($key);
            $subscriberId = $helper->getStoreConfig(Kiwiz::CONFIG_PATH_AUTH_SUBSCRIPTION_ID);
            if (!in_array($subscriberId, $this->configurations)) {
                $this->configurations[$key] = $subscriberId;
            }
        }
        return array_keys($this->configurations);
    }
}
