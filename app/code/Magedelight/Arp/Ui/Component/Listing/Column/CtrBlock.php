<?php 

namespace Magedelight\Arp\Ui\Component\Listing\Column;

use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class CtrBlock extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * Column name
     */
    const ctr_block = 'ctr_block';

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Framework\Locale\CurrencyInterface $localeCurrency
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if(isset($dataSource['data']['items'])){
            $fieldName = self::ctr_block;
            foreach ($dataSource['data']['items'] as & $item) {
                if($item['page_visitor'] == 0) {
                    $item[$fieldName] = '0%';
                } else {
                    $ctr = $item['clicks']/$item['page_visitor'] * 100;
                    $item[$fieldName] = round($ctr, 2).'%';
                }
            }
        }
        return $dataSource;
    }
}