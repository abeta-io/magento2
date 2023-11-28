<?php declare(strict_types=1);

namespace Abeta\PunchOut\Model\Config\Source;

use Magento\Shipping\Model\Config\Source\Allmethods;

class ShippingMethods extends Allmethods
{

    /**
     * Return array of carriers used for config.
     * If $isActiveOnlyFlag is set to true, will return only active carriers
     *
     * @param bool $isActiveOnlyFlag
     *
     * @return array
     */
    public function toOptionArray($isActiveOnlyFlag = false): array
    {
        return parent::toOptionArray();
    }
}
