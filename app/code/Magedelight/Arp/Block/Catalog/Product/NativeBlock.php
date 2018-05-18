<?php
namespace Magedelight\Arp\Block\Catalog\Product;
 
class NativeBlock extends \Magento\Catalog\Block\Product\ProductList\Related
{   
    const NativeEnabled = 'arp_products/productpage/native/enabled';
    const slidesToShow = 'arp_products/productpage/native/slidesToShow';
    const slidesToScroll = 'arp_products/productpage/native/slidesToScroll';
    
    public $collectionFactory;
    public $arpHelper;
    public $AbstractProduct;
    public $productFactory;
    public $urlHelper;
    public $priceHelper;


    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Checkout\Model\ResourceModel\Cart $checkoutCart,
        \Magento\Catalog\Model\Product\Visibility $catalogProductVisibility,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magedelight\Arp\Helper\Data $arpHelper,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\Pricing\Helper\Data $priceHelper,
        \Magento\Catalog\Block\Product\AbstractProduct $abstractProduct,
        \Magedelight\Arp\Model\ResourceModel\Productrule\CollectionFactory $CollectionFactory,
        \Magento\Store\Model\StoreManager $storeManager,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        array $data = []
    ) {
        $this->registry = $context->getRegistry();
        $this->storeManager = $storeManager;
        $this->arpHelper = $arpHelper;
        $this->urlHelper = $urlHelper;
        $this->priceHelper = $priceHelper;
        $this->AbstractProduct = $abstractProduct;
        $this->collectionFactory = $CollectionFactory;
        $this->productFactory = $productFactory;
        parent::__construct($context, $checkoutCart, $catalogProductVisibility, $checkoutSession, $moduleManager, $data);
    }
    
    public function getCurrentProduct()
    {        
        return $this->registry->registry('current_product');
    }
    public function getProductBlockRelated() {
        $currentProduct = $this->getCurrentProduct()->getId();
        $storeId = $this->storeManager->getStore()->getId();
        $group = $this->arpHelper->getCustomerGroup();
        $relatedProductsblock = $this->collectionFactory->create()
                ->addFieldToFilter(
                    ['store_id', 'store_id'],
                    [
                        ["finset"=>[$storeId]],
                        ["finset"=>[0]]
                    ]
                )
                ->addFieldToFilter('products_ids_conditions',array('finset'=>[$currentProduct]))
                ->addFieldToFilter('customer_groups',array('finset'=>[$group]))
                ->addFieldToFilter('status', [
                'eq' => \Magedelight\Arp\Model\Productrule::STATUS_ENABLED
                ])
                ->addFieldToFilter('block_page', [
                'eq' => \Magedelight\Arp\Model\Source\BlockPage::PRODUCT_PAGE
                ])
                ->addFieldToFilter('block_position', [
                'eq' => \Magedelight\Arp\Model\Source\BlockPositionProduct::InsteadOfNative
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
        if(!empty($this->getProductBlockRelated())) {
            if($this->getProductBlockRelated()['display_cart_button'] == 0) {
                return $this->arpHelper->getConfig('arp_products/productpage/native/addtocartbutton');
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
                return $this->arpHelper->getConfig('arp_products/productpage/native/displaymaxproduct_grid');
                } else {
               return $this->arpHelper->getConfig('arp_products/productpage/native/displaymaxproduct_slider');
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
                return $this->arpHelper->getConfig('arp_products/productpage/native/noOfColunm');
            } else {
                return $this->getProductBlockRelated()['number_of_rows'];
            }
        }    
        return false;
    }
    
    public function getBlockTitle() 
    {
        if(!empty($this->getProductBlockRelated())){
            return $this->getProductBlockRelated()['block_title'];
        }
        return false;
    }
    
    public function getBlockLayout() 
    {
        if(!empty($this->getProductBlockRelated())) {
            if($this->getProductBlockRelated()['block_layout'] == 0) {
                return $this->arpHelper->getConfig('arp_products/productpage/native/block_layout');
            } else {
                return $this->getProductBlockRelated()['block_layout'];
            }
        }
        return false;
    }
    
    public function getRelatedProductsIds() 
    {
        if(!empty($this->getProductBlockRelated())){
            return $this->getProductBlockRelated()['products_ids_actions'];
        }
        return null;
    }
    
    public function getProductColletion() {
        $productIds = $this->getRelatedProductsIds();
        $productArray = [];
        if(!($productIds === null)){
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
    
    public function setCustomTemplate() {
        $productoverride = $this->productFactory->create()->load($this->getCurrentProduct()->getId())->getData('arp_override');
        $blockDataCount = count($this->getProductColletion());
        if($blockDataCount > 0 && $productoverride == 0 && $this->canShowIf()) {
            $this->setTemplate('Magedelight_Arp::catalog/product/related-items.phtml');
            $this->setType('product-native');
        }
    }
    
    public function getPageTopEnabled() {
        return $this->arpHelper->getConfig(self::NativeEnabled);
    }
    
    public function getSlidesToShow() {
        if(!empty($this->getProductBlockRelated())) {
            if($this->getProductBlockRelated()['slides_to_show'] == 0) {
                return $this->arpHelper->getConfig('arp_products/productpage/native/slidesToShow');
            } else {
                return $this->getProductBlockRelated()['slides_to_show'];
            }
        }
        return false;
    }
    
    public function getSlidesToScroll() {
        if(!empty($this->getProductBlockRelated())) {
            if($this->getProductBlockRelated()['slides_to_scroll'] == 0) {
                return $this->arpHelper->getConfig('arp_products/productpage/native/slidesToScroll');
            } else {
                return $this->getProductBlockRelated()['slides_to_scroll'];
            }
        }
        return false;
    }
    
    public function canShowIf() {
        if ($this->arpHelper->getConfig('arp_products/general/enabled_arp') && $this->getPageTopEnabled()) {
            return true;
        }
        return false;
    }
    public function isShowQuickView() {
        return $this->arpHelper->getConfig('arp_products/productpage/quickview');
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
    
    /**
     * Get post parameters
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return string
     */
    public function getAddToCartPostParams(\Magento\Catalog\Model\Product $product)
    {
        $url = $this->getAddToCartUrl($product);
        return [
            'action' => $url,
            'data' => [
                'product' => $product->getEntityId(),
                \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED =>
                    $this->urlHelper->getEncodedUrl($url),
            ]
        ];
    }
    
    public function getProductPriceHtmlCust($price) {
        return $this->priceHelper->currency($price, true, false);
    }
}