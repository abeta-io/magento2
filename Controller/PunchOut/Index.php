<?php declare(strict_types=1);

namespace Abeta\PunchOut\Controller\PunchOut;

use Abeta\PunchOut\Service\Checkout\Process as ProcessCheckout;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action
{

    /**
     * @var ProcessCheckout
     */
    private $processCheckout;
    /**
     * @var ResultFactory
     */
    private $resultRedirect;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var RedirectInterface
     */
    private $redirect;

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        ProcessCheckout $processCheckout,
        RedirectInterface $redirect,
        ResultFactory $result
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->processCheckout = $processCheckout;
        $this->redirect = $redirect;
        $this->resultRedirect = $result;
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirect->create(ResultFactory::TYPE_REDIRECT);

        try {
            $quote = $this->checkoutSession->getQuote();
            $result = $this->processCheckout->execute($quote);
            $this->resetSessionData();
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            return $resultRedirect->setPath($this->redirect->getRefererUrl());
        }

        return $resultRedirect->setUrl($result['redirect_url']);
    }

    /**
     * @return void
     */
    private function resetSessionData()
    {
        $this->checkoutSession->unsAbetaSessionId();
        $this->checkoutSession->unsAbetaReturnUrl();
    }
}
