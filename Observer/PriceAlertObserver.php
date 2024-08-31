<?php
namespace OlehVas\ProductAlertChangePrice\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\ProductAlert\Model\Mailing\Publisher;
use Magento\ProductAlert\Model\Mailing\AlertProcessor;
use Magento\Store\Model\ScopeInterface;
use Magento\ProductAlert\Model\ResourceModel\Price\CollectionFactory as PriceCollectionFactory;

class PriceAlertObserver implements ObserverInterface
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param Publisher $publisher
     * @param PriceCollectionFactory $priceCollectionFactory
     */
    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Publisher $publisher,
        private readonly PriceCollectionFactory $priceCollectionFactory
    ) {}

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        $product = $observer->getEvent()->getProduct();
        $sku = $product->getSku();

        // Get the configured SKUs from the admin configuration
        $configuredSkus = $this->scopeConfig->getValue(
            'catalog/productalert/price_alert_skus',
            ScopeInterface::SCOPE_STORE
        );

        // Get the batch size for processing from the admin configuration
        $batchSize = (int) $this->scopeConfig->getValue(
            'catalog/productalert/batch_size',
            ScopeInterface::SCOPE_STORE
        );

        if (!$batchSize) {
            $batchSize = 10000; // Default batch size
        }

        if ($configuredSkus) {
            // Convert the SKUs list into an array and trim whitespace
            $skuList = array_map('trim', explode(',', $configuredSkus));

            // Check if the product SKU is in the configured SKU list
            if (in_array($sku, $skuList, true)) {
                $websiteId = (int) $product->getStore()->getWebsiteId();

                // Load and publish customer IDs in batches to avoid memory overload
                $offset = 0;
                do {
                    $customerIds = $this->loadCustomerIds($websiteId, $skuList, $batchSize, $offset);
                    if (!empty($customerIds)) {
                        $this->publisher->execute(AlertProcessor::ALERT_TYPE_PRICE, $customerIds, $websiteId);
                        $offset += $batchSize;
                    }
                } while (!empty($customerIds));
            }
        }
    }

    /**
     * Load alert customers with product SKU filtering in batches
     *
     * @param int $websiteId
     * @param array $configuredSkus
     * @param int $batchSize
     * @param int $offset
     * @return array
     */
    private function loadCustomerIds(int $websiteId, array $configuredSkus, int $batchSize, int $offset): array
    {
        // Create the collection for price alerts
        $alertCollection = $this->priceCollectionFactory->create();

        // Filter by website ID and select only unique customer IDs
        $alertCollection->addFieldToFilter('website_id', $websiteId)
            ->addFieldToSelect('customer_id')
            ->distinct(true)
            ->setPageSize($batchSize)
            ->setCurPage($offset / $batchSize + 1);

        // Join with the product entity table and filter by SKU
        if (!empty($configuredSkus)) {
            $alertCollection->getSelect()->join(
                ['cpe' => 'catalog_product_entity'],
                'main_table.product_id = cpe.entity_id',
                []
            )->where('cpe.sku IN (?)', $configuredSkus);
        }

        // Return unique customer IDs
        return $alertCollection->getColumnValues('customer_id');
    }
}
