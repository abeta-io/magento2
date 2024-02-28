<?php declare(strict_types=1);

namespace Abeta\PunchOut\Model\Config;

use Abeta\PunchOut\Api\Config\RepositoryInterface as ConfigRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Config repository class
 */
class Repository implements ConfigRepositoryInterface
{

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Repository constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @inheritDoc
     */
    public function getExtensionVersion(): string
    {
        return $this->getStoreValue(self::XML_PATH_EXTENSION_VERSION);
    }

    /**
     * Get Configuration data
     *
     * @param string $path
     * @param int|null $storeId
     *
     * @return string
     */
    private function getStoreValue(string $path, int $storeId = null): string
    {
        return (string)$this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, (int)$storeId);
    }

    /**
     * @inheritDoc
     */
    public function isDebugMode(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_DEBUG, $storeId);
    }

    /**
     * @param string $path
     * @param int|null $storeId
     *
     * @return bool
     */
    private function isSetFlag(string $path, int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, (int)$storeId);
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_EXTENSION_ENABLE, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getApiKey(): ?string
    {
        return $this->getStoreValue(self::XML_PATH_API_KEY);
    }

    /**
     * @inheritDoc
     */
    public function getApiVersion(): ?string
    {
        return $this->getStoreValue(self::XML_PATH_API_VERSION);
    }

    /**
     * @inheritDoc
     */
    public function getButtonLabel(): ?string
    {
        return $this->getStoreValue(self::XML_PATH_BUTTON_LABEL);
    }

    /**
     * @inheritDoc
     */
    public function getShippingMethod(): ?string
    {
        return $this->getStoreValue(self::XML_PATH_SHIPPING_METHOD);
    }

    /**
     * @inheritDoc
     */
    public function useModal(int $storeId = null): bool
    {
        return $this->isSetFlag(self::XML_PATH_USE_MODAL, $storeId);
    }

    /**
     * @inheritDoc
     */
    public function getStoreInformation(int $storeId = null): array
    {
        return [
            'country_id' => $this->getStoreValue(self::XML_PATH_STORE_COUNTRY_ID, $storeId),
            'postcode' => $this->getStoreValue(self::XML_PATH_STORE_POSTCODE, $storeId),
        ];
    }
}
