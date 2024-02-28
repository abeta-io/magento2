<?php declare(strict_types=1);

namespace Abeta\PunchOut\Service\Cart;

use Abeta\PunchOut\Api\Config\RepositoryInterface as ConfigProvider;
use Abeta\PunchOut\Api\Log\RepositoryInterface as LogRepository;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Media\Config as CatalogProductMediaConfig;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Model\Data\AddressFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\QuoteRepository;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Export Cart API Service Class
 */
class Export
{

    private $checkoutSession;
    /**
     * @var ConfigProvider
     */
    private $configProvider;
    /**
     * @var Rate
     */
    private $shippingRates;
    /**
     * @var QuoteRepository
     */
    private $quoteRepository;
    /**
     * @var LogRepository
     */
    private $logRepository;
    /**
     * @var CatalogProductMediaConfig
     */
    private $catalogProductMediaConfig;
    /**
     * @var CollectionFactory
     */
    private $categoryCollectionFactory;
    /**
     * @var AddressRepositoryInterface
     */
    private $addressRepository;
    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var AddressFactory
     */
    private $addressFactory;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * Export constructor.
     * @param CheckoutSession $checkoutSession
     * @param Rate $shippingRates
     * @param QuoteRepository $quoteRepository
     * @param ProductRepositoryInterface $productRepository
     * @param CatalogProductMediaConfig $catalogProductMediaConfig
     * @param CollectionFactory $categoryCollectionFactory
     * @param ConfigProvider $configProvider
     * @param LogRepository $logRepository
     * @param AddressRepositoryInterface $addressRepository
     * @param AddressFactory $addressFactory
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        Rate $shippingRates,
        QuoteRepository $quoteRepository,
        ProductRepositoryInterface $productRepository,
        CatalogProductMediaConfig $catalogProductMediaConfig,
        CollectionFactory $categoryCollectionFactory,
        ConfigProvider $configProvider,
        LogRepository $logRepository,
        AddressRepositoryInterface $addressRepository,
        AddressFactory $addressFactory,
        StoreManagerInterface $storeManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->quoteRepository = $quoteRepository;
        $this->productRepository = $productRepository;
        $this->shippingRates = $shippingRates;
        $this->configProvider = $configProvider;
        $this->catalogProductMediaConfig = $catalogProductMediaConfig;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->logRepository = $logRepository;
        $this->addressRepository = $addressRepository;
        $this->addressFactory = $addressFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @param Quote $cart
     * @param bool $summary
     * @return array
     * @throws LocalizedException
     */
    public function execute(Quote $cart, bool $summary = true): array
    {
        $this->appendDefaultShippingMethod($cart);
        $cartData = [
            'general' => $this->getGeneralData(),
            'header' => $this->getHeaderData($cart),
            'products' => $this->getProductData($cart),
        ];

        return $summary ? $this->extractSummary($cartData) : $cartData;
    }

    /**
     * @param Quote $cart
     * @return void
     * @throws LocalizedException
     */
    private function appendDefaultShippingMethod(Quote $cart): void
    {
        try {
            if ($methodCode = $this->configProvider->getShippingMethod()) {
                $this->shippingRates->setCode($methodCode)->getPrice();

                $shippingAddress = $cart->getShippingAddress();
                if (!$shippingAddress->getCountryId()) {
                    $shippingAddress->importCustomerAddressData($this->getShippingAddress($cart));
                }
                $shippingAddress->setCollectShippingRates(true)
                    ->collectShippingRates()
                    ->setShippingMethod($methodCode);

                $cart->getShippingAddress()->addShippingRate($this->shippingRates);
                $this->quoteRepository->save($cart);
            }
        } catch (\Exception $exception) {
            $this->logRepository->addErrorLog('Append Shipping Error', $exception->getMessage());
            throw new LocalizedException(
                __('Unable to add shipping method to the cart: %1', $exception->getMessage())
            );
        }
    }

    /**
     * @param Quote $cart
     * @return AddressInterface
     * @throws LocalizedException
     */
    private function getShippingAddress(Quote $cart): AddressInterface
    {
        try {
            return $this->addressRepository->getById($cart->getCustomer()->getDefaultShipping());
        } catch (\Exception $e) {
            /** @var AddressInterface $address */
            $address = $this->addressFactory->create();
            $storeInformation = $this->configProvider->getStoreInformation(
                (int)$this->storeManager->getStore()->getId()
            );

            if ($storeInformation['country_id']) {
                $address->setCountryId($storeInformation['country_id']);
            }

            if ($storeInformation['postcode']) {
                $address->setPostcode($storeInformation['postcode']);
            }

            return $address;
        }
    }

    /**
     * @return array
     * @throws LocalizedException
     */
    private function getGeneralData(): array
    {
        if (!$this->checkoutSession->getAbetaSessionId()) {
            $this->logRepository->addErrorLog('Export Cart', 'No Abeta Session ID found.');
            throw new LocalizedException(__('Unable to export cart, session data missing'));
        }

        if (!$this->checkoutSession->getAbetaReturnUrl()) {
            $this->logRepository->addErrorLog('Export Cart', 'No Abeta Return Url found.');
            throw new LocalizedException(__('Unable to export cart, session data missing'));
        }

        return [
            'api_version' => $this->configProvider->getApiVersion(),
            'session_id' => $this->checkoutSession->getAbetaSessionId(),
            'return_url' => $this->checkoutSession->getAbetaReturnUrl()
        ];
    }

    /**
     * @param Quote $cart
     * @return array
     */
    private function getHeaderData(Quote $cart): array
    {
        return [
            'total_price_ex_vat' => $cart->getShippingAddress()->getGrandTotal()
                - $cart->getShippingAddress()->getTaxAmount(),
            'total_price_inc_vat' => $cart->getShippingAddress()->getGrandTotal(),
            'currency' => $cart->getBaseCurrencyCode(),
            'order_reference' => '',
            'cart_id' => $cart->getId()
        ];
    }

    /**
     * @param Quote $cart
     * @return array
     */
    private function getProductData(Quote $cart): array
    {
        $products = [];
        $simples = [];

        /** @var QuoteItem $cartItem */
        foreach ($cart->getAllItems() as $cartItem) {

            if ($cartItem->getParentItemId()) {
                $simples[$cartItem->getParentItemId()][] = $this->getSimpleArray($cartItem);
                continue;
            }

            $products[$cartItem->getItemId()] = [
                'standard' => [
                    'type' => 'product',
                    'product_type' => $cartItem->getProductType(),
                    'item_id' => $cartItem->getItemId(),
                    'sku' => $cartItem->getSku(),
                    'title' => $cartItem->getName(),
                    'qty' => $cartItem->getQty(),
                    'price_incl_vat' => $this->getProductInclTax($cartItem),
                    'price_ex_vat' => $this->getProductExclTax($cartItem),
                    'vat_percentage' => $cartItem->getTaxPercent(),
                    'line_total_ex_vat' => $this->getProductExclTax($cartItem) * $cartItem->getQty(),
                    'original_price_ex_vat' => $cartItem->getPrice(),
                    'image_url' => $this->getProductImageData($cartItem),
                    'children' => [],
                ],
                'cart_item' => $cartItem->getData(),
                'product_data' => $this->getProductDataArray($cartItem),
                'categories' => $this->getCategoriesName($cartItem)
            ];
        }

        foreach ($simples as $itemId => $data) {
            $products[$itemId]['standard']['children'] = $data;
        }

        $products[] = $this->getShippingData($cart);

        return $products;
    }

    /**
     * @param QuoteItem $cartItem
     * @return array
     */
    private function getSimpleArray(QuoteItem $cartItem): array
    {
        return [
            'type' => 'product',
            'product_type' => $cartItem->getProductType(),
            'item_id' => $cartItem->getItemId(),
            'sku' => $cartItem->getSku(),
            'title' => $cartItem->getName(),
            'qty' => $cartItem->getQty(),
        ];
    }

    /**
     * @param Quote\Item $cartItem
     * @return float
     */
    private function getProductInclTax(Quote\Item $cartItem): float
    {
        return (float)($this->getProductExclTax($cartItem) * (($cartItem->getTaxPercent() / 100) + 1));
    }

    /**
     * @param Quote\Item $cartItem
     * @return float
     */
    private function getProductExclTax(Quote\Item $cartItem): float
    {
        return (float)$cartItem->getPrice()
            - $cartItem->getDiscountAmount()
            + $cartItem->getDiscountTaxCompensationAmount();
    }

    /**
     * @param Quote\Item $cartItem
     * @return string
     */
    private function getProductImageData(Quote\Item $cartItem): string
    {
        if ($img = $cartItem->getProduct()->getData('small_image')) {
            return $this->catalogProductMediaConfig->getMediaUrl($img);
        }

        return '';
    }

    /**
     * @param Quote\Item $cartItem
     * @return array
     */
    private function getProductDataArray(Quote\Item $cartItem): array
    {
        try {
            return $this->productRepository->getById($cartItem->getProduct()->getId())->getData();
        } catch (\Exception $exception) {
            return $cartItem->getProduct()->getData();
        }
    }

    /**
     * @param Quote\Item $cartItem
     * @return array
     */
    public function getCategoriesName(Quote\Item $cartItem): array
    {
        $categoryIds = $cartItem->getProduct()->getCategoryIds();
        if (empty($categoryIds)) {
            return [];
        }

        $categories = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', $categoryIds);

        $categoryNames = [];
        foreach ($categories as $category) {
            $categoryNames[] = $category->getName();
        }

        return $categoryNames;
    }

    /**
     * @param Quote $cart
     * @return array
     */
    private function getShippingData(Quote $cart): array
    {
        return [
            'standard' => [
                'type' => 'shipping',
                'sku' => 'shipping',
                'title' => $cart->getShippingAddress()->getShippingDescription(),
                'qty' => 1,
                'price_incl_vat' => $this->getShippingInclTax($cart),
                'price_ex_vat' => $this->getShippingExclTax($cart),
                'vat_percentage' => $this->getTaxRateShipping($cart),
                'line_total_ex_vat' => $this->getShippingExclTax($cart),
                'original_price_ex_vat' => $cart->getShippingAddress()->getShippingAmount(),
                'image_url' => ''
            ]
        ];
    }

    /**
     * @param Quote $cart
     * @return float
     */
    private function getShippingInclTax(Quote $cart): float
    {
        return (float)($this->getShippingExclTax($cart) * (($this->getTaxRateShipping($cart) / 100) + 1));
    }

    /**
     * @param Quote $cart
     * @return float
     */
    private function getShippingExclTax(Quote $cart): float
    {
        return ($cart->getShippingAddress()->getShippingAmount()
            - $cart->getShippingAddress()->getShippingDiscountAmount()
            + $cart->getShippingAddress()->getShippingDiscountTaxCompensationAmount());
    }

    /**
     * @param Quote $cart
     * @return float
     */
    public function getTaxRateShipping(Quote $cart): float
    {
        $shippingInclTax = $cart->getShippingAddress()->getShippingInclTax();
        $shippingAmount = $cart->getShippingAddress()->getShippingAmount();

        if ($shippingInclTax > 0 && $shippingAmount > 0) {

            return (($shippingInclTax / $shippingAmount) - 1) * 100;
        }

        return 0;
    }

    /**
     * @param array $cartData
     * @return array
     */
    private function extractSummary(array $cartData): array
    {
        $summaryData = [];
        if (empty($cartData['products'])) {
            return [];
        }

        foreach ($cartData['products'] as $product) {
            $summaryData[] = [
                'name' => $product['standard']['title'] ?? '',
                'qty' => $product['standard']['qty'] ?? 0
            ];
        }

        return $summaryData;
    }
}
