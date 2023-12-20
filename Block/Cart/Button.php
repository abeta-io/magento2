<?php declare(strict_types=1);

namespace Abeta\PunchOut\Block\Cart;

use Abeta\PunchOut\Api\Config\RepositoryInterface as ConfigProvider;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

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
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    public function __construct(
        Context $context,
        ConfigProvider $configProvider,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        $this->configProvider = $configProvider;
        $this->checkoutSession = $checkoutSession;
        $this->urlBuilder = $context->getUrlBuilder();
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

    /**
     * @return bool
     */
    public function useModal(): bool
    {
        return $this->configProvider->useModal();
    }

    /**
     * @return string
     */
    public function getPunchOutUrl(): string
    {
        return $this->urlBuilder->getUrl('abeta/punchOut');
    }
}
