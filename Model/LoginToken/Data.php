<?php declare(strict_types=1);

namespace Abeta\PunchOut\Model\LoginToken;

use Abeta\PunchOut\Api\LoginToken\DataInterface;
use Magento\Framework\Api\ExtensibleDataInterface;
use Magento\Framework\Model\AbstractModel;

/**
 * Login Token data class
 */
class Data extends AbstractModel implements ExtensibleDataInterface, DataInterface
{
    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(ResourceModel::class);
    }

    /**
     * @inheritDoc
     */
    public function getToken(): string
    {
        return (string)$this->getData(self::TOKEN);
    }

    /**
     * @inheritDoc
     */
    public function setToken(string $token): DataInterface
    {
        return $this->setData(self::TOKEN, $token);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerId(): int
    {
        return (int)$this->getData(self::CUSTOMER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerId(int $customerId): DataInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function getSessionId(): string
    {
        return $this->getData(self::SESSION_ID);
    }

    /**
     * @inheritDoc
     */
    public function setSessionId(string $sessionId): DataInterface
    {
        return $this->setData(self::SESSION_ID, $sessionId);
    }

    /**
     * @inheritDoc
     */
    public function getStoreId(): int
    {
        return (int)$this->getData(self::STORE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setStoreId(int $storeId): DataInterface
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getEmptyCartOnLogin(): bool
    {
        return (bool)$this->getData(self::EMPTY_CART_ON_LOGIN);
    }

    /**
     * @inheritDoc
     */
    public function setEmptyCartOnLogin(bool $bool): DataInterface
    {
        return $this->setData(self::EMPTY_CART_ON_LOGIN, $bool);
    }

    /**
     * @inheritDoc
     */
    public function getLogoutOnPunchout(): bool
    {
        return (bool)$this->getData(self::LOGOUT_ON_PUNCHOUT);
    }

    /**
     * @inheritDoc
     */
    public function setLogoutOnPunchout(bool $bool): DataInterface
    {
        return $this->setData(self::LOGOUT_ON_PUNCHOUT, $bool);
    }

    /**
     * @inheritDoc
     */
    public function getReturnUrl(): string
    {
        return $this->getData(self::RETURN_URL);
    }

    /**
     * @inheritDoc
     */
    public function setReturnUrl(string $url): DataInterface
    {
        return $this->setData(self::RETURN_URL, $url);
    }
}
