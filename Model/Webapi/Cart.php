<?php declare(strict_types=1);

namespace Abeta\PunchOut\Model\Webapi;

use Abeta\PunchOut\Api\Log\RepositoryInterface as LogRepository;
use Abeta\PunchOut\Api\Webapi\CartInterface;
use Abeta\PunchOut\Service\Cart\Export as ExportCart;
use Magento\Checkout\Model\Session as CheckoutSession;

class Cart implements CartInterface
{

    /**
     * @var ExportCart
     */
    private $exportCart;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var LogRepository
     */
    private $logRepository;

    public function __construct(
        ExportCart $exportCart,
        CheckoutSession $checkoutSession,
        LogRepository $logRepository
    ) {
        $this->exportCart = $exportCart;
        $this->checkoutSession = $checkoutSession;
        $this->logRepository = $logRepository;
    }

    /**
     * @inheritDoc
     */
    public function export(): array
    {
        try {
            $cart = $this->checkoutSession->getQuote();
            $result = $this->exportCart->execute($cart);
            $this->logRepository->addDebugLog('WebApi - Cart', $result);
        } catch (\Exception $exception) {
            $result = ['success' => false, 'message' => $exception->getMessage()];
            $this->logRepository->addErrorLog('WebApi - Cart', $result);
        }

        return [$result];
    }
}
