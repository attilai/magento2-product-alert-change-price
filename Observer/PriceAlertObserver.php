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
     * Customer list default batch size
     */
    private const DEFAULT_BATCH_SIZE = 1000;

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

        if ($configuredSkus) {
            $skuList = array_map('trim', explode(',', $configuredSkus));

            if (in_array($sku, $skuList, true)) {
                $websiteId = (int) $product->getStore()->getWebsiteId();

                $totalCustomers = $this->getTotalCustomerCount($skuList);
                $batchSize = self::DEFAULT_BATCH_SIZE;
                $batchCount = ceil($totalCustomers / $batchSize);

                for ($i = 0; $i < $batchCount; $i++) {
                    $customerIds = $this->loadCustomerIds($skuList, $batchSize, $i * $batchSize);
                    if (!empty($customerIds)) {
                        $this->publisher->execute(AlertProcessor::ALERT_TYPE_PRICE, $customerIds, $websiteId);
                    }
                }
            }
        }
    }

    /**
     * @param array $skuList
     * @return int
     */
    private function getTotalCustomerCount(array $skuList): int
    {
        $alertCollection = $this->createAlertCollectionBySkus($skuList);
        return $alertCollection->getSize();
    }

    /**
     * Load alert customers with product SKU filtering in batches
     *
     * @param array $skuList
     * @param int $batchSize
     * @param int $offset
     * @return array
     */
    private function loadCustomerIds(array $skuList, int $batchSize, int $offset): array
    {
        $alertCollection = $this->createAlertCollectionBySkus($skuList)
            ->setPageSize($batchSize)
            ->setCurPage($offset / $batchSize + 1);

        return $alertCollection->getColumnValues('customer_id');
    }

    /**
     * @param array $skuList
     * @return \Magento\ProductAlert\Model\ResourceModel\Price\Collection
     */
    private function createAlertCollectionBySkus(array $skuList)
    {
        $alertCollection = $this->priceCollectionFactory->create();

        $alertCollection->addFieldToSelect('customer_id')
            ->distinct(true);

        if (!empty($skuList)) {
            $alertCollection->getSelect()->join(
                ['cpe' => 'catalog_product_entity'],
                'main_table.product_id = cpe.entity_id',
                []
            )->where('cpe.sku IN (?)', $skuList);
        }

        return $alertCollection;
    }
}
