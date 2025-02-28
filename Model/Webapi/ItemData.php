<?php
declare(strict_types=1);

namespace Abeta\PunchOut\Model\Webapi;

use Abeta\PunchOut\Api\Config\RepositoryInterface as ConfigRepository;
use Abeta\PunchOut\Api\Log\RepositoryInterface as LogRepository;
use Abeta\PunchOut\Api\Webapi\ItemDataInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Webapi\Rest\Request;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\QuoteFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class ItemData implements ItemDataInterface
{
    private ?array $postData = [];
    private ProductRepositoryInterface $productRepository;
    private CustomerRepositoryInterface $customerRepository;
    private StoreManagerInterface $storeManager;
    private QuoteFactory $quoteFactory;
    private ConfigRepository $configProvider;
    private Request $request;
    private LogRepository $logger;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface $storeManager,
        Request $request,
        QuoteFactory $quoteFactory,
        ConfigRepository $configProvider,
        LogRepository $logger
    ) {
        $this->productRepository = $productRepository;
        $this->customerRepository = $customerRepository;
        $this->storeManager = $storeManager;
        $this->quoteFactory = $quoteFactory;
        $this->configProvider = $configProvider;
        $this->request = $request;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function export(): array
    {
        if (!$this->configProvider->isEnabled()) {
            return [];
        }

        $this->postData = array_map('trim', $this->request->getBodyParams());
        if ($this->postData['api_key'] !== $this->configProvider->getApiKey()) {
            return [];
        }

        $store = $this->getStore();
        $product = $this->getProduct($store);
        $quote = $this->createQuote($store, $product);

        $item = $quote->getItemByProduct($product);
        return $item ? [$item->getData()] : [];

    }

    /**
     * Retrieve the customer object from the request.
     *
     * @return CustomerInterface
     * @throws LocalizedException
     */
    private function getCustomer(): CustomerInterface
    {
        return $this->validateAndGetEntity(
            'customer_id',
            fn (int $id) => $this->customerRepository->getById($id)
        );
    }

    /**
     * Retrieve the product object from the request.
     *
     * @param StoreInterface $store
     * @return ProductInterface
     * @throws LocalizedException
     */
    private function getProduct(StoreInterface $store): ProductInterface
    {
        return $this->validateAndGetEntity(
            'product_id',
            fn (int $id) => $this->productRepository->getById($id, false, $store->getId())
        );
    }

    /**
     * Retrieve the store object from the request.
     *
     * @return StoreInterface
     * @throws LocalizedException
     */
    private function getStore(): StoreInterface
    {
        return $this->validateAndGetEntity(
            'store_id',
            fn (int $id) => $this->storeManager->getStore($id)
        );
    }

    /**
     * Create a quote for the customer with the specified product and quantity.
     *
     * @param StoreInterface $store
     * @param ProductInterface $product
     * @return Quote
     * @throws LocalizedException
     */
    private function createQuote(StoreInterface $store, ProductInterface $product): Quote
    {
        $customer = $this->getCustomer();
        $qty = $this->postData['qty'] ?? 1;

        $quote = $this->quoteFactory->create()
            ->setStore($store)
            ->assignCustomer($customer);

        $this->addProduct($quote, $product, $qty);
        $quote->collectTotals();

        return $quote;
    }

    /**
     * Add a product to the quote.
     *
     * @param Quote $quote
     * @param ProductInterface $product
     * @param int $qty
     * @throws LocalizedException
     */
    private function addProduct(Quote $quote, ProductInterface $product, int $qty): void
    {
        $buyRequest = new DataObject(['qty' => $qty]);
        $result = $quote->addProduct($product, $buyRequest);

        if (is_string($result)) {
            throw new LocalizedException(__('Product could not be added: %1', $result));
        }
    }

    /**
     * Validate request data and fetch corresponding entity.
     *
     * @param string $key
     * @param callable $fetcher
     * @return mixed
     * @throws LocalizedException
     */
    private function validateAndGetEntity(string $key, callable $fetcher)
    {
        if (empty($this->postData[$key])) {
            throw new LocalizedException(__('Missing data: %1', $key));
        }

        return $fetcher((int) $this->postData[$key]);
    }
}