<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


namespace Amasty\ShopbyBrand\Helper;

use Amasty\Shopby\Api\CategoryDataSetterInterface;
use Amasty\Shopby\Api\Data\OptionSettingInterface;
use Amasty\Shopby\Helper\OptionSetting;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Layer;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManager;
use Amasty\Shopby\Model\Category\Manager as CategoryManager;

class Content extends AbstractHelper implements CategoryDataSetterInterface
{
    /**
     * @var  Layer\Resolver
     */
    private $layerResolver;

    /**
     * @var  OptionSetting
     */
    private $optionHelper;

    /**
     * @var  StoreManager
     */
    private $storeManager;

    /** Shopby\Api\Data\OptionSettingInterface|bool */
    private $currentBranding = false;

    public function __construct(
        Context $context,
        Layer\Resolver $layerResolver,
        OptionSetting $optionHelper,
        StoreManager $storeManager
    ) {
        parent::__construct($context);
        $this->layerResolver = $layerResolver;
        $this->optionHelper = $optionHelper;
        $this->storeManager = $storeManager;
    }

    /**
     * Set Category data from current Brand.
     * @param Category $category
     * @return $this;
     */
    public function setCategoryData(Category $category)
    {
        $brand = $this->getCurrentBranding();
        if ($brand) {
            $this->populateCategoryWithBrand($category, $brand);
        }
        return $this;
    }

    /**
     * Get current Brand.
     * @return null|OptionSettingInterface
     */
    public function getCurrentBranding()
    {
        if ($this->currentBranding === false) {
            if ($this->_getRequest()->getControllerName() !== 'index') {
                return $this->currentBranding = null;
            }

            $attributeCode =
                $this->scopeConfig->getValue('amshopby_brand/general/attribute_code', ScopeInterface::SCOPE_STORE);
            if ($attributeCode == '') {
                return $this->currentBranding = null;
            }

            $value = $this->_request->getParam($attributeCode);

            if (!isset($value)) {
                return $this->currentBranding = null;
            }

            $layer = $this->layerResolver->get();
            $isRootCategory = $layer->getCurrentCategory()->getId() == $layer->getCurrentStore()->getRootCategoryId();
            if (!$isRootCategory) {
                return $this->currentBranding = null;
            }

            $this->currentBranding = $this->optionHelper->getSettingByValue(
                $value,
                \Amasty\Shopby\Helper\FilterSetting::ATTR_PREFIX . $attributeCode,
                $this->storeManager->getStore()->getId()
            );
        }

        return $this->currentBranding;
    }

    /**
     * Populate category with Brand data.
     * @param Category $category
     * @param OptionSettingInterface $brand
     * @return $this
     */
    protected function populateCategoryWithBrand(Category $category, OptionSettingInterface $brand)
    {
        $category->setName($brand->getTitle());
        $category->setData('description', $brand->getDescription());
        $category->setData('landing_page', $brand->getTopCmsBlockId());
        if ($brand->getTopCmsBlockId()) {
            $category->setData(CategoryManager::CATEGORY_FORCE_MIXED_MODE, 1);
        }
        $category->setData(CategoryManager::CATEGORY_SHOPBY_IMAGE_URL, $brand->getImageUrl());

        $category->setData('meta_title', $brand->getMetaTitle());
        $category->setData('meta_description', $brand->getMetaDescription());
        $category->setData('meta_keywords', $brand->getMetaKeywords());
        $category->setData(CategoryDataSetterInterface::APPLIED_BRAND_VALUE, $brand->getValue());
        return $this;
    }
}
