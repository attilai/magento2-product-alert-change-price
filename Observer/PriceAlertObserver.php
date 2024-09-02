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
    private const DEFAULT_BATCH_SIZE = 1000;

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly Publisher $publisher,
        private readonly PriceCollectionFactory $priceCollectionFactory
    ) {}

    public function execute(Observer $observer): void
    {
        $product = $observer->getEvent()->getProduct();
        $sku = $product->getSku();

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

    private function getTotalCustomerCount(array $skuList): int
    {
        $alertCollection = $this->createAlertCollectionBySkus($skuList);
        return $alertCollection->getSize();
    }

    private function loadCustomerIds(array $skuList, int $batchSize, int $offset): array
    {
        $alertCollection = $this->createAlertCollectionBySkus($skuList)
            ->setPageSize($batchSize)
            ->setCurPage($offset / $batchSize + 1);

        $customerIds = [];
        foreach ($alertCollection as $item) {
            $customerIds[] = $item->getCustomerId();
        }

        return $customerIds;
    }

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
