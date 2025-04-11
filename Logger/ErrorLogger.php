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
 * $logger->addLog('API Error', ['message' => 'Error msg', 'code' => 500]);
 */
class ErrorLogger
{

    private Logger $logger;
    private Json $json;
    private RemoteAddress $remoteAddress;

    public function __construct(
        Logger $logger,
        Json $json,
        RemoteAddress $remoteAddress
    ) {
        $this->logger = $logger;
        $this->json = $json;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * Add error data to Log
     *
     * @param string $type
     * @param mixed $data
     */
    public function addLog(string $type, $data): void
    {
        if (is_array($data) || is_object($data)) {
            $data['remote_address'] = $this->remoteAddress->getRemoteAddress();
            $this->logger->info( $type . ': ' . $this->json->serialize($data));
        } else {
            $this->logger->info( $type . ': ' . $data);
        }
    }

}
