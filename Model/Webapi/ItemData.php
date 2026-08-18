<?php declare(strict_types=1);

namespace Abeta\PunchOut\Model\Webapi;

use Abeta\PunchOut\Api\Config\RepositoryInterface as ConfigRepository;
use Abeta\PunchOut\Api\Log\RepositoryInterface as LogRepository;
use Abeta\PunchOut\Api\Webapi\ItemDataInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\AuthorizationException;
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
            throw new LocalizedException(__('Module is not enabled'));
        }

        $this->postData = array_map(function ($value) {
            return is_string($value) ? trim($value) : $value;
        }, $this->request->getBodyParams());
        if (($this->postData['api_key'] ?? '') !== ($this->configProvider->getApiKey() ?? '')) {
            throw new AuthorizationException(__('Invalid API key'));
        }

        try {
            $store = $this->getStore();
            $products = $this->getProducts($store);
            $quote = $this->createQuote($store, $products);

            $quoteData = $quote->getData();
            $quoteData['items'] = [];
            foreach ($quote->getAllVisibleItems() as $item) {
                $quoteData['items'][] = $item->getData();
            }

            // Add available shipping rates
            $shippingAddress = $quote->getShippingAddress();
            if ($shippingAddress) {
                $quoteData['available_shipping_rates'] = [];
                foreach ($shippingAddress->getAllShippingRates() as $rate) {
                    $quoteData['available_shipping_rates'][] = [
                        'carrier_code' => $rate->getCarrier(),
                        'carrier_title' => $rate->getCarrierTitle(),
                        'method_code' => $rate->getMethod(),
                        'method_title' => $rate->getMethodTitle(),
                        'price' => $rate->getPrice(),
                        'cost' => $rate->getCost(),
                        'code' => $rate->getCode()
                    ];
                }

                // Add selected shipping method details
                $quoteData['selected_shipping_method'] = [
                    'code' => $shippingAddress->getShippingMethod(),
                    'description' => $shippingAddress->getShippingDescription(),
                    'amount' => $shippingAddress->getShippingAmount(),
                ];
            }

            return [$quoteData];
        } catch (\Exception $exception) {
            $this->logger->addErrorLog('ItemData Webapi', ['exception' => $exception->getMessage()]);
            throw new LocalizedException(__($exception->getMessage()));
        }
    }

    /**
     * Retrieve the customer object using customer ID first, with fallback to email.
     *
     * @return CustomerInterface
     * @throws LocalizedException
     */
    private function getCustomer(): CustomerInterface
    {
        $customer = $this->validateAndGetEntity(
            'customer_id',
            fn ($id) => $this->customerRepository->getById((int) $id),
            false
        );

        if (!$customer) {
            $customer = $this->validateAndGetEntity(
                'email',
                fn ($email) => $this->customerRepository->get((string) $email)
            );
        }

        return $customer;
    }

    /**
     * Retrieve products array from request data.
     * Supports both multiple products array and single product fallback.
     *
     * @param StoreInterface $store
     * @return array
     * @throws LocalizedException
     */
    private function getProducts(StoreInterface $store): array
    {
        $products = [];

        // Check if multiple products are provided
        if (!empty($this->postData['products']) && is_array($this->postData['products'])) {
            foreach ($this->postData['products'] as $productData) {
                $product = $this->getProductFromData($productData, $store);
                if ($product) {
                    $products[] = [
                        'product' => $product,
                        'qty' => $productData['qty'] ?? 1
                    ];
                }
            }
        } else {
            // Fallback to single product
            $product = $this->getProduct($store);
            $products[] = [
                'product' => $product,
                'qty' => $this->postData['qty'] ?? 1
            ];
        }

        return $products;
    }

    /**
     * Retrieve a single product from product data array.
     *
     * @param array $productData
     * @param StoreInterface $store
     * @return ProductInterface|null
     */
    private function getProductFromData(array $productData, StoreInterface $store): ?ProductInterface
    {
        // Try SKU first
        if (!empty($productData['sku'])) {
            try {
                return $this->productRepository->get((string) $productData['sku'], false, $store->getId());
            } catch (\Exception $e) {
                // Continue to try product_id
            }
        }

        // Try product ID
        if (!empty($productData['product_id'])) {
            try {
                return $this->productRepository->getById((int) $productData['product_id'], false, $store->getId());
            } catch (\Exception $e) {
                // Product not found
            }
        }

        return null;
    }

    /**
     * Retrieve the product object using SKU first, with fallback to product ID.
     *
     * @param StoreInterface $store
     * @return ProductInterface
     * @throws LocalizedException
     */
    private function getProduct(StoreInterface $store): ProductInterface
    {
        $product = $this->validateAndGetEntity(
            'sku',
            fn ($sku) => $this->productRepository->get((string) $sku, false, $store->getId()),
            false
        );

        if (!$product) {
            $product = $this->validateAndGetEntity(
                'product_id',
                fn ($id) => $this->productRepository->getById((int) $id, false, $store->getId())
            );
        }

        return $product;
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
            fn ($id) => $this->storeManager->getStore((int) $id)
        );
    }

    /**
     * Create a quote for the customer with the specified products and quantities.
     *
     * @param StoreInterface $store
     * @param ProductInterface[] $products
     * @return Quote
     * @throws LocalizedException
     */
    private function createQuote(StoreInterface $store, array $products): Quote
    {
        $customer = $this->getCustomer();

        $quote = $this->quoteFactory->create()
            ->setStore($store)
            ->assignCustomer($customer);

        foreach ($products as $productData) {
            $product = $productData['product'];
            $qty = $productData['qty'] ?? 1;
            $this->addProduct($quote, $product, (int) $qty);
        }

        // Collect totals before shipping rates to ensure accurate calculations
        $quote->collectTotals();

        // Set shipping address and collect shipping rates
        $shippingAddress = $quote->getShippingAddress();
        if ($shippingAddress) {
            $shippingAddress->setCollectShippingRates(true);
            $shippingAddress->collectShippingRates();

            // Set first available shipping method
            $rates = $shippingAddress->getAllShippingRates();
            if (!empty($rates)) {
                $firstRate = reset($rates);
                $shippingAddress->setShippingMethod($firstRate->getCode());
            }
        }

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
     * Validate request data and fetch the corresponding entity.
     *
     * @param string $key
     * @param callable $fetcher
     * @param bool $throwException
     * @return mixed|null
     * @throws LocalizedException
     */
    private function validateAndGetEntity(string $key, callable $fetcher, bool $throwException = true)
    {
        if (empty($this->postData[$key])) {
            if ($throwException) {
                throw new LocalizedException(__('Missing data: %1', $key));
            }
            return null;
        }

        try {
            return $fetcher($this->postData[$key]);
        } catch (\Exception $e) {
            if ($throwException) {
                throw new LocalizedException(__('Could not retrieve entity for key: %1', $key));
            }
            return null;
        }
    }
}
