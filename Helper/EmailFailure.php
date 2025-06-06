<?php
/**
 * Email
 *
 * @copyright Copyright © 2020 Staempfli AG. All rights reserved.
 * @author    juan.alonso@staempfli.com
 */

namespace Kwz\Certification\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\DataObject;
use Magento\Store\Model\ScopeInterface;

class EmailFailure extends AbstractHelper
{
    /**
     * Sender email config path - from default CONTACT extension
     */
    const XML_PATH_EMAIL_SENDER = 'contact/email/sender_email_identity';

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Translate\Inline\StateInterface
     */
    private $inlineTranslation;

    /**
     * @var \Magento\Framework\Mail\Template\TransportBuilder
     */
    private $transportBuilder;

    protected $scopeConfig;
    protected $storeId = null;

    /**
     * Demo constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation
     * @param \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Translate\Inline\StateInterface $inlineTranslation,
        \Magento\Framework\Mail\Template\TransportBuilder $transportBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $inlineTranslation;
        $this->transportBuilder = $transportBuilder;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Return store configuration value of your template field that which id you set for template
     *
     * @param string $path
     * @param int $storeId
     * @return mixed
     */
    private function getConfigValue($path, $storeId)
    {
        return $this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    public function setStoreId($storeId)
    {
        $this->storeId = $storeId;
        return $this;
    }

    /**
     * @return int
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getStoreId()
    {
        return $this->storeId ?: $this->storeManager->getStore()->getId();
    }

    /**
     * @param $variable
     * @param $receiverInfo
     * @param $templateId
     * @return $this
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function generateTemplate(array $variable, DataObject $receiverInfo, $templateId)
    {
        $this->transportBuilder->setTemplateIdentifier($templateId)
            ->setTemplateOptions(
                [
                    'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                    'store' => $this->getStoreId(),
                ]
            )
            ->setTemplateVars($variable)
            ->setFrom($this->emailSender())
            ->addTo($receiverInfo->getEmail(), $receiverInfo->getName());

        return $this;
    }

    /**
     * Return email for sender header
     * @return mixed
     */
    public function emailSender()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_SENDER,
            ScopeInterface::SCOPE_STORE
        );
    }

    private function getEmailReceiver()
    {
        return $this->scopeConfig->getValue(
            Kiwiz::CONFIG_PATH_FAILURE_NOTIFICATION_TO,
            ScopeInterface::SCOPE_STORE
        );
    }

    private function getEmailReceiverName()
    {
        return $this->scopeConfig->getValue(
            Kiwiz::CONFIG_PATH_FAILURE_NOTIFICATION_TO_NAME,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function canNotify()
    {
        return (bool) $this->getConfigValue(
            Kiwiz::CONFIG_PATH_FAILURE_NOTIFICATION_ENABLE,
            $this->getStoreId()
        );
    }

    public function notify($failure, $force = false)
    {
        if($force || $this->canNotify()) {
            $this->inlineTranslation->suspend();
            $this->generateTemplate(
                [
                    'failure' => $failure
                ],
                new DataObject([
                    'name' => $this->getEmailReceiverName(),
                    'email' => $this->getEmailReceiver()
                ]),
                $this->getConfigValue(
                    Kiwiz::CONFIG_PATH_FAILURE_NOTIFICATION_TPL,
                    $this->getStoreId()
                )
            );
            $transport = $this->transportBuilder->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
        }
        return $this;
    }
}
