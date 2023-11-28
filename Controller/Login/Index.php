<?php declare(strict_types=1);

namespace Abeta\PunchOut\Controller\Login;

use Abeta\PunchOut\Service\Login\LoginCustomer;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;

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

    public function __construct(
        Context $context,
        LoginCustomer $loginCustomer,
        ResultFactory $result
    ) {
        $this->loginCustomer = $loginCustomer;
        $this->resultRedirect = $result;
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
            $this->messageManager->addSuccessMessage(__('You have successfully logged in'));
        } catch (\Exception $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $resultRedirect->setPath('/');
    }
}
