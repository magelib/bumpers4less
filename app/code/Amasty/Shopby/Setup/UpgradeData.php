<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shopby
 */


namespace Amasty\Shopby\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Amasty\ShopbyBrand\Helper\Data as BrandHelper;
use Amasty\Shopby\Model\ResourceModel\FilterSetting\CollectionFactory;
use Amasty\Shopby\Api\Data\FilterSettingInterface;
use Magento\Catalog\Setup\CategorySetupFactory;

/**
 * Class UpgradeData
 * @package Amasty\Shopby\Setup
 */
class UpgradeData implements UpgradeDataInterface
{
    /**
     * @var \Amasty\Base\Helper\Deploy
     */
    private $deployHelper;

    /**
     * @var BrandHelper
     */
    private $brandHelper;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var CollectionFactory
     */
    private $filterCollectionFactory;

    /**
     * @var CategorySetupFactory
     */
    private $categorySetupFactory;

    /**
     * UpgradeData constructor.
     * @param \Amasty\Base\Helper\Deploy $deployHelper
     * @param BrandHelper $settingHelper
     * @param ScopeConfigInterface $scopeConfig
     * @param CollectionFactory $filterCollectionFactory
     * @param \Magento\Framework\App\State $state
     * @param CategorySetupFactory $categorySetupFactory
     */
    public function __construct(
        \Amasty\Base\Helper\Deploy $deployHelper,
        BrandHelper $settingHelper,
        ScopeConfigInterface $scopeConfig,
        CollectionFactory $filterCollectionFactory,
        \Magento\Framework\App\State $state,
        CategorySetupFactory $categorySetupFactory
    ) {
        try {
            $state->setAreaCode('adminhtml');
        } catch (\Exception $e) {
            //Area code already set
        }
        $this->deployHelper = $deployHelper;
        $this->brandHelper = $settingHelper;
        $this->scopeConfig = $scopeConfig;
        $this->filterCollectionFactory = $filterCollectionFactory;
        $this->categorySetupFactory = $categorySetupFactory;
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     *
     * @return void
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        if (version_compare($context->getVersion(), '1.6.3', '<')) {
            $this->deployPub();
        }

        if (version_compare($context->getVersion(), '1.9.0', '<')) {
            $this->brandHelper->updateBrandOptions();
        }

        if (version_compare($context->getVersion(), '1.14.13', '<')) {
            $this->setShowBrandIcon();
        }

        if (version_compare($context->getVersion(), '2.1.5', '<')) {
            $this->addCategoryThumbnail($setup);
        }
    }

    protected function deployPub()
    {
        $p = strrpos(__DIR__, DIRECTORY_SEPARATOR);
        $modulePath = $p ? substr(__DIR__, 0, $p) : __DIR__;
        $modulePath .= '/pub';
        $this->deployHelper->deployFolder($modulePath);
    }

    private function setShowBrandIcon()
    {
        if ($this->scopeConfig->isSetFlag('amshopby_brand/general/product_icon')) {
            $attributeCode = $this->scopeConfig->getValue('amshopby_brand/general/attribute_code');
            $filterCode = \Amasty\Shopby\Helper\FilterSetting::ATTR_PREFIX . $attributeCode;
            $filterCollection = $this->filterCollectionFactory->create();
            /** @var \Amasty\Shopby\Model\FilterSetting $filter */
            $filter = $filterCollection
                ->addFieldToFilter(FilterSettingInterface::FILTER_CODE, $filterCode)
                ->getFirstItem();
            if ($filter && $filter->getId()) {
                $filter->setData(FilterSettingInterface::SHOW_ICONS_ON_PRODUCT, true);
                $filter->getResource()->save($filter);
            }
        }
    }

    /**
     * @param ModuleDataSetupInterface $setup
     * @return $this|ModuleDataSetupInterface
     */
    private function addCategoryThumbnail(ModuleDataSetupInterface $setup)
    {
        $categorySetup = $this->categorySetupFactory->create(['setup' => $setup]);
        $entityTypeId = $categorySetup->getEntityTypeId(\Magento\Catalog\Model\Category::ENTITY);
        $attributeSetId = $categorySetup->getDefaultAttributeSetId($entityTypeId);
        if ($categorySetup->getAttribute('catalog_category', 'thumbnail')) {
            return $setup;
        }
        $categorySetup->addAttribute(\Magento\Catalog\Model\Category::ENTITY, 'thumbnail', [
            'type' => 'varchar',
            'label' => 'Thumbnail',
            'input' => 'image',
            'backend' => \Magento\Catalog\Model\Category\Attribute\Backend\Image::class,
            'required' => false,
            'sort_order' => 5,
            'global' => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
            'group' => 'General Information',
        ]);

        $idGroup = $categorySetup->getAttributeGroupId($entityTypeId, $attributeSetId, 'General Information');
        $categorySetup->addAttributeToGroup(
            $entityTypeId,
            $attributeSetId,
            $idGroup,
            'thumbnail',
            45
        );
        return $this;
    }
}
