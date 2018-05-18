<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\DataProvider\Import\Job\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Stdlib\ArrayManager;
use Firebear\ImportExport\Model\Source\Config;
use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Model\Category as CategoryModel;
use \Psr\Log\LoggerInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ObjectManager;

/**
 * Data provider for advanced inventory form
 */
class AdvancedImport implements ModifierInterface
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var \Firebear\ImportExport\Model\Source\Config
     */
    protected $config;

    /**
     * @var \Magento\Backend\Model\UrlInterface
     */
    protected $backendUrl;

    /**
     * @var \Magento\Framework\File\Size
     */
    protected $fileSize;

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var CategoryCollectionFactory
     */
    protected $categoryCollectionFactory;

    /**
     * @var CacheInterface
     */
    private $cacheManager;

    private $categoriesTree;


    private $logger;

    protected $request;

    const CONTAINER_PREFIX = 'container_';

    /**#@+
     * Category tree cache id
     */
    const CATEGORY_TREE_ID = 'CATALOG_PRODUCT_CATEGORY_TREE';

    /**
     * AdvancedImport constructor.
     * @param ArrayManager $arrayManager
     * @param \Magento\Backend\Model\UrlInterface $backendUrl
     * @param Config $config
     * @param \Magento\Framework\File\Size $fileSize
     */
    public function __construct(
        ArrayManager $arrayManager,
        \Magento\Backend\Model\UrlInterface $backendUrl,
        Config $config,
        \Magento\Framework\File\Size $fileSize,
        UrlInterface $urlBuilder,
        LocatorInterface $locator,
        CategoryCollectionFactory $categoryCollectionFactory,
        LoggerInterface $logger,
        RequestInterface $request
    ) {
        $this->arrayManager = $arrayManager;
        $this->config = $config;
        $this->backendUrl = $backendUrl;
        $this->fileSize = $fileSize;
        $this->urlBuilder = $urlBuilder;
        $this->locator = $locator;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->logger = $logger;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return $this->prepareMeta($meta);
    }

    /**
     * @return array
     */
    protected function addFieldSource()
    {
        $maxImageSize = $this->fileSize->getMaxFileSize();
        $childrenArray = [];
        $generalConfig = [
            'componentType' => 'field',
            'component' => 'Firebear_ImportExport/js/form/import-dep-file',
            'formElement' => 'input',
            'dataType' => 'text',
            'source' => 'import',
            'valueUpdate' => 'afterkeydown'
        ];
        $types = $this->config->get();

        foreach ($types as $typeName => $type) {
            $sortOrder = 20;
            foreach ($type['fields'] as $name => $values) {
                $localConfig = [
                    'label' => $values['label'],
                    'dataScope' => $name,
                    'sortOrder' => $sortOrder,
                    'valuesForOptions' => [
                        $typeName => $typeName
                    ]
                ];
                if (isset($values['componentType']) && ($values['componentType'])) {
                    $localConfig['componentType'] = $values['componentType'];
                }
                if (isset($values['component']) && ($values['component'])) {
                    $localConfig['component'] = $values['component'];
                }
                if (isset($values['template']) && ($values['template'])) {
                    $localConfig['template'] = $values['template'];
                }
                if (isset($values['required']) && $values['required'] == "true") {
                    $localConfig['validation'] = [
                        'required-entry' => true
                    ];
                }
                if (isset($values['validation'])) {
                    if (strpos($values['validation'], " ") !== false) {
                        $array = explode(" ", $values['validation']);
                    } else {
                        $array = [$values['validation']];
                    }
                    foreach ($array as $item) {
                        $localConfig['validation'][$item] = true;
                    }
                }
                if (isset($values['url']) && $values['url']) {
                    $localConfig['uploaderConfig'] = [
                        'url' => $this->backendUrl->getUrl($values['url'])
                    ];
                }
                if (isset($values['notice']) && $values['notice']) {
                    $localConfig['notice'] = __($values['notice']);
                }
                if ($values['componentType'] == 'fileUploader') {
                    $localConfig['maxFileSize'] = $maxImageSize;
                }
                $sortOrder += 10;
                $config = array_merge($generalConfig, $localConfig);

                $childrenArray[$typeName . "_" . $name] = [
                    'arguments' => [
                        'data' => [
                            'config' => $config
                        ],
                    ]
                ];
            }
        }

        return $childrenArray;
    }

    /**
     * @return void
     */
    private function prepareMeta()
    {
        $meta['source'] = ['children' => $this->addFieldSource()];
        $meta = $this->createNewCategoryModal($meta);
        $meta = $this->customizeCategoriesField($meta);
        return $meta;
    }


    /**
     * Create slide-out panel for new category creation
     *
     * @param array $meta
     * @return array
     */
    protected function createNewCategoryModal(array $meta)
    {
        return $this->arrayManager->set(
            'create_category_modal',
            $meta,
            [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'isTemplate' => false,
                            'componentType' => 'modal',
                            'options' => [
                                'title' => __('New Category'),
                            ],
                            'imports' => [
                                'state' => '!index=create_category:responseStatus'
                            ],
                        ],
                    ],
                ],
                'children' => [
                    'create_category' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'label' => '',
                                    'componentType' => 'container',
                                    'component' => 'Magento_Ui/js/form/components/insert-form',
                                    'dataScope' => '',
                                    'update_url' => $this->urlBuilder->getUrl('mui/index/render'),
                                    'render_url' => $this->urlBuilder->getUrl(
                                        'mui/index/render_handle',
                                        [
                                            'handle' => 'catalog_category_create',
                                            'buttons' => 1
                                        ]
                                    ),
                                    'autoRender' => false,
                                    'ns' => 'new_category_form',
                                    'externalProvider' => 'new_category_form.new_category_form_data_source',
                                    'toolbarContainer' => '${ $.parentName }',
                                    'formSubmitType' => 'ajax',
                                ],
                            ],
                        ]
                    ]
                ]
            ]
        );
    }

    /**
     * Customize Categories field
     *
     * @param array $meta
     * @return array
     */
    protected function customizeCategoriesField(array $meta)
    {
        $meta = $this->arrayManager->set(
            'source_data_map_container_category',
            $meta,
            [
                'children' => [
                    'source_data_categories_map' => [
                        'children' => [
                            'source_category_data_new' => [
                                'arguments' => [
                                    'data' => [
                                        'config' => [
                                            'formElement' => 'select',
                                            'componentType' => 'field',
                                            'component' => 'Firebear_ImportExport/js/components/new-category',
                                            'filterOptions' => true,
                                            'chipsEnabled' => true,
                                            'disableLabel' => true,
                                            'levelsVisibility' => '1',
                                            'elementTmpl' => 'ui/grid/filters/elements/ui-select',
                                            'listens' => [
                                                'index=create_category:responseData' => 'setParsed',
                                                'newOption' => 'toggleOptionSelected'
                                            ],
                                            'config' => [
                                                'dataScope' => 'source_category_data_new',
                                                'sortOrder' => 10,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'new_category_button' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'title' => __('New Category'),
                                    'formElement' => 'container',
                                    'additionalClasses' => 'admin__field-small',
                                    'componentType' => 'container',
                                    'component' => 'Magento_Ui/js/form/components/button',
                                    'template' => 'ui/form/components/button/container',
                                    'actions' => [
                                        [
                                            'targetName' => 'import_job_form.import_job_form.create_category_modal',
                                            'actionName' => 'toggleModal',
                                        ],
                                        [
                                            'targetName' =>
                                                'import_job_form.import_job_form.create_category_modal.create_category',
                                            'actionName' => 'render'
                                        ],
                                        [
                                            'targetName' =>
                                                'import_job_form.import_job_form.create_category_modal.create_category',
                                            'actionName' => 'resetForm'
                                        ]
                                    ],
                                    'additionalForGroup' => true,
                                    'provider' => false,
                                    'source' => 'product_details',
                                    'displayArea' => 'insideGroup',
                                    'sortOrder' => 20,
                                ],
                            ],
                        ]
                    ]
                ]
            ]
        );
        return $meta;
    }

    /**
     * Retrieve categories tree
     *
     * @return array
     */
    protected function getCategoriesTree($filter = null)
    {
        $categoryTree = $this->getCacheManager()->load(self::CATEGORY_TREE_ID . '_' . $filter);
        if ($categoryTree) {
            return unserialize($categoryTree);
        }
        if ($this->categoriesTree === null) {
            $storeId = $this->request->getParam('store');
            /* @var $matchingNamesCollection \Magento\Catalog\Model\ResourceModel\Category\Collection */
            $matchingNamesCollection = $this->categoryCollectionFactory->create();

            $matchingNamesCollection->addAttributeToSelect('path')
                ->addAttributeToFilter('entity_id', ['neq' => CategoryModel::TREE_ROOT_ID])
                ->setStoreId($storeId);

            $shownCategoriesIds = [];

            /** @var \Magento\Catalog\Model\Category $category */
            foreach ($matchingNamesCollection as $category) {
                foreach (explode('/', $category->getPath()) as $parentId) {
                    $shownCategoriesIds[$parentId] = 1;
                }
            }

            /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
            $collection = $this->categoryCollectionFactory->create();

            $collection->addAttributeToFilter('entity_id', ['in' => array_keys($shownCategoriesIds)])
                ->addAttributeToSelect(['name', 'is_active', 'parent_id'])
                ->setOrder('entity_id', 'ASC')
                ->setStoreId($storeId);

            $categoryPaths = [];
            foreach ($collection as $category) {
                if ($category->hasChildren()) {
                    $this->recursiveCategory($category->getChildrenCategories(), $categoryPaths);
                }
            }
        }

        $this->categoriesTree = [];
        foreach ($categoryPaths as $path) {
            $this->categoriesTree[] = ['value' => $path, 'label' => $path];
        }

        $this->getCacheManager()->save(
            serialize($this->categoriesTree),
            self::CATEGORY_TREE_ID . '_' . $filter,
            [
                \Magento\Catalog\Model\Category::CACHE_TAG,
                \Magento\Framework\App\Cache\Type\Block::CACHE_TAG
            ]
        );

        return $this->categoriesTree;
    }

    private function recursiveCategory($categoryChildren, &$categoryPaths = [])
    {
        foreach ($categoryChildren as $categoryChild) {
            $categoryPaths[] = $this->getCategoryPath($categoryChild);
        }
    }

    private function getCategoryPath($categoryChild)
    {
        $storeId = $this->request->getParam('store');

        /* @var $collection \Magento\Catalog\Model\ResourceModel\Category\Collection */
        $collection = $this->categoryCollectionFactory->create();

        $collection->addAttributeToFilter('entity_id', $categoryChild->getId())
            ->addAttributeToSelect(['name', 'is_active', 'parent_id', 'path'])
            ->setStoreId($storeId);
        $categoryFullPath = '';
        foreach ($collection as $category) {
            $categoryPath = $category->getPath();
            $explodeCategoryPath = explode('/', $categoryPath);
            foreach ($explodeCategoryPath as $categoryId) {
                if ($categoryId == 1) {
                    continue;
                }
                $collectionPathCategory = $this->categoryCollectionFactory->create();
                $collectionPathCategory->addAttributeToFilter('entity_id', $categoryId)
                    ->addAttributeToSelect(['name', 'is_active', 'parent_id', 'path'])
                    ->setStoreId($storeId);
                foreach ($collectionPathCategory as $categoryPath) {
                    if (empty($categoryFullPath)) {
                        $categoryFullPath = $categoryPath->getName();
                    } else {
                        $categoryFullPath .= '/' . $categoryPath->getName();
                    }
                }
            }
        }

        return $categoryFullPath;
    }

    /**
     * Retrieve cache interface
     *
     * @return CacheInterface
     * @deprecated
     */
    private function getCacheManager()
    {
        if (!$this->cacheManager) {
            $this->cacheManager = ObjectManager::getInstance()
                ->get(CacheInterface::class);
        }
        return $this->cacheManager;
    }
}
