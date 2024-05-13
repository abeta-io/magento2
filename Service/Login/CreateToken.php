<?php declare(strict_types=1);

namespace Abeta\PunchOut\Service\Login;

use Abeta\PunchOut\Api\Config\RepositoryInterface as ConfigProvider;
use Abeta\PunchOut\Api\Log\RepositoryInterface as LogRepository;
use Abeta\PunchOut\Api\LoginToken\RepositoryInterface as TokenRepository;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Math\Random;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Login API Service Class
 */
class CreateToken
{

    private const REQUIRED_FIELDS = [
        'username',
        'password',
        'session_id',
        'api_key',
        'return_url'
    ];

    /**
     * @var array
     */
    private $loginData = [];
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var Random
     */
    private $mathRandom;
    /**
     * @var TokenRepository
     */
    private $tokenRepository;
    /**
     * @var CustomerRepository
     */
    private $customerRepository;
    /**
     * @var AccountManagementInterface
     */
    private $accountManagement;
    /**
     * @var Emulation
     */
    private $appEmulation;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    public function __construct(
        LogRepository $logRepository,
        ConfigProvider $configProvider,
        TokenRepository $tokenRepository,
        Random $mathRandom,
        CustomerRepository $customerRepository,
        AccountManagementInterface $accountManagement,
        Emulation $appEmulation,
        StoreManagerInterface $storeManager,
        ResourceConnection $resourceConnection
    ) {
        $this->logRepository = $logRepository;
        $this->configProvider = $configProvider;
        $this->tokenRepository = $tokenRepository;
        $this->mathRandom = $mathRandom;
        $this->customerRepository = $customerRepository;
        $this->accountManagement = $accountManagement;
        $this->appEmulation = $appEmulation;
        $this->storeManager = $storeManager;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @throws LocalizedException
     */
    public function execute(array $postData): array
    {
        $this->validatePostData($postData);
        $customer = $this->getCustomer();

        $token = $this->createLoginToken($customer);

        return ['success' => true, 'one_time_url' => $this->buildLoginUrl($token)];
    }

    /**
     * @param $postData
     * @return void
     * @throws LocalizedException
     */
    private function validatePostData($postData): void
    {
        $this->logRepository->addDebugLog("ValidatePostData - Login", $postData);

        foreach (self::REQUIRED_FIELDS as $requiredField) {
            if (empty($postData[$requiredField])) {
                throw new LocalizedException(__('%1 not set or empty', $requiredField));
            }
        }

        $this->loginData = array_map('trim', $postData);

        if ($this->loginData['api_key'] != $this->configProvider->getApiKey()) {
            throw new LocalizedException(__('Invalid API-key'));
        }

        foreach (['empty_cart_on_login', 'logout_on_punchout'] as $key) {
            $this->loginData[$key] = !isset($this->loginData[$key]) || (bool)$this->loginData[$key];
        }
    }

    /**
     * @param CustomerInterface $customer
     * @return string
     * @throws LocalizedException
     */
    private function createLoginToken(CustomerInterface $customer): string
    {
        $loginToken = $this->tokenRepository->create();
        $loginToken->setCustomerId((int)$customer->getId())
            ->setToken($this->mathRandom->getUniqueHash('AB'))
            ->setSessionId($this->loginData['session_id'])
            ->setStoreId((int)$this->loginData['store_id'])
            ->setReturnUrl($this->loginData['return_url'])
            ->setEmptyCartOnLogin($this->loginData['empty_cart_on_login'])
            ->setLogoutOnPunchout($this->loginData['logout_on_punchout']);

        return $this->tokenRepository->save($loginToken)->getToken();
    }

    /**
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomer(): CustomerInterface
    {
        if (!$this->findCustomerByEmail()) {
            throw new LocalizedException(__('No active customer found for %1', $this->loginData['username']));
        }

        $this->appEmulation
            ->startEnvironmentEmulation((int)$this->loginData['store_id'], Area::AREA_FRONTEND, true);

        $this->accountManagement->authenticate($this->loginData['username'], $this->loginData['password']);
        $customer = $this->customerRepository->get($this->loginData['username']);

        $this->appEmulation->stopEnvironmentEmulation();

        return $customer;
    }

    /**
     * @return bool
     */
    private function findCustomerByEmail(): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('customer_entity'), 'store_id')
            ->where('email = ?', $this->loginData['username'])
            ->where('is_active = 1')
            ->limit(1);

        if ($storeId = $connection->fetchOne($select)) {
            $this->loginData['store_id'] = (int)$storeId;
            return true;
        }

        return false;
    }

    /**
     * @param string $token
     * @return string
     * @throws NoSuchEntityException
     */
    private function buildLoginUrl(string $token): string
    {
        return $this->storeManager->getStore((int)$this->loginData['store_id'])
            ->getUrl('abeta/login', ['token' => $token, '_current' => true]);
    }
}
