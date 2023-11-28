<?php declare(strict_types=1);

namespace Abeta\PunchOut\Model\LoginToken;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * LoginToken resource class
 *
 */
class ResourceModel extends AbstractDb
{

    /**
     * Table name
     */
    public const ENTITY_TABLE = 'abeta_login_token';

    /**
     * Primary field
     */
    public const PRIMARY = 'entity_id';

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(
            self::ENTITY_TABLE,
            self::PRIMARY
        );
    }

    /**
     * Check if entity exists
     *
     * @param int $entityId
     * @return bool
     */
    public function isExists(int $entityId): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::ENTITY_TABLE), self::PRIMARY)
            ->where(sprintf('%s = :%s', self::PRIMARY, self::PRIMARY));
        $bind = [sprintf(':%s', self::PRIMARY) => $entityId];
        return (bool)$connection->fetchOne($select, $bind);
    }

    /**
     * Check if token exists
     *
     * @param string $token
     * @return bool
     */
    public function isTokenExists(string $token): bool
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::ENTITY_TABLE), self::PRIMARY)
            ->where('token = :token');
        $bind = [':token' => $token];
        return (bool)$connection->fetchOne($select, $bind);
    }
}
