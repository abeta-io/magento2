<?php declare(strict_types=1);

namespace Abeta\PunchOut\Plugin\Quote\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\ChangeQuoteControl;

class ChangeQuoteControlPlugin
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * @param ChangeQuoteControl $subject
     * @param bool $result
     * @param CartInterface $quote
     * @return bool
     */
    public function afterIsAllowed(ChangeQuoteControl $subject, bool $result, CartInterface $quote): bool
    {
        return $this->checkoutSession->getAbetaSessionId()
            ? true
            : $result;
    }
}
