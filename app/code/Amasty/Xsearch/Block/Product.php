<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Xsearch
 */


namespace Amasty\Xsearch\Block;

use Magento\Framework\DB\Select;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\Response\RedirectInterface;
use Amasty\Xsearch\Controller\RegistryConstants;

class Product extends \Magento\Catalog\Block\Product\ListProduct
{
    const XML_PATH_TEMPLATE_PRODUCT_LIMIT = 'product/limit';
    const XML_PATH_TEMPLATE_TITLE = 'product/title';
    const XML_PATH_TEMPLATE_NAME_LENGTH = 'product/name_length';
    const XML_PATH_TEMPLATE_DESC_LENGTH = 'product/desc_length';
    const XML_PATH_TEMPLATE_REVIEWS = 'product/reviews';
    const XML_PATH_TEMPLATE_ADD_TO_CART = 'product/add_to_cart';
    
    protected $_template = 'product.phtml';
    protected $_redirector;
    
    /**
     * @var \Amasty\Xsearch\Helper\Data
     */
    private $xsearchHelper;
    
    /**
     * @var \Magento\Framework\Stdlib\StringUtils
     */
    private $string;
    
    /**
     * @var \Magento\Framework\Data\Form\FormKey
     */
    private $formKey;

    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Data\Helper\PostHelper $postDataHelper,
        \Magento\Catalog\Model\Layer\Resolver $layerResolver,
        CategoryRepositoryInterface $categoryRepository,
        \Magento\Framework\Url\Helper\Data $urlHelper,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Framework\Data\Form\FormKey $formKey,
        \Amasty\Xsearch\Helper\Data $xsearchHelper,
        RedirectInterface $redirector,
        array $data = []
    ) {
        $this->_redirector = $redirector;
        $this->xsearchHelper = $xsearchHelper;
        $this->string = $string;
        $this->formKey = $formKey;

        return parent::__construct(
            $context,
            $postDataHelper,
            $layerResolver,
            $categoryRepository,
            $urlHelper,
            $data
        );
    }

    public function getFormKey()
    {
        return $this->formKey->getFormKey();
    }

    protected function _beforeToHtml()
    {
        $collection = $this->_getProductCollection();

        $collection->setOrder('relevance', Select::SQL_DESC)
            ->setPageSize($this->getLimit())
            ->setCurPage(1);

        $this->_eventManager->dispatch(
            'catalog_block_product_list_collection',
            ['collection' => $this->_getProductCollection()]
        );
        
        $this->_getProductCollection()->load();

        return parent::_beforeToHtml();
    }

    public function getLimit()
    {
        return $this->xsearchHelper->getModuleConfig(self::XML_PATH_TEMPLATE_PRODUCT_LIMIT);
    }

    protected function _getQuery()
    {
        return $this->_coreRegistry->registry(RegistryConstants::CURRENT_AMASTY_XSEARCH_QUERY);
    }

    public function getResultUrl()
    {
        $_searchHelper = \Magento\Framework\App\ObjectManager::getInstance()->create('Magento\Search\Helper\Data');

        return $_searchHelper->getResultUrl($this->_getQuery()->getQueryText());
    }

    public function highlight($text)
    {
        return $this->xsearchHelper->highlight($text, $this->_getQuery()->getQueryText());
    }

    public function getTitle()
    {
        return $this->xsearchHelper->getModuleConfig(self::XML_PATH_TEMPLATE_TITLE);
    }

    public function getName($_product)
    {
        $nameLength = $this->getNameLength();

        $_productNameStripped = $this->stripTags($_product->getName(), null, true);

        $text =
            $this->string->strlen($_productNameStripped) > $nameLength ?
            $this->string->substr($_productNameStripped, 0, $this->getNameLength()) . '...'
            : $_productNameStripped;

        return $this->highlight($text);
    }

    public function getDescription($_product)
    {
        $descLength = $this->getDescLength();

        $_productDescStripped = $this->stripTags($_product->getShortDescription(), null, true);

        $text =
            $this->string->strlen($_productDescStripped) > $descLength ?
            $this->string->substr($_productDescStripped, 0, $this->getDescLength()) . '...'
            : $_productDescStripped;

        return $this->highlight($text);
    }

    public function showDescription($_product)
    {
        return $this->string->strlen($_product->getDescription()) > 0;
    }

    public function getNameLength()
    {
        return $this->xsearchHelper->getModuleConfig(self::XML_PATH_TEMPLATE_NAME_LENGTH);
    }

    public function getDescLength()
    {
        return $this->xsearchHelper->getModuleConfig(self::XML_PATH_TEMPLATE_DESC_LENGTH);
    }

    public function getReviews()
    {
        return $this->xsearchHelper->getModuleConfig(self::XML_PATH_TEMPLATE_REVIEWS) == '1' ? 1 : 0;
    }

    public function getAddToCart()
    {
        return $this->xsearchHelper->getModuleConfig(self::XML_PATH_TEMPLATE_ADD_TO_CART) == '1'? 1 : 0;
    }

    protected function getPriceRender()
    {
        return $this->_layout->createBlock(
            'Magento\Framework\Pricing\Render',
            '',
            ['data' => ['price_render_handle' => 'catalog_product_prices']]
        );
    }

    public function getAddToCartPostParams(\Magento\Catalog\Model\Product $product)
    {
        $url = $this->getAddToCartUrl($product);
        return [
            'action' => $url,
            'data' => [
                'return_url' => $this->_redirector->getRefererUrl(),
                'product' => $product->getEntityId(),
                \Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED =>
                    $this->urlHelper->getEncodedUrl($url),
            ]
        ];
    }
}
