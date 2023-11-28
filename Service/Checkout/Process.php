<?php declare(strict_types=1);

namespace Abeta\PunchOut\Service\Checkout;

use Abeta\PunchOut\Service\Api\Adapter;
use Abeta\PunchOut\Service\Cart\Export as ExportCart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

class Process
{

    /**
     * @var ExportCart
     */
    private $exportCart;
    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;
    /**
     * @var Adapter
     */
    private $adapter;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    public function __construct(
        ExportCart $exportCart,
        Adapter $adapter,
        CheckoutSession $checkoutSession,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->exportCart = $exportCart;
        $this->checkoutSession = $checkoutSession;
        $this->adapter = $adapter;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @param Quote $cart
     * @return array[]
     * @throws LocalizedException
     */
    public function execute(Quote $cart): array
    {
        $dataArray = $this->exportCart->execute($cart, false);
        $this->adapter->execute(Adapter::METHOD_POST, $dataArray);

        $redirectUrl = $this->checkoutSession->getAbetaReturnUrl();
        $cart->setIsActive(false);
        $this->quoteRepository->save($cart);

        return [
            [
                'success' => true,
                'message' => 'Successfully transferred cart to Abeta',
                'redirect_url' => $redirectUrl
            ]
        ];
    }
}
