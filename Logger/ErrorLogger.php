<?php declare(strict_types=1);

namespace Abeta\PunchOut\Logger;

use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Serialize\Serializer\Json;
use Monolog\Logger;

/**
 * ErrorLogger logger class
 */
class ErrorLogger extends Logger
{

    /**
     * @var Json
     */
    private $json;
    /**
     * @var RemoteAddress
     */
    private $remoteAddress;

    public function __construct(
        Json $json,
        RemoteAddress $remoteAddress,
        string $name,
        array $handlers = [],
        array $processors = []
    ) {
        $this->json = $json;
        $this->remoteAddress = $remoteAddress;
        parent::__construct($name, $handlers, $processors);
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
            $this->addRecord(static::EMERGENCY, $type . ': ' . $this->json->serialize($data));
        } else {
            $this->addRecord(static::EMERGENCY, $type . ': ' . $data);
        }
    }
}
