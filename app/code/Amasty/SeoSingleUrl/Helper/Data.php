<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_SeoSingleUrl
 */


namespace Amasty\SeoSingleUrl\Helper;

use Amasty\SeoSingleUrl\Model\Source\By;
use Amasty\SeoSingleUrl\Model\Source\Type;
use Magento\Store\Model\ScopeInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const MODULE_PATH = 'amasty_seourl/';

    protected $categoryData = null;

    /**
     * @var \Magento\Framework\Json\EncoderInterface
     */
    private $jsonEncoder;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    private $categoryCollectionFactory;

    /**
     * @var \Amasty\SeoSingleUrl\Model\UrlRewrite\Storage
     */
    private $urlFinder;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Amasty\SeoSingleUrl\Model\UrlRewrite\Storage $urlFinder
    ) {
        parent::__construct($context);
        $this->jsonEncoder = $jsonEncoder;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->urlFinder = $urlFinder;
    }

    public function getModuleConfig($path)
    {
        return $this->scopeConfig->getValue(self::MODULE_PATH . $path, ScopeInterface::SCOPE_STORE);
    }

    public function getSeoUrl($product, $storeId)
    {
        $requestPath = $this->generateSeoUrl($product->getId(), $storeId);
        if ($requestPath) {
            $product->setRequestPath($requestPath);
        }

        return $requestPath;
    }

    public function generateSeoUrl($productId, $storeId)
    {
        $requestPath = '';
        $filterData = [
            UrlRewrite::ENTITY_ID => $productId,
            UrlRewrite::ENTITY_TYPE => \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator::ENTITY_TYPE,
            UrlRewrite::STORE_ID => $storeId,
        ];
        $rewrites = $this->urlFinder->findAllByDataWithoutCategory($filterData);

        $ulrVariants = [];
        $simplePath = '';
        foreach ($rewrites as $rewrite) {
            $path = $rewrite->getRequestPath();
            if (!$simplePath) {
                $simplePath = $path;
            }

            $path = ltrim($path, '/');
            $path = $this->replaceExcludedCategories($path, $storeId);
            if (strpos($path, '/') === false) {
                continue;
            }

            $ulrVariants[] = $path;
        }

        if ($ulrVariants) {
            $requestPath = $this->getVariantBySetting($ulrVariants);
        }

        if (!$requestPath) {
            $requestPath = $simplePath;
        }

        return $requestPath;
    }

    private function getCategoryData($storeId)
    {
        if ($this->categoryData === null) {
            $this->categoryData = [];
            $collection = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect('url_key')
                ->addFieldToFilter('entity_id', ['in' => $this->getExcludedCategoryIds()])
                ->setStoreId($storeId);
            foreach ($collection as $category) {
                if ($category->getUrlKey()) {
                    $this->categoryData[] = $category->getUrlKey();
                }
            }
        }

        return $this->categoryData;
    }

    private function replaceExcludedCategories($path, $storeId)
    {
        $categoryUrls = $this->getCategoryData($storeId);
        if ($categoryUrls) {
            $pathArray = explode('/', $path);
            foreach ($categoryUrls as $categoryUrl) {
                $key = array_search($categoryUrl, $pathArray);
                if ($key !== false) {
                    $path = '';
                    break;
                }
            }
        }

        return $path;
    }

    private function getExcludedCategoryIds()
    {
        $ids = $this->getModuleConfig('general/exclude');
        $ids = str_replace(' ', '', $ids);
        $ids = explode(',', $ids);

        return $ids;
    }

    private function getVariantBySetting($urlVariants)
    {
        $type = $this->getModuleConfig('general/product_url_type');
        $by = $this->getModuleConfig('general/by');
        if ($by == By::CHARACTER_NUMBER) {
            usort($urlVariants, function ($first, $second) {
                $positionFirst = strlen($first);
                $positionSecond = strlen($second);
                return $positionFirst - $positionSecond;
            });
        }

        if ($type == Type::SHORTEST) {
            $result = $urlVariants[0];
        } else {
            $result = end($urlVariants);
        }

        return $result;
    }
}
