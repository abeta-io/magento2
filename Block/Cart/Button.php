<?php declare(strict_types=1);

namespace Abeta\PunchOut\Block\Cart;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Abeta\PunchOut\Api\Config\RepositoryInterface as ConfigProvider;

class Button extends Template
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        $this->configProvider = $configProvider;
        $this->checkoutSession = $checkoutSession;
        parent::__construct($context, $data);
    }

    /**
     * @return string|null
     */
    public function getButtonLabel(): ?string
    {
        return $this->configProvider->getButtonLabel() ?? 'Punch Out';
    }

    /**
     * @return bool
     */
    public function isNeedToShow(): bool
    {
        return $this->configProvider->isEnabled()
            && $this->checkoutSession->getAbetaSessionId();
    }
}
