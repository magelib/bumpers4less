<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


namespace Amasty\ShopbyBrand\Block\Widget;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Catalog\Model\Product\Attribute\Repository;
use Magento\Framework\View\Element\Template\Context;
use Amasty\Shopby\Helper\OptionSetting as OptionSettingHelper;
use Magento\Catalog\Model\ResourceModel\Layer\Filter\Attribute as FilterAttributeResource;
use Magento\Store\Model\ScopeInterface;

class BrandList extends \Magento\Framework\View\Element\Template implements \Magento\Widget\Block\BlockInterface
{
    /**
     * @var  array
     */
    protected $items;

    /**
     * @var  Repository
     */
    private $repository;

    /**
     * @var OptionSettingHelper
     */
    private $optionSettingHelper;

    /** @var array|null  */
    private $productCount = null;

    /**
     * @var FilterAttributeResource
     */
    private $filterAttributeResource;

    /**
     * @var \Magento\CatalogInventory\Helper\Stock
     */
    private $stockHelper;

    /**
     * @var \Magento\Catalog\Model\Product\Visibility
     */
    protected $catalogProductVisibility;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var \Magento\CatalogSearch\Model\Layer\Category\ItemCollectionProvider
     */
    private $collectionProvider;

    public function __construct(
        Context $context,
        Repository $repository,
        OptionSettingHelper $optionSetting,
        FilterAttributeResource $filterAttributeResource,
        \Magento\CatalogInventory\Helper\Stock $stockHelper,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\CatalogSearch\Model\Layer\Category\ItemCollectionProvider $collectionProvider,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->repository = $repository;
        $this->optionSettingHelper = $optionSetting;
        $this->filterAttributeResource = $filterAttributeResource;
        $this->stockHelper = $stockHelper;
        $this->catalogProductVisibility = $catalogProductVisibility;
        $this->categoryRepository = $categoryRepository;
        $this->collectionProvider = $collectionProvider;
    }

    public function getIndex()
    {
        $items = $this->getItems();
        if (!$items) {
            return [];
        }

        $this->sortItems($items);

        $letters = $this->items2letters($items);

        $columnCount = abs((int)$this->getData('columns'));
        if (!$columnCount) {
            $columnCount = 1;
        }
        $itemsPerColumn = ceil((sizeof($items) + sizeof($letters)) / max(1, $columnCount));

        $col = 0; // current column
        $num = 0; // current number of items in column
        $index = [];
        foreach ($letters as $letter => $items) {
            $index[$col][$letter] = $items['items'];
            $num += $items['count'];
            $num++;
            if ($num >= $itemsPerColumn) {
                $num = 0;
                $col++;
            }
        }

        return $index;
    }

    public function getItems()
    {
        if ($this->items === null) {
            $this->items = [];
            $attributeCode = $this->_scopeConfig->getValue(
                'amshopby_brand/general/attribute_code',
                ScopeInterface::SCOPE_STORE
            );
            if ($attributeCode == '') {
                return $this->items;
            }

            $options = $this->repository->get($attributeCode)->getOptions();
            array_shift($options);

            $items = [];
            foreach ($options as $option) {
                $filterCode = \Amasty\Shopby\Helper\FilterSetting::ATTR_PREFIX . $attributeCode;
                $setting = $this->optionSettingHelper->getSettingByValue(
                    $option->getValue(),
                    $filterCode,
                    $this->_storeManager->getStore()->getId()
                );

                $items[] = [
                    'label' => $setting->getLabel(),
                    'url' => $setting->getUrlPath(),
                    'img' => $setting->getImageUrl(),
                    'cnt' => $this->_getOptionProductCount($setting->getValue())
                ];
            }
            $displayZero = $this->_scopeConfig->getValue(
                'amshopby_brand/brands_landing/display_zero',
                ScopeInterface::SCOPE_STORE
            );
            if (!$displayZero) {
                $items = array_filter($items, [$this, "_removeEmptyBrands"]);
            }

            $this->items = $items;
        }

        return $this->items;
    }

    protected function _removeEmptyBrands($var)
    {
        return $var['cnt'] > 0;
    }

    protected function sortItems(array &$items)
    {
        usort($items, function ($a, $b) {
            $a['label'] = trim($a['label']);
            $b['label'] = trim($b['label']);

            $x = substr($a['label'], 0, 1);
            $y = substr($b['label'], 0, 1);
            if (is_numeric($x) && !is_numeric($y)) {
                return 1;
            }
            if (!is_numeric($x) && is_numeric($y)) {
                return -1;
            }

            return strcmp(strtoupper($a['label']), strtoupper($b['label']));
        });
    }

    protected function items2letters($items)
    {
        $letters = [];
        foreach ($items as $item) {
            if (function_exists('mb_strtoupper')) {
                $i = mb_strtoupper(mb_substr($item['label'], 0, 1, 'UTF-8'));
            } else {
                $i = strtoupper(substr($item['label'], 0, 1));
            }

            if (is_numeric($i)) {
                $i = '#';
            }

            if (!isset($letters[$i]['items'])) {
                $letters[$i]['items'] = [];
            }

            $letters[$i]['items'][] = $item;

            if (!isset($letters[$i]['count'])) {
                $letters[$i]['count'] = 0;
            }

            $letters[$i]['count']++;
        }

        return $letters;
    }

    /**
     * @return array
     */
    public function getAllLetters()
    {
        $brandLetters = [];
        foreach ($this->getIndex() as $letters) {
            $brandLetters = array_merge($brandLetters, array_keys($letters));
        }
        return $brandLetters;
    }

    public function getSearchHtml()
    {
        if (!$this->getData('show_search') || !$this->getItems()) {
            return '';
        }
        $searchCollection = [];
        foreach ($this->getItems() as $item) {
            $searchCollection[$item['url']] = $item['label'];
        }
        $searchCollection = json_encode($searchCollection);
        /** @var \Magento\Framework\View\Element\Template $block */
        $block = $this->getLayout()->createBlock(\Magento\Framework\View\Element\Template::class, 'ambrands.search')
            ->setTemplate('Amasty_ShopbyBrand::brand_search.phtml')
            ->setBrands($searchCollection);
        return $block->toHtml();
    }

    /**
     * Get brand product count
     *
     * @param $optionId
     * @return int
     */
    protected function _getOptionProductCount($optionId)
    {
        if ($this->productCount === null) {
            $rootCategoryId = $this->_storeManager->getStore()->getRootCategoryId();
            $category = $this->categoryRepository->get($rootCategoryId);
            /** @var \Amasty\Shopby\Model\ResourceModel\Fulltext\Collection */
            $collection = $this->collectionProvider->getCollection($category);
            $attrCode = $this->_scopeConfig->getValue(
                'amshopby_brand/general/attribute_code',
                ScopeInterface::SCOPE_STORE
            );

            $this->productCount = $collection
                ->addAttributeToFilter($attrCode, ['nin' => 1])
                ->setVisibility([2,4])
                ->getFacetedData($attrCode);
        }

        return isset($this->productCount[$optionId]) ? $this->productCount[$optionId]['count'] : 0;
    }

    /**
     * Apply options from config
     * @return $this
     */
    protected function _beforeToHtml()
    {
        $configValues = $this->_scopeConfig->getValue('amshopby_brand/brands_landing', ScopeInterface::SCOPE_STORE);
        foreach ($configValues as $option => $value) {
            if ($this->getData($option) === null) {
                $this->setData($option, $value);
            }
        }
        return parent::_beforeToHtml();
    }
}
