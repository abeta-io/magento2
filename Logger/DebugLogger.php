<?php declare(strict_types=1);

namespace Abeta\PunchOut\Logger;

use Abeta\PunchOut\Api\Config\RepositoryInterface as ConfigProvider;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Logger;

/**
 * Wrapper around Monolog\Logger to log error-level messages for module.
 * Automatically serializes array or object input using Magento's JSON serializer.
 *
 * Example usage:
 * $logger->addLog('API Error', ['message' => 'Debug response', 'code' => 200]);
 */
class DebugLogger
{

    private const REPLACE_KEY_VALUE = ['password' => '****'];

    private Logger $logger;
    private Json $json;
    private ConfigProvider $configProvider;
    private RemoteAddress $remoteAddress;

    public function __construct(
        Logger $logger,
        Json $json,
        ConfigProvider $configProvider,
        RemoteAddress $remoteAddress
    ) {
        $this->logger = $logger;
        $this->json = $json;
        $this->configProvider = $configProvider;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * Add debug data to Log
     *
     * @param string $type
     * @param mixed $data
     */
    public function addLog(string $type, $data): void
    {
        if (!$this->configProvider->isDebugMode()) {
            return;
        }

        if (is_array($data) || is_object($data)) {
            $data = is_array($data) ? $this->cleanUpDebugLog($data) : $data;
            $this->logger->info( $type . ': ' . $this->json->serialize($data));
        } else {
            $this->logger->info( $type . ': ' . $data);
        }
    }

    /**
     * @param array $data
     * @return array
     */
    private function cleanUpDebugLog(array $data): array
    {
        foreach (self::REPLACE_KEY_VALUE as $key => $value) {
            if (isset($data[$key])) {
                $data[$key] = $value;
            }
        }

        $data['remote_address'] = $this->remoteAddress->getRemoteAddress();

        return $data;
    }
}
