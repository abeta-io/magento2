<?php declare(strict_types=1);

namespace Abeta\PunchOut\Model\LoginToken;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * LoginToken collection class
 */
class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = ResourceModel::PRIMARY;

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            Data::class,
            ResourceModel::class
        );
    }
}
