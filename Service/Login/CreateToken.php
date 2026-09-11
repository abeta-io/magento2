<?php declare(strict_types=1);

namespace Abeta\PunchOut\Service\Login;

use Abeta\PunchOut\Api\Config\RepositoryInterface as ConfigProvider;
use Abeta\PunchOut\Api\Log\RepositoryInterface as LogRepository;
use Abeta\PunchOut\Api\LoginToken\RepositoryInterface as TokenRepository;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface as CustomerRepository;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
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
        'api_key'
    ];

    private array $loginData = [];
    private LogRepository $logRepository;
    private ConfigProvider $configProvider;
    private Random $mathRandom;
    private TokenRepository $tokenRepository;
    private CustomerRepository $customerRepository;
    private CustomerInterfaceFactory $customerFactory;
    private AccountManagementInterface $accountManagement;
    private Emulation $appEmulation;
    private StoreManagerInterface $storeManager;
    private ResourceConnection $resourceConnection;

    public function __construct(
        LogRepository $logRepository,
        ConfigProvider $configProvider,
        TokenRepository $tokenRepository,
        Random $mathRandom,
        CustomerRepository $customerRepository,
        CustomerInterfaceFactory $customerFactory,
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
        $this->customerFactory = $customerFactory;
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

        $this->loginData['login_only'] = isset($this->loginData['login_only']) && $this->loginData['login_only'];
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
            ->setSessionId($this->loginData['session_id'] ?? null)
            ->setStoreId((int)$this->loginData['store_id'])
            ->setReturnUrl($this->loginData['return_url'] ?? null)
            ->setRedirectUrl($this->loginData['redirect_url'] ?? null)
            ->setEmptyCartOnLogin($this->loginData['empty_cart_on_login'])
            ->setLogoutOnPunchout($this->loginData['logout_on_punchout'])
            ->setLoginOnly((bool)$this->loginData['login_only']);

        return $this->tokenRepository->save($loginToken)->getToken();
    }

    /**
     * @return CustomerInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function getCustomer(): CustomerInterface
    {
        $masterRow = $this->findCustomerByEmail($this->loginData['username']);
        if ($masterRow === null) {
            throw new LocalizedException(__('No active customer found for %1', $this->loginData['username']));
        }

        $this->appEmulation
            ->startEnvironmentEmulation((int)$this->loginData['store_id'], Area::AREA_FRONTEND, true);

        $this->accountManagement->authenticate($this->loginData['username'], $this->loginData['password']);
        $masterCustomer = $this->customerRepository->get(
            $this->loginData['username'],
            $this->getWebsiteIdFromRow($masterRow)
        );

        $this->appEmulation->stopEnvironmentEmulation();

        $buyerEmail = $this->loginData['buyer_email'] ?? null;
        if (empty($buyerEmail)) {
            return $masterCustomer;
        }

        return $this->getOrCreateBuyerCustomer($buyerEmail, $masterCustomer);
    }

    private function getOrCreateBuyerCustomer(string $buyerEmail, CustomerInterface $masterCustomer): CustomerInterface
    {
        $storeId = (int)$this->loginData['store_id'];

        $buyerRow = $this->findCustomerByEmail($buyerEmail);
        if ($buyerRow !== null) {
            $buyer = $this->customerRepository->get($buyerEmail, $this->getWebsiteIdFromRow($buyerRow));
        } else {
            $buyer = $this->createBuyerCustomer($buyerEmail, $masterCustomer, $storeId);
        }

        $this->syncCustomerData($buyer, $masterCustomer);

        return $buyer;
    }

    private function createBuyerCustomer(
        string $email,
        CustomerInterface $masterCustomer,
        int $storeId
    ): CustomerInterface {
        $store = $this->storeManager->getStore($storeId);
        $websiteId = (int)$store->getWebsiteId();

        $customer = $this->customerFactory->create();
        $customer->setEmail($email);
        $customer->setFirstname($this->loginData['buyer_first_name'] ?? $masterCustomer->getFirstname());
        $customer->setLastname($this->loginData['buyer_last_name'] ?? $masterCustomer->getLastname());
        $customer->setStoreId($storeId);
        $customer->setWebsiteId($websiteId);
        $customer->setGroupId($masterCustomer->getGroupId());
        $customer->setTaxvat($masterCustomer->getTaxvat());

        return $this->customerRepository->save($customer);
    }

    private function syncCustomerData(CustomerInterface $buyer, CustomerInterface $masterCustomer): void
    {
        $changed = false;

        if ($buyer->getGroupId() !== $masterCustomer->getGroupId()) {
            $buyer->setGroupId($masterCustomer->getGroupId());
            $changed = true;
        }

        if ($buyer->getTaxvat() !== $masterCustomer->getTaxvat()) {
            $buyer->setTaxvat($masterCustomer->getTaxvat());
            $changed = true;
        }

        if ($changed) {
            $this->customerRepository->save($buyer);
        }
    }

    /**
     * Find an active customer by email and set its store_id on the login data.
     *
     * @param string $email
     * @return array|null Row with store_id and website_id, or null if not found
     */
    private function findCustomerByEmail(string $email): ?array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from($this->resourceConnection->getTableName('customer_entity'), ['store_id', 'website_id'])
            ->where('email = ?', $email)
            ->where('is_active = 1')
            ->limit(1);

        $row = $connection->fetchRow($select);
        if (!$row || !$row['store_id']) {
            return null;
        }

        $this->loginData['store_id'] = (int)$row['store_id'];
        return $row;
    }

    /**
     * Website ID of the found customer row, so the repository looks up the customer
     * on the correct website when account sharing is set per website.
     *
     * @param array $row
     * @return int|null
     */
    private function getWebsiteIdFromRow(array $row): ?int
    {
        return $row['website_id'] !== null ? (int)$row['website_id'] : null;
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
