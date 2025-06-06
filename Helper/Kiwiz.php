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

namespace Kwz\Certification\Helper;

use Kwz\Certification\Exception\InstallException;
use Kwz\Certification\Exception\NotConfiguredException;
use Kwz\Certification\Model\Flag\Time;
use Kwz\Certification\Model\Flag\KiwizConfigured;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Module\ModuleListInterface;
use \Magento\Framework\Stdlib\DateTime\DateTime;

class Kiwiz extends AbstractHelper
{
    const KIWIZ_URL = 'https://www.kiwiz.io';

    const MODULE_ID = 'Kwz_Certification';
    const MODULE_NAME = 'Kiwiz';

    const CONFIG_PATH_AUTH_APIURL                   = 'kiwizauth/auth/apiurl';
    const CONFIG_PATH_AUTH_USERNAME                 = 'kiwizauth/auth/username';
    const CONFIG_PATH_AUTH_PASSWORD                 = 'kiwizauth/auth/password';
    const CONFIG_PATH_AUTH_SUBSCRIPTION_ID          = 'kiwizauth/auth/subscription_id';
    const CONFIG_PATH_GENERAL_LOGGER_ENABLE         = 'kiwizsettings/general/logger_enabled';
    const CONFIG_PATH_GENERAL_IS_TEST_MODE          = 'kiwizsettings/general/is_test_mode';
    const CONFIG_PATH_GENERAL_QUEUE_CRON_ENABLE     = 'kiwizsettings/general/queue_cron_enabled';
    const CONFIG_PATH_ATTRIBUTES_EAN13              = 'kiwizsettings/attributes/ean13';
    const CONFIG_PATH_ATTRIBUTES_MANUFACTURER       = 'kiwizsettings/attributes/manufacturer';
    const CONFIG_PATH_FAILURE_NOTIFICATION_ENABLE  = 'kiwizsettings/failure_alerting/enabled';
    const CONFIG_PATH_FAILURE_NOTIFICATION_TPL      = 'kiwizsettings/failure_alerting/template';
    const CONFIG_PATH_FAILURE_NOTIFICATION_TO       = 'kiwizsettings/failure_alerting/email_to';
    const CONFIG_PATH_FAILURE_NOTIFICATION_TO_NAME  = 'kiwizsettings/failure_alerting/email_to_name';
    const CONFIG_PATH_QUOTA_CRON_ENABLE             = 'kiwizsettings/quota/cron_enabled';
    const CONFIG_PATH_QUOTA_NOTIFICATION_TPL        = 'kiwizsettings/quota/quota_notification_template';
    const CONFIG_PATH_QUOTA_NOTIFICATION_THRESHOLD  = 'kiwizsettings/quota/quota_notification_threshold';
    const CONFIG_PATH_QUOTA_NOTIFICATION_TO         = 'kiwizsettings/quota/quota_notification_email_to';
    const CONFIG_PATH_QUOTA_NOTIFICATION_TO_NAME    = 'kiwizsettings/quota/quota_notification_email_to_name';



    const CRON_QUOTA_ENABLED = 'kiwizsettings/quota/cron_enabled';

    protected $isLoggerEnabled = null;
    protected $isCronEnabled = null;
    protected $isCronQuotaEnabled = null;

    protected $scopeConfig;
    protected $logger;
    protected $storeId;
    protected $date;
    protected $productMetadata;
    protected $encryptor;

    protected $timeFlag;
    protected $configuredFlag;
    protected $moduleList;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        PsrLoggerInterface $logger,
        DateTime $date,
        ProductMetadataInterface    $productMetadata,
        EncryptorInterface $encryptor,
        Time $timeFlag,
        KiwizConfigured $configuredFlag,
        ModuleListInterface $moduleList
    ) {
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->date = $date;
        $this->productMetadata = $productMetadata;
        $this->encryptor = $encryptor;
        $this->timeFlag = $timeFlag;
        $this->configuredFlag = $configuredFlag;
        $this->moduleList = $moduleList;

        parent::__construct($context);
    }

    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
    }

    public function getStoreConfig($path)
    {
        $value = $this->scopeConfig->getValue($path, 'stores', $this->storeId);
        if ($this->isEncrypted($path)) {
            return $this->decrypt($value);
        }
        return $value;
    }

    protected function isEncrypted($path)
    {
        return (in_array($path, [self::CONFIG_PATH_AUTH_PASSWORD]));
    }

    public function encrypt($value)
    {
        return $this->encryptor->encrypt($value);
    }

    public function decrypt($value)
    {
        return $this->encryptor->decrypt($value);
    }

    public function isLoggerEnabled()
    {
        if (null === $this->isLoggerEnabled) {
            $this->isLoggerEnabled = (bool) $this->getStoreConfig(self::CONFIG_PATH_GENERAL_LOGGER_ENABLE);
        }
        return $this->isLoggerEnabled;
    }

    public function isCronEnabled()
    {
        if (null === $this->isCronEnabled) {
            $this->isCronEnabled = (bool) $this->getStoreConfig(self::CONFIG_PATH_GENERAL_QUEUE_CRON_ENABLE);
        }
        return $this->isCronEnabled;
    }

    public function isCronQuotaEnabled()
    {
        if (null === $this->isCronQuotaEnabled) {
            $this->isCronQuotaEnabled = (bool) $this->getStoreConfig(self::CONFIG_PATH_QUOTA_CRON_ENABLE);
        }
        return $this->isCronQuotaEnabled;
    }

    public function sanitize($args)
    {
        $sanitizedArgs = [];
        $fieldsToSanitize = ['password', 'token'];
        if (is_array($args)) {
            foreach ($args as $key => $value) {
                if (in_array($key, $fieldsToSanitize)) {
                    $value = str_repeat('*', strlen($value));
                }
                $sanitizedArgs[$key] = $value;
            }
            return $sanitizedArgs;
        }
    }

    public function getCtype($attachment)
    {
        switch (true) {
            case $attachment instanceof \Zend_Pdf:
            default:
                return 'application/pdf';
        }
    }

    public function logInfo($message, $args = [])
    {
        if ($this->isLoggerEnabled()) {
            $this->logger->info($message, [$args]);
        }
    }

    public function logError($message, $args = [])
    {
        $this->logger->error($message, [$args]);
    }

    public function getNameDocument($nameDocument)
    {
        $date = $this->date->date('Y-m-d_H-i-s');
        return $nameDocument . '_certified_' . $date . '.pdf';
    }

    public function getAttributeText($orderProduct, $configCode)
    {
        $attributeCode = $this->getStoreConfig($configCode);
        if (!empty($orderProduct->getData($attributeCode))) {
            $attributeText = $orderProduct->getAttributeText($attributeCode);
            if (!empty($attributeText)) {
                if (is_array($attributeText)) {
                    return implode(',', $attributeText);
                } else {
                    return $attributeText;
                }
            }
            return $orderProduct->getData($attributeCode);
        }
        return null;
    }

    public function getPlatform()
    {
        return mb_strtolower(
            $this->productMetadata->getName() .
            '-' .
            $this->productMetadata->getEdition()
        );
    }

    public function getVersion()
    {
        $version = [];

        // Magento
        $version[] =
            mb_strtolower($this->productMetadata->getName()) .
            ':' .
            $this->productMetadata->getVersion();

        // Kiwiz
        $version[] = 'kiwiz:' . $this->getKiwizVersion();

        return implode('|', $version);
    }

    public function getKiwizVersion()
    {
        return $this->moduleList->getOne(self::MODULE_ID)['setup_version'];
    }

    public function isOrderInvoicesKiwizable($order)
    {
        foreach ($order->getInvoiceCollection() as $invoice) {
            if ($this->isDocumentKiwizable($invoice)) {
                if (empty($invoice->getKiwizBlockHash()) && empty($invoice->getKiwizFileHash())) {
                    return false;
                }
            }
        }
        return true;
    }

    public function isOrderCreditmemoKiwizable($order)
    {
        foreach ($order->getCreditmemosCollection() as $creditmemo) {
            if (empty($creditmemo->getKiwizBlockHash()) && empty($creditmemo->getKiwizFileHash())) {
                return false;
            }
        }
        return true;
    }

    public function isConfigured()
    {
        return (bool) $this->configuredFlag->getFlagData();
    }

    public function isDocumentKiwizable($document, $throw = false)
    {
        try {
            if(!$this->isConfigured())
                throw new NotConfiguredException(__('Kiwiz is still not configured'));

            $dateConfigured = new \Zend_Date(
                $this->configuredFlag->getFlagData(),
                \Zend_Date::ISO_8601
            );

            $dateDocument = new \Zend_Date($document->getCreatedAt(), \Zend_Date::ISO_8601);
            return $dateDocument->isLater($dateConfigured);
        } catch (\Exception $e) {
            $this->logError($e->getMessage(), $e->getTrace());
            if($throw) throw $e;
            return false;
        }
    }
}
