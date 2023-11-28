<?php declare(strict_types=1);

namespace Abeta\PunchOut\Model\Webapi;

use Abeta\PunchOut\Api\Log\RepositoryInterface as LogRepository;
use Abeta\PunchOut\Api\Webapi\CheckoutInterface;
use Abeta\PunchOut\Service\Checkout\Process as ProcessCheckout;
use Magento\Checkout\Model\Session as CheckoutSession;

class Checkout implements CheckoutInterface
{

    /**
     * @var ProcessCheckout
     */
    private $processCheckout;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var LogRepository
     */
    private $logRepository;

    public function __construct(
        ProcessCheckout $processCheckout,
        CheckoutSession $checkoutSession,
        LogRepository $logRepository
    ) {
        $this->processCheckout = $processCheckout;
        $this->checkoutSession = $checkoutSession;
        $this->logRepository = $logRepository;
    }

    /**
     * @inheritDoc
     */
    public function process(): array
    {
        try {
            $cart = $this->checkoutSession->getQuote();
            $result = $this->processCheckout->execute($cart);
            $this->logRepository->addDebugLog('WebApi - Checkout', $result);
        } catch (\Exception $exception) {
            $result = ['success' => false, 'message' => $exception->getMessage()];
            $this->logRepository->addErrorLog('WebApi - Checkout', $result);
        }

        return [$result];
    }
}
