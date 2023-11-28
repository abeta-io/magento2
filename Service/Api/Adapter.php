<?php declare(strict_types=1);

namespace Abeta\PunchOut\Service\Api;

use Abeta\PunchOut\Api\Log\RepositoryInterface as LogRepository;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\ClientInterface as Curl;
use Magento\Framework\Serialize\Serializer\Json;

/**
 * Adapter class
 */
class Adapter
{

    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';

    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var Json
     */
    private $json;
    /**
     * @var Curl
     */
    private $curl;

    /**
     * Adapter constructor.
     *
     * @param Curl $curl
     * @param Json $json
     * @param LogRepository $logRepository
     */
    public function __construct(
        Curl $curl,
        Json $json,
        LogRepository $logRepository
    ) {
        $this->curl = $curl;
        $this->json = $json;
        $this->logRepository = $logRepository;
    }

    /**
     * @param $action
     * @param $data
     * @return string
     * @throws LocalizedException
     */
    public function execute($action, $data = [])
    {
        $this->curl->addHeader('Content-Type', 'application/json');
        $this->logRepository->addDebugLog(sprintf('API CALL: [%s]', $action), $data);

        if ($this->getMethod($action) == self::METHOD_GET) {
            $this->curl->get($data['general']['return_url']);
        } elseif ($this->getMethod($action) == self::METHOD_POST) {
            $this->curl->post($data['general']['return_url'], $this->json->serialize($data));
        }

        $result = $this->curl->getBody(); //ToDo parse xml string
        $status = $this->curl->getStatus();

        $this->logRepository->addDebugLog(
            sprintf('API RESULT [%s => %s] (status: %s)', $action, $data['general']['return_url'], $status),
            $result
        );

        if ($status >= 100 && $status < 300) {
            return $result;
        }

        throw new LocalizedException(__('Something went wrong'));
    }

    /**
     * @param string $method
     *
     * @return string
     */
    private function getMethod(string $method): string
    {
        switch (strtoupper($method)) {
            case 'GET':
                return self::METHOD_GET;
            case 'POST':
                return self::METHOD_POST;
        }
        return '';
    }
}
