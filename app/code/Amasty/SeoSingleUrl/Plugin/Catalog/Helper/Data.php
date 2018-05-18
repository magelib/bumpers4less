<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_SeoSingleUrl
 */


namespace Amasty\SeoSingleUrl\Plugin\Catalog\Helper;

use Amasty\SeoSingleUrl\Model\Source\Breadcrumb;
use Magento\Catalog\Helper\Data as MagentoData;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;

class Data
{
    /**
     * @var \Amasty\SeoSingleUrl\Helper\Data
     */
    private $helper;

    /**
     * @var CollectionFactory
     */
    private $categoryFactoryCollection;

    public function __construct(
        \Amasty\SeoSingleUrl\Helper\Data $helper,
        CollectionFactory $categoryFactoryCollection
    ) {
        $this->helper = $helper;
        $this->categoryFactoryCollection = $categoryFactoryCollection;
    }

    public function aroundGetBreadcrumbPath(
        MagentoData $subject,
        \Closure $proceed
    ) {
        $type = $this->helper->getModuleConfig('general/breadcrumb');
        $product = $subject->getProduct();
        $result = [];

        if ($type === Breadcrumb::CURRENT_URL && $product) {
            $seoUrl = $this->helper->getSeoUrl($product, $product->getStoreId());
            $urlArray = explode('/', $seoUrl);
            array_pop($urlArray);

            $collection = $this->categoryFactoryCollection->create()
                ->setStore($product->getStoreId())
                ->addAttributeToSelect('name')
                ->addAttributeToFilter('url_key', $urlArray);

            foreach ($collection as $category) {
                $result['category' . $category->getId()] = [
                    'label' => $category->getName(),
                    'link' => $category->getUrl()
                ];
            }

            if ($subject->getProduct()) {
                $result['product'] = ['label' => $subject->getProduct()->getName()];
            }
        }

        if (!$result) {
            $result = $proceed();
        }

        return  $result;
    }
}
