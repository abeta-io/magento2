<?php declare(strict_types=1);

namespace Abeta\PunchOut\Service\Login;

use Abeta\PunchOut\Api\LoginToken\RepositoryInterface as TokenRepository;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\QuoteRepository;

/**
 * Login API Service Class
 */
class LoginCustomer
{

    /**
     * @var TokenRepository
     */
    private $tokenRepository;
    /**
     * @var Session
     */
    private $session;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;

    public function __construct(
        TokenRepository $tokenRepository,
        CheckoutSession $checkoutSession,
        QuoteRepository $quoteRepository,
        Session $session,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->tokenRepository = $tokenRepository;
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->session = $session;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(string $token): bool
    {
        $tokenData = $this->tokenRepository->getByToken($token, true);
        $customer = $this->customerRepository->getById($tokenData->getCustomerId());
        $this->session->setCustomerDataAsLoggedIn($customer);

        $this->cleanQuote();
        $this->checkoutSession->setAbetaReturnUrl($tokenData->getReturnUrl());
        $this->checkoutSession->setAbetaSessionId($tokenData->getSessionId());

        return true;
    }

    /**
     * @return void
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function cleanQuote()
    {
        $quote = $this->checkoutSession->getQuote();
        if ($quote->getItems()) {
            $quote->setIsActive(false);
            $this->quoteRepository->save($quote);
        }
    }
}
