<?php

namespace Kwz\Certification\Model\Flag;

use Magento\Framework\Flag;

class Token extends Flag
{
    const FLAG_CODE = 'kiwiz_token';
    protected $_flagCode = self::FLAG_CODE;
    
    public function setFlagByStore($store)
    {
        $this->_flagCode = sprintf('%s_%s', self::FLAG_CODE, $store);
        return $this;
    }
}
