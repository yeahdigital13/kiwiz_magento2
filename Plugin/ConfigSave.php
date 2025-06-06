<?php
/**
 * ConfigSave
 *
 * @copyright Copyright © 2020 Staempfli AG. All rights reserved.
 * @author    juan.alonso@staempfli.com
 */

namespace Kwz\Certification\Plugin;

use Kwz\Certification\Model\Flag\TokenFactory;
use Magento\Store\Model\StoreManagerInterface;

class ConfigSave
{
    protected $tokenFlagFactory;
    protected $storeManager;

    public function __construct(TokenFactory $tokenFlagFactory, StoreManagerInterface $storeManager)
    {
        $this->storeManager = $storeManager;
        $this->tokenFlagFactory = $tokenFlagFactory;
    }

    public function aroundSave(
        \Magento\Config\Model\Config $subject,
        \Closure $proceed
    ) {
        if ($subject->getSection() == 'kiwizauth') {
            $stores = $this->storeManager->getStores();
            foreach ($stores as $store) {
                $tokenFlag = $this->tokenFlagFactory->create();
                $tokenFlag->setFlagByStore($store->getId())
                    ->loadSelf()
                    ->setFlagData(null)
                    ->save();
            }
        }
        return $proceed();
    }
}
