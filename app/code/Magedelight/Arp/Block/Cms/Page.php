<?php
namespace Magedelight\Arp\Block\Cms;
 
class Page extends \Magento\Catalog\Block\Product\ListProduct
{   
    public $collectionFactory;
    public $arpHelper;
    public $AbstractProduct;
    public $page;


    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magedelight\Arp\Helper\Data $arpHelper,
        \Magento\Catalog\Block\Product\AbstractProduct $abstractProduct,
        \Magedelight\Arp\Model\ResourceModel\Productrule\CollectionFactory $CollectionFactory,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Cms\Model\Page $page,
        array $data = []
    ) {
        $this->registry = $context->getRegistry();
        $this->storeManager = $storeManager;
        $this->page = $page;
        $this->arpHelper = $arpHelper;
        $this->AbstractProduct = $abstractProduct;
        $this->collectionFactory = $CollectionFactory;
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper,$data );
    }
    
    public function getPageId()
    {        
        if ($this->page->getId()) {
            return $this->page->getIdentifier();
        }
    }
    
    public function getProductBlockRelated() 
    {
        $currentPage = $this->getPageId(); 
        $group = $this->arpHelper->getCustomerGroup();
        $storeId = $this->storeManager->getStore()->getId();
        $relatedProductsblock = $this->collectionFactory->create()
                ->addFieldToFilter(
                    ['store_id', 'store_id'],
                    [
                        ["finset"=>[$storeId]],
                        ["finset"=>[0]]
                    ]
                )
                ->addFieldToFilter('cms_page',array('finset'=>[$currentPage]))
                ->addFieldToFilter('customer_groups',array('finset'=>[$group]))
                ->addFieldToFilter('status', [
                'eq' => \Magedelight\Arp\Model\Productrule::STATUS_ENABLED
                ])
                ->addFieldToFilter('block_page', [
                'eq' => \Magedelight\Arp\Model\Source\BlockPage::CMS_PAGE
                ])
                ->addFieldToFilter('block_position', [
                'eq' => $this->getPosition()
                ])
                ->setOrder('priority', 'ASC')
                ->setPageSize(1);
                //echo $relatedProductsblock->getSelect()->__ToString(); exit();
            if(!empty($relatedProductsblock->getData())){
                return $relatedProductsblock->getData()[0];
            }
            return null;
    }
    
    public function getDisplayCartButton()
    {
        if(!empty($this->getProductBlockRelated())) {
            if($this->getProductBlockRelated()['display_cart_button'] == 0) {
                return $this->arpHelper->getConfig('arp_products/cmspage/'.$this->getGroupName().'/addtocartbutton');
            } else {
                return $this->getProductBlockRelated()['display_cart_button'];
            }
        }
        return false;
    }
    
    public function getMaxProductsDisplay() 
    {
        if(!empty($this->getProductBlockRelated())) {
            if($this->getProductBlockRelated()['max_products_display'] == 0) {
                if($this->getBlockLayout() == 1){
                return $this->arpHelper->getConfig('arp_products/cmspage/'.$this->getGroupName().'/displaymaxproduct_grid');
                } else {
               return $this->arpHelper->getConfig('arp_products/cmspage/'.$this->getGroupName().'/displaymaxproduct_slider');
               } 
            } else {
                 return $this->getProductBlockRelated()['max_products_display'];
            }
        }
        return false;
    }
    
    public function getNumberOfRows() 
    {
        if(!empty($this->getProductBlockRelated())) {
            if($this->getProductBlockRelated()['number_of_rows'] == 0) {
                return $this->arpHelper->getConfig('arp_products/cmspage/'.$this->getGroupName().'/noOfColunm');
            } else {
                return $this->getProductBlockRelated()['number_of_rows'];
            }
        }    
        return false;
    }
    
    public function getBlockTitle() 
    {
        if(!empty($this->getProductBlockRelated())) {
            return $this->getProductBlockRelated()['block_title'];
        }
        return false;
    }
    
    public function getBlockLayout() 
    {
        if(!empty($this->getProductBlockRelated())) {
            if($this->getProductBlockRelated()['block_layout'] == 0) {
                return $this->arpHelper->getConfig('arp_products/cmspage/'.$this->getGroupName().'/block_layout');
            } else {
                return $this->getProductBlockRelated()['block_layout'];
            }
        }
        return false;
    }
    
    public function getRelatedProductsIds() 
    {
        if(!empty($this->getProductBlockRelated())) {
            return $this->getProductBlockRelated()['products_ids_actions'];
        }
        return null;
    }
    
    public function getProductColletion() {
        $productIds = $this->getRelatedProductsIds();
        $productArray = [];
        if(!($productIds === null)) {
            $productCollection =  $this->arpHelper->getProductColletion()
                    ->addAttributeToSelect('*')
                    ->addAttributeToSelect('image')
                    ->addAttributeToSelect('url_key')
                    ->addAttributeToFilter('status', 
                            array('eq' => \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
                    )
                    ->addAttributeToFilter('visibility',
                            array('eq' => \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
                    )
                    ->addAttributeToFilter('entity_id',['in'=>$productIds])
                    ->setPageSize($this->getMaxProductsDisplay());
            return $this->getColletionAfterSort($productCollection);
        }
        return  $productArray;        
    }
    
    public function getSlidesToShow() {
        if(!empty($this->getProductBlockRelated())) {
            if($this->getProductBlockRelated()['slides_to_show'] == 0) {
                return $this->arpHelper->getConfig('arp_products/cmspage/'.$this->getGroupName().'/slidesToShow');
            } else {
                return $this->getProductBlockRelated()['slides_to_show'];
            }
        }
        return false;
    }
    
    public function getSlidesToScroll() {
        if(!empty($this->getProductBlockRelated())) {
            if($this->getProductBlockRelated()['slides_to_scroll'] == 0) {
                return $this->arpHelper->getConfig('arp_products/cmspage/'.$this->getGroupName().'/slidesToScroll');
            } else {
                return $this->getProductBlockRelated()['slides_to_scroll'];
            }
        }
        return false;        
    }
    
    public function setCustomTemplate() {
        if ($this->arpHelper->getConfig('arp_products/general/enabled_arp') && $this->getPageTopEnabled()) {
            $this->setTemplate('Magedelight_Arp::cms/page/related-items.phtml');
        }
    }
    
    public function isShowQuickView() {
        return $this->arpHelper->getConfig('arp_products/cmspage/quickview');
    }
    
    public function getPageTopEnabled() {
        return $this->arpHelper->getConfig('arp_products/cmspage/'.$this->getGroupName().'/enabled');
    }
    
    public function getBaseUrlProd() {
        return $this->storeManager->getStore()->getBaseUrl();        
    }
    
    public function getColletionAfterSort($collection) {
        if(!empty($this->getProductBlockRelated())) {
            $sortId = $this->getProductBlockRelated()['sort_by'];
            return $this->arpHelper->getColletionAfterSort($collection, $sortId);
        } else {
            return $collection;
        }
    }
    public function getRuleId() {
        if(!empty($this->getProductBlockRelated())) {
            if($this->getProductBlockRelated()['rule_id']) {
               return $this->getProductBlockRelated()['rule_id'];
            }
        }
        return false;        
    }
}