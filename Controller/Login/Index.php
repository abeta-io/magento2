<?php declare(strict_types=1);

namespace Abeta\PunchOut\Controller\Login;

use Abeta\PunchOut\Service\Login\LoginCustomer;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\PageCache\Version;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\CookieManagerInterface;

class Index extends Action
{

    /**
     * @var LoginCustomer
     */
    private $loginCustomer;
    /**
     * @var ResultFactory
     */
    private $resultRedirect;
    /**
     * @var CookieManagerInterface
     */
    private $cookieManager;
    /**
     * @var CookieMetadataFactory
     */
    private $cookieMetadataFactory;

    public function __construct(
        Context $context,
        LoginCustomer $loginCustomer,
        ResultFactory $result,
        CookieManagerInterface $cookieManager,
        CookieMetadataFactory $cookieMetadataFactory
    ) {
        $this->loginCustomer = $loginCustomer;
        $this->resultRedirect = $result;
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        parent::__construct($context);
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirect->create(ResultFactory::TYPE_REDIRECT);

        if (!$token = $this->getRequest()->getParam('token')) {
            $this->messageManager->addErrorMessage(__('Unable to login'));
            return $resultRedirect->setPath('/');
        }

        try {
            $this->loginCustomer->execute($token);
            $this->refreshPrivateContent();
            $this->messageManager->addSuccessMessage(__('You have successfully logged in'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $this->redirectAfterResult($resultRedirect);
    }

    /**
     * Bump private_content_version cookie to force customer-data.js reload.
     * Required because this login happens via GET, bypassing Magento's
     * built-in section invalidation which only triggers on POST/PUT/DELETE.
     */
    private function refreshPrivateContent(): void
    {
        $metadata = $this->cookieMetadataFactory->createPublicCookieMetadata()
            ->setDuration(86400)
            ->setPath('/')
            ->setHttpOnly(false)
            ->setSameSite('Lax');

        $this->cookieManager->setPublicCookie(
            Version::COOKIE_NAME,
            uniqid('', true),
            $metadata
        );
    }

    /**
     * Redirect after processing the result
     *
     * @param Redirect $resultRedirect
     * @return Redirect
     */
    private function redirectAfterResult(Redirect $resultRedirect): Redirect
    {
        if ($redirectUrl = $this->loginCustomer->getRedirectUrl()) {
            return $resultRedirect->setUrl($redirectUrl);
        }

        return $resultRedirect->setPath('/');
    }
}
