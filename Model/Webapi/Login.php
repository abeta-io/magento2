<?php declare(strict_types=1);

namespace Abeta\PunchOut\Model\Webapi;

use Abeta\PunchOut\Api\Log\RepositoryInterface as LogRepository;
use Abeta\PunchOut\Api\Webapi\LoginInterface;
use Abeta\PunchOut\Service\Login\CreateToken as CreateLoginToken;
use Magento\Framework\Webapi\Rest\Request;

class Login implements LoginInterface
{

    /**
     * @var Request
     */
    private $request;
    /**
     * @var CreateLoginToken
     */
    private $createLoginToken;
    /**
     * @var LogRepository
     */
    private $logRepository;

    public function __construct(
        CreateLoginToken $createLoginToken,
        Request $request,
        LogRepository $logRepository
    ) {
        $this->createLoginToken = $createLoginToken;
        $this->request = $request;
        $this->logRepository = $logRepository;
    }

    /**
     * @inheritDoc
     */
    public function create(): array
    {
        try {
            $result = $this->createLoginToken->execute(
                $this->request->getBodyParams()
            );
            $this->logRepository->addDebugLog('WebApi - Login', $result);
        } catch (\Exception $exception) {
            $result = ['success' => false, 'message' => $exception->getMessage()];
            $this->logRepository->addErrorLog('WebApi - Login', $result);
        }

        return [$result];
    }
}
