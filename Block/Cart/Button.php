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
     * Get the label for the PunchOut button
     *
     * @return string
     */
    public function getButtonLabel(): string
    {
        return $this->configProvider->getButtonLabel() ?? 'Punch Out';
    }

    /**
     * Determine if the PunchOut button should be displayed
     *
     * @return bool
     */
    public function isNeedToShow(): bool
    {
        return $this->configProvider->isEnabled()
            && $this->checkoutSession->getAbetaSessionId();
    }

    /**
     * Check if the PunchOut button should open in a modal
     *
     * @return bool
     */
    public function useModal(): bool
    {
        return $this->configProvider->useModal();
    }

    /**
     * Retrieve any custom CSS for the PunchOut button
     *
     * @return string|null
     */
    public function getCustomCss(): ?string
    {
        return $this->configProvider->getCustomCss();
    }

    /**
     * Generate the PunchOut URL
     *
     * @return string
     */
    public function getPunchOutUrl(): string
    {
        return $this->urlBuilder->getUrl('abeta/punchOut');
    }
}
