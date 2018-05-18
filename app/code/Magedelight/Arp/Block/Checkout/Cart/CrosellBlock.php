<?php
namespace Magedelight\Arp\Block\Checkout\Cart;
 
class CrosellBlock extends \Magento\Catalog\Block\Product\ListProduct
{   
    public $collectionFactory;
    public $arpHelper;
    public $AbstractProduct;
    public $cartModel;

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
        \Magento\Checkout\Model\Cart $cartModel,
        array $data = []
    ) {
        $this->registry = $context->getRegistry();;
        $this->storeManager = $storeManager;
        $this->arpHelper = $arpHelper;
        $this->AbstractProduct = $abstractProduct;
        $this->collectionFactory = $CollectionFactory;
        $this->cartModel = $cartModel;
        parent::__construct($context, $postDataHelper, $layerResolver, $categoryRepository, $urlHelper,$data );
    }
    public function getProductBlockCrossell() {
        $quoteItems = $this->cartModel->getQuote()->getAllVisibleItems();
        $group = $this->arpHelper->getCustomerGroup();
        $fieldName = [];
        $fieldValues = [];
        if($quoteItems) {
            foreach($quoteItems as $item) {
                $fieldName[] = 'products_ids_conditions';
                $fieldValues[] = ["finset"=>[$item->getProductId()]];        
            }
        }else{
                $fieldName[] = 'products_ids_conditions';
                $fieldValues[] = ["eq" => '']; 
        }
        $storeId = $this->storeManager->getStore()->getId();
        
        $relatedProductsblock = $this->collectionFactory->create()
                ->addFieldToFilter(
                    ['store_id', 'store_id'],
                    [
                        ["finset"=>[$storeId]],
                        ["finset"=>[0]]
                    ]
                )
                ->addFieldToFilter($fieldName,$fieldValues)
                ->addFieldToFilter('customer_groups',array('finset'=>[$group]))
                ->addFieldToFilter('status', [
                'eq' => \Magedelight\Arp\Model\Productrule::STATUS_ENABLED
                ])
                ->addFieldToFilter('block_position', [
                'eq' => $this->getPosition()
                ])
                ->addFieldToFilter('block_page', [
                'eq' => \Magedelight\Arp\Model\Source\BlockPage::SHOPPING_CART
                ])
                ->setOrder('priority', 'ASC')
                ->setPageSize(1);
        
            if(!empty($relatedProductsblock->getData())){
                return $relatedProductsblock->getData()[0];
            }
            return null;        
    }
    
    public function getDisplayCartButton()
    {   
        if(!empty($this->getProductBlockCrossell())) {
            if($this->getProductBlockCrossell()['display_cart_button'] == 0) {
                return $this->arpHelper->getConfig('arp_products/shoppingcart/'.$this->getGroupName().'/addtocartbutton');
            } else {
                return $this->getProductBlockCrossell()['display_cart_button'];
            }
        }
        return false;
    }
    
    public function getMaxProductsDisplay() 
    {
        if(!empty($this->getProductBlockCrossell())) {
            if($this->getProductBlockCrossell()['max_products_display'] == 0) {
                if($this->getBlockLayout() == 1){
                return $this->arpHelper->getConfig('arp_products/shoppingcart/'.$this->getGroupName().'/displaymaxproduct_grid');
                } else {
               return $this->arpHelper->getConfig('arp_products/shoppingcart/'.$this->getGroupName().'/displaymaxproduct_slider');
               } 
            } else {
                 return $this->getProductBlockCrossell()['max_products_display'];
            }
        }
        return false;
    }
    
    public function getNumberOfRows() 
    {
        if(!empty($this->getProductBlockCrossell())) {
            if($this->getProductBlockCrossell()['number_of_rows'] == 0) {
                return $this->arpHelper->getConfig('arp_products/shoppingcart/'.$this->getGroupName().'/noOfColunm');
            } else {
                return $this->getProductBlockCrossell()['number_of_rows'];
            }
        }    
        return false;
    }
    
    public function getBlockTitle() 
    {
        if(!empty($this->getProductBlockCrossell())){
            return $this->getProductBlockCrossell()['block_title'];
        }
        return false;
    }
    
    public function getBlockLayout() 
    {
        if(!empty($this->getProductBlockCrossell())) {
            if($this->getProductBlockCrossell()['block_layout'] == 0) {
                return $this->arpHelper->getConfig('arp_products/shoppingcart/'.$this->getGroupName().'/block_layout');
            } else {
                return $this->getProductBlockCrossell()['block_layout'];
            }
        }
        return false;
    }
    
    public function getRelatedProductsIds() 
    {
        if(!empty($this->getProductBlockCrossell())){
            return $this->getProductBlockCrossell()['products_ids_actions'];
        }
        return null;
    }
    public function getProductColletion() {
        $productIds = $this->getRelatedProductsIds();
        $productArray = [];
        if(!($productIds === null)) {
            $productCollection =  $this->arpHelper->getProductColletion()
                    ->addAttributeToSelect('*')
                    ->addAttributeToSelect(
                    'required_options'
                    )
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
    
    public function getPageTopEnabled() {
        return $this->arpHelper->getConfig('arp_products/shoppingcart/'.$this->getGroupName().'/enabled');
    }
    
    public function getSlidesToShow() {
        if(!empty($this->getProductBlockCrossell())) {
            if($this->getProductBlockCrossell()['slides_to_show'] == 0) {
                return $this->arpHelper->getConfig('arp_products/shoppingcart/'.$this->getGroupName().'/slidesToShow');
            } else {
                return $this->getProductBlockCrossell()['slides_to_show'];
            }
        }
        return false;
    }
    
    public function getSlidesToScroll() {
        if(!empty($this->getProductBlockCrossell())) {
            if($this->getProductBlockCrossell()['slides_to_scroll'] == 0) {
                return $this->arpHelper->getConfig('arp_products/shoppingcart/'.$this->getGroupName().'/slidesToScroll');
            } else {
                return $this->getProductBlockCrossell()['slides_to_scroll'];
            }
        }
        return false;
    }
    
    public function setCustomTemplate() {
        if ($this->arpHelper->getConfig('arp_products/general/enabled_arp') && $this->getPageTopEnabled()) {
            $this->setTemplate('Magedelight_Arp::checkout/cart/crossell-items.phtml');
        }
    }
    public function isShowQuickView() {
        return $this->arpHelper->getConfig('arp_products/shoppingcart/quickview');
    }
    
    public function getBaseUrlProd() {
        return $this->storeManager->getStore()->getBaseUrl();;        
    }
    
    public function getColletionAfterSort($collection) {
        if(!empty($this->getProductBlockCrossell())) {
            $sortId = $this->getProductBlockCrossell()['sort_by'];
            return $this->arpHelper->getColletionAfterSort($collection, $sortId);
        } else {
            return $collection;
        }
    }
    public function getRuleId() {
        if(!empty($this->getProductBlockCrossell())) {
            if($this->getProductBlockCrossell()['rule_id']) {
               return $this->getProductBlockCrossell()['rule_id'];
            }
        }
        return false;        
    }
    
}