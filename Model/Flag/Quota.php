<?php
/**
 * Quota
 *
 * @copyright Copyright © 2020 Staempfli AG. All rights reserved.
 * @author    juan.alonso@staempfli.com
 */

namespace Kwz\Certification\Model\Flag;

use Magento\Framework\Flag;

class Quota extends Flag
{
    const FLAG_CODE = 'kiwiz_quota';

    protected $_flagCode = self::FLAG_CODE;

    public function setFlagByStoreQuota($quota, $store)
    {
        $this->_flagCode = sprintf('%s_%s_%s', self::FLAG_CODE, $store, $quota);
        return $this;
    }
}
