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

namespace Kwz\Certification\Model\Source\Catalog;

use Magento\Framework\Model\AbstractModel;
use \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory;

class Attributes extends AbstractModel
{
    protected $attributeFactory;

    public function __construct(CollectionFactory $attributeFactory)
    {
        $this->attributeFactory = $attributeFactory;
    }

    public function toOptionArray()
    {
        $attributes = $this->attributeFactory->create();
        $attributesArray = [];
        foreach ($attributes as $attribute) {
            $attributesArray[] =[
                    'value' => $attribute->getAttributeCode(),
                    'label' => $attribute->getAttributeCode()
                ];
        }
        return $attributesArray;
    }
}
