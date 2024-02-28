<?php declare(strict_types=1);

namespace Abeta\PunchOut\Api\Config;

/**
 * Config repository interface
 * @api
 */
interface RepositoryInterface
{

    public const EXTENSION_CODE = 'Abeta_PunchOut';
    public const XML_PATH_EXTENSION_VERSION = 'abeta_punch_out/general/version';
    public const XML_PATH_EXTENSION_ENABLE = 'abeta_punch_out/general/enable';
    public const XML_PATH_API_KEY = 'abeta_punch_out/general/api_key';
    public const XML_PATH_API_VERSION = 'abeta_punch_out/general/api_version';
    public const XML_PATH_BUTTON_LABEL = 'abeta_punch_out/settings/button_label';
    public const XML_PATH_SHIPPING_METHOD = 'abeta_punch_out/settings/shipping_method';
    public const XML_PATH_USE_MODAL = 'abeta_punch_out/settings/use_modal';
    public const XML_PATH_DEBUG = 'abeta_punch_out/debug/enabled';

    public const XML_PATH_STORE_COUNTRY_ID = 'general/store_information/country_id';
    public const XML_PATH_STORE_POSTCODE = 'general/store_information/postcode';

    /**
     * Get extension version
     *
     * @return string
     */
    public function getExtensionVersion(): string;

    /**
     * Check if module is enabled
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isEnabled(int $storeId = null): bool;

    /**
     * Get API Key
     *
     * @return string|null
     */
    public function getApiKey(): ?string;

    /**
     * Get API Version
     *
     * @return string|null
     */
    public function getApiVersion(): ?string;

    /**
     * Get Label Button
     *
     * @return string|null
     */
    public function getButtonLabel(): ?string;

    /**
     * Get default Shipping Method
     *
     * @return string|null
     */
    public function getShippingMethod(): ?string;

    /**
     * Check if we need to show modal
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function useModal(int $storeId = null): bool;

    /**
     * Check if debug mode is enabled
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isDebugMode(int $storeId = null): bool;

    /**
     * Get store information
     *
     * @param int|null $storeId
     *
     * @return array
     */
    public function getStoreInformation(int $storeId = null): array;
}
