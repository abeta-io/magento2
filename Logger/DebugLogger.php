<?php declare(strict_types=1);

namespace Abeta\PunchOut\Logger;

use Abeta\PunchOut\Api\Config\RepositoryInterface as ConfigProvider;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Logger;

/**
 * DebugLogger logger class
 */
class DebugLogger extends Logger
{

    /**
     * Data that should be removed from debug log.
     */
    private const REPLACE_KEY_VALUE = [
        'password' => '****'
    ];

    /**
     * @var Json
     */
    private $json;
    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    public function __construct(
        Json $json,
        ConfigProvider $configProvider,
        RemoteAddress $remoteAddress,
        string $name,
        array $handlers = [],
        array $processors = []
    ) {
        $this->json = $json;
        $this->configProvider = $configProvider;
        $this->remoteAddress = $remoteAddress;
        parent::__construct($name, $handlers, $processors);
    }

    /**
     * Add debug data to Log
     *
     * @param string $type
     * @param mixed $data
     */
    public function addLog(string $type, mixed $data): void
    {
        if (!$this->configProvider->isDebugMode()) {
            return;
        }

        if (is_array($data) || is_object($data)) {
            $data = is_array($data) ? $this->cleanUpDebugLog($data) : $data;
            $this->addRecord(static::INFO, $type . ': ' . $this->json->serialize($data));
        } else {
            $this->addRecord(static::INFO, $type . ': ' . $data);
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
