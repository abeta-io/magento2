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
    public const RETURN_URL = 'return_url';
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
     * @return string
     */
    public function getReturnUrl(): string;

    /**
     * @param string $url
     * @return DataInterface
     */
    public function setReturnUrl(string $url): self;

    /**
     * @return string
     */
    public function getCreatedAt(): string;
}
