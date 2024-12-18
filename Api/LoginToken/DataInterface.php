<?php declare(strict_types=1);

namespace Abeta\PunchOut\Api\LoginToken;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * @api
 */
interface DataInterface extends ExtensibleDataInterface
{
    /**
     * Constants for keys of data array.
     */
    public const ENTITY_ID = 'entity_id';
    public const TOKEN = 'token';
    public const CUSTOMER_ID = 'customer_id';
    public const SESSION_ID = 'session_id';
    public const STORE_ID = 'store_id';
    public const EMPTY_CART_ON_LOGIN = 'empty_cart_on_login';
    public const LOGOUT_ON_PUNCHOUT = 'logout_on_punchout';
    public const RETURN_URL = 'return_url';
    public const REDIRECT_URL = 'redirect_url';
    public const CREATED_AT = 'created_at';

    /**
     * @return string
     */
    public function getToken(): string;

    /**
     * @param string $token
     * @return DataInterface
     */
    public function setToken(string $token): self;

    /**
     * @return int
     */
    public function getCustomerId(): int;

    /**
     * @param int $customerId
     * @return DataInterface
     */
    public function setCustomerId(int $customerId): self;

    /**
     * @return string
     */
    public function getSessionId(): string;

    /**
     * @param string $sessionId
     * @return DataInterface
     */
    public function setSessionId(string $sessionId): self;

    /**
     * @return int
     */
    public function getStoreId(): int;

    /**
     * @param int $storeId
     * @return DataInterface
     */
    public function setStoreId(int $storeId): self;

    /**
     * @return bool
     */
    public function getEmptyCartOnLogin(): bool;

    /**
     * @param bool $bool
     * @return DataInterface
     */
    public function setEmptyCartOnLogin(bool $bool): self;

    /**
     * @return bool
     */
    public function getLogoutOnPunchout(): bool;

    /**
     * @param bool $bool
     * @return DataInterface
     */
    public function setLogoutOnPunchout(bool $bool): self;

    /**
     * @return string
     */
    public function getReturnUrl(): string;

    /**
     * @param string $url
     * @return DataInterface
     */
    public function setReturnUrl(string $url): self;

    /**
     * @return string|null
     */
    public function getRedirectUlr(): ?string;

    /**
     * @param string|null $url
     * @return DataInterface
     */
    public function setRedirectUrl(?string $url): self;

    /**
     * @return string
     */
    public function getCreatedAt(): string;
}
