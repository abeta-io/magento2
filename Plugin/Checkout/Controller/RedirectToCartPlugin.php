<?php declare(strict_types=1);

namespace Abeta\PunchOut\Plugin\Checkout\Controller;

use Magento\Checkout\Controller\Onepage;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

/**
 * RedirectToCart Plugin
 */
class RedirectToCartPlugin
{
    /**
     * @var MessageManagerInterface
     */
    protected $messageManager;
    /**
     * @var RedirectFactory
     */
    protected $resultRedirectFactory;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * ChangeQuoteControlPlugin constructor.
     *
     * @param CheckoutSession $checkoutSession
     * @param MessageManagerInterface $messageManager
     * @param RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        MessageManagerInterface $messageManager,
        RedirectFactory $resultRedirectFactory
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }

    /**
     * Redirect customer to cart page if there is an active abeta session
     *
     * @param Onepage $subject
     * @param $proceed
     * @param RequestInterface $request
     * @return mixed
     */
    public function aroundDispatch(Onepage $subject, $proceed, RequestInterface $request)
    {
        if ($this->checkoutSession->getAbetaSessionId()) {
            // $this->messageManager->addErrorMessage(
            //    __('Checkout not supported when having active abeta session.')
            // );
            return $this->resultRedirectFactory->create()->setPath('checkout/cart');
        }
        return $proceed($request);
    }
}
