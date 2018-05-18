<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Controller\Index;


class Index  extends \Magento\Framework\App\Action\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * Catalog session
     *
     * @var \Magento\Catalog\Model\Session
     */
    protected $catalogSession;

    /**
     * Catalog design
     *
     * @var \Magento\Catalog\Model\Design
     */
    protected $catalogDesign;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator
     */
    protected $categoryUrlPathGenerator;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * @var \Magento\Catalog\Model\Layer\Resolver
     */
    protected $layerResolver;

    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * Index constructor.
     *
     * @param Context                                                   $context
     * @param \Magento\Catalog\Model\Design                             $catalogDesign
     * @param \Magento\Catalog\Model\Session                            $catalogSession
     * @param \Magento\Framework\Registry                               $coreRegistry
     * @param \Magento\Store\Model\StoreManagerInterface                $storeManager
     * @param \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator
     * @param PageFactory                                               $resultPageFactory
     * @param \Magento\Framework\Controller\Result\ForwardFactory       $resultForwardFactory
     * @param \Magento\Catalog\Model\Layer\Resolver                     $layerResolver
     * @param CategoryRepositoryInterface                               $categoryRepository
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\Design $catalogDesign,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\CatalogUrlRewrite\Model\CategoryUrlPathGenerator $categoryUrlPathGenerator,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->catalogDesign = $catalogDesign;
        $this->catalogSession = $catalogSession;
        $this->coreRegistry = $coreRegistry;
        $this->categoryUrlPathGenerator = $categoryUrlPathGenerator;
        $this->resultPageFactory = $resultPageFactory;
        $this->resultForwardFactory = $resultForwardFactory;
        $this->layerResolver = $layerResolver;
        $this->categoryRepository = $categoryRepository;
    }


    protected function _initCategory()
    {
        $categoryId = $this->storeManager->getStore()->getRootCategoryId();
        if (!$categoryId) {
            return false;
        }

        try {
            $category = $this->categoryRepository->get($categoryId, $this->storeManager->getStore()->getId());
        } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            return false;
        }
        /*if (!$this->_objectManager->get('Magento\Catalog\Helper\Category')->canShow($category)) {
            return false;
        }*/
        $this->catalogSession->setLastVisitedCategoryId($category->getId());
        $this->coreRegistry->register('current_category', $category);
        try {
            $this->_eventManager->dispatch(
                'catalog_controller_category_init_after',
                ['category' => $category, 'controller_action' => $this]
            );
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
            return false;
        }

        return $category;
    }

    public function execute()
    {
        $category = $this->_initCategory();
        if(!$category) {
            return $this->resultForwardFactory->create()->forward('noroute');
        }

        $this->layerResolver->create(\Magento\Catalog\Model\Layer\Resolver::CATALOG_LAYER_CATEGORY);
        $settings = $this->catalogDesign->getDesignSettings($category);

        // apply custom design
        if ($settings->getCustomDesign()) {
            $this->catalogDesign->applyCustomDesign($settings->getCustomDesign());
        }

        $this->catalogSession->setLastViewedCategoryId($category->getId());

        $page = $this->resultPageFactory->create();
        // apply custom layout (page) template once the blocks are generated
        if ($settings->getPageLayout()) {
            $page->getConfig()->setPageLayout($settings->getPageLayout());
        }
        if ($category->getIsAnchor()) {
            $type = $category->hasChildren() ? 'layered' : 'layered_without_children';
        } else {
            $type = $category->hasChildren() ? 'default' : 'default_without_children';
        }

        if (!$category->hasChildren()) {
            // Two levels removed from parent.  Need to add default page type.
            $parentType = strtok($type, '_');
            $page->addPageLayoutHandles(['type' => $parentType]);
        }
        $page->addPageLayoutHandles(['type' => $type, 'id' => $category->getId()]);

        // apply custom layout update once layout is loaded
        $layoutUpdates = $settings->getLayoutUpdates();
        if ($layoutUpdates && is_array($layoutUpdates)) {
            foreach ($layoutUpdates as $layoutUpdate) {
                $page->addUpdate($layoutUpdate);
            }
        }

        $page->getConfig()->addBodyClass('page-products')
            ->addBodyClass('categorypath-' . $this->categoryUrlPathGenerator->getUrlPath($category))
            ->addBodyClass('category-' . $category->getUrlKey());
        $page->getConfig()->getTitle()->set($category->getName());

        return $page;
    }
}
