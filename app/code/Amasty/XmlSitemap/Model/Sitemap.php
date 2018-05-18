<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */


namespace Amasty\XmlSitemap\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\UrlInterface as XmlUrlInterface;

class Sitemap extends AbstractModel
{
    /** @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $_productCollectionFactory */
    protected $_productCollectionFactory;

    /** @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $_categoryCollectionFactory */
    protected $_categoryCollectionFactory;

    /** @var \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $_pageCollectionFactory */
    protected $_pageCollectionFactory;

    /**
     * @var \Magento\Sitemap\Model\ResourceModel\Catalog\ProductFactory
     */
    protected $_productFactory;

    /** @var \Magento\Framework\Filesystem\Io\File */
    protected $_ioFile;

    /** @var \Magento\Framework\Filesystem\DirectoryList $_dir */
    protected $_dir;

    /** @var \Magento\Framework\Stdlib\DateTime\DateTime $_dateTime */
    protected $_dateTime;

    /** @var  \Magento\Store\Model\StoreManagerInterface $_storeManager */
    protected $_storeManager;

    /** @var \Magento\Framework\Filesystem\Directory\Write $_directory */
    protected $_directory;

    /** @var \Magento\Catalog\Model\Product\Attribute\Source\Status $_productStatus */
    protected $_productStatus;

    /** @var \Magento\Catalog\Model\Product\Visibility $_productVisibility */
    protected $_productVisibility;

    /** @var \Magento\Catalog\Model\Product\Media\Config $_mediaConfig */
    protected $_mediaConfig;

    /** @var \Magento\Framework\Module\Manager $_moduleManager */
    protected $_moduleManager;

    /** @var \Magento\Framework\App\Config\ScopeConfigInterface $_scopeConfig */
    protected $_scopeConfig;

    /** @var \Magento\Eav\Model\Config $_eavConfig */
    protected $_eavConfig;

    /** @var \Magento\Framework\ObjectManagerInterface $_objectManager */
    protected $_objectManager;

    /** @var \Magento\Framework\Message\ManagerInterface $_messageManager */
    protected $_messageManager;

    protected $_path;
    protected $_file;
    protected $_date;
    protected $_xml = array();
    protected $_baseUrl;
    protected $_storeId;
    protected $_iterator = 1;
    protected $_excludeUrls;
    protected $_isFirst = true;
    protected $_firstTime;
    protected $_filePath;

    /**
     * Sitemap constructor.
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Filesystem\Io\File $ioFile
     * @param \Magento\Framework\Filesystem\DirectoryList $dir
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $dateTime
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus
     * @param Visibility $productVisibility
     * @param \Magento\Catalog\Model\Product\Media\Config $mediaConfig
     * @param \Magento\Framework\Module\Manager $moduleManager
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory
     * @param \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pageCollectionFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Filesystem\Io\File $ioFile,
        \Magento\Framework\Filesystem\DirectoryList $dir,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Catalog\Model\Product\Attribute\Source\Status $productStatus,
        \Magento\Catalog\Model\Product\Visibility $productVisibility,
        \Magento\Catalog\Model\Product\Media\Config $mediaConfig,
        \Magento\Framework\Module\Manager $moduleManager,
        \Magento\Sitemap\Model\ResourceModel\Catalog\ProductFactory $productFactory,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        \Magento\Cms\Model\ResourceModel\Page\CollectionFactory $pageCollectionFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,

        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,

        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
        $this->_productFactory = $productFactory;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        $this->_pageCollectionFactory = $pageCollectionFactory;
        $this->_ioFile = $ioFile;
        $this->_dir = $dir;
        $this->_dateTime = $dateTime;
        $this->_storeManager = $storeManager;
        $this->_directory = $filesystem->getDirectoryWrite(DirectoryList::ROOT);
        $this->_productStatus = $productStatus;
        $this->_productVisibility = $productVisibility;
        $this->_mediaConfig = $mediaConfig;
        $this->_moduleManager = $moduleManager;
        $this->_scopeConfig = $scopeConfig;
        $this->_eavConfig = $eavConfig;
        $this->_objectManager = $objectManager;
        $this->_messageManager = $messageManager;
    }

    protected function _construct()
    {
        $this->_init('Amasty\XmlSitemap\Model\ResourceModel\Sitemap');
    }

    public function run()
    {
        $this->generateXml();
        $this->_removeOldFiles();
    }

    public function generateXml()
    {
        $this->_storeId = $this->getStoreId();
        $this->_baseUrl = $this->_storeManager->getStore($this->_storeId)->getBaseUrl(XmlUrlInterface::URL_TYPE_LINK);
        $this->_generateProducts();
        $this->_generateCategories();
        $this->_generateCms();
        $this->_generateExtra();

        if ($this->_moduleManager->isEnabled('Amasty_Xlanding')) {
            $this->_generateLanding();
        }

        if ($this->_moduleManager->isEnabled('Amasty_Blog')) {
            $this->_generateBlog();
        }

        if ($this->_moduleManager->isEnabled('Amasty_ShopbyBrand')) {
            $this->_generateBrands();
        }

        if ($this->_moduleManager->isEnabled('Amasty_ShopbyPage')) {
            $this->_generateNavigation();
        }

        $pieces = array();
        $this->_iterator = 0;
        $isChunk = $this->getMaxItems() > 0 && count($this->_xml) > $this->getMaxItems();
        if ($isChunk) {
            $split = array_chunk($this->_xml, $this->getMaxItems(), false);
            foreach ($split as $chunk) {
                $pieces = array_merge($pieces, $this->_writePortion($chunk, false));
            }
        } else {
            $pieces = $this->_writePortion($this->_xml, true);
            $this->_renameFirstFile($pieces);
        }

        $this->_writeIndexFile($pieces);

        $this->setLastRun($this->_dateTime->gmtDate());
        $this->save();

        return $this;
    }

    public function parsePlaceholder($product)
    {
        $txt = $this->getProductsCaptionsTemplate();
        if ($txt == '') {
            return $txt;
        }

        $vars = array();
        preg_match_all('/{([a-zA-Z:\_0-9]+)}/', $txt, $vars);
        if (!$vars[1]) {
            return $txt;
        }
        $vars = $vars[1];

        foreach ($vars as $var) {
            $value = '';
            switch ($var) {
                case 'product_name':
                    $value = $product->getName();
                    break;
            }
            $txt = str_replace('{' . $var . '}', $value, $txt);
        }

        return $txt;
    }

    public function beforeSave()
    {
        if (!preg_match('#\.xml$#', $this->getFolderName())) {
            if (substr($this->getFolderName(), strlen($this->getFolderName()) - 1) == "/") {
                $this->setFolderName(substr($this->getFolderName(), 0, strlen($this->getFolderName()) - 1));
            }
            $this->setFolderName($this->getFolderName() . '.xml');
        }

        $realPath = $this->_ioFile->getCleanPath($this->getPath());

        if (!$this->_ioFile->allowedPath($realPath, $this->_dir->getRoot())) {
            $this->_messageManager->addErrorMessage(__('Please define correct path'));
        } elseif (!$this->_ioFile->fileExists($realPath, false)) {
            $this->_messageManager->addErrorMessage(__('Please create the specified folder ' . $this->getPreparedFilename() . ' before saving the sitemap.'));
        } elseif (!$this->_ioFile->isWriteable($realPath)) {
            $this->_messageManager->addErrorMessage(__('Please make sure that ' . $this->getPreparedFilename() . ' is writable by web-server.'));
        }

        return parent::beforeSave();
    }

    /**
     * Return full file name with path
     *
     * @return string
     */
    public function getPreparedFilename()
    {
        return $this->getPath() . $this->getSitemapFilename();
    }

    /**
     * Return real file path
     *
     * @return string
     */
    protected function getPath()
    {
        if (is_null($this->_filePath)) {
            $dirname = pathinfo($this->getFolderName());
            if ($dirname['dirname'] == '.') {
                $this->_filePath = str_replace('//', '/', $this->_dir->getRoot() . '/');
            } else {
                $this->_filePath = str_replace('//', '/', $this->_dir->getRoot() . '/' . $dirname['dirname'] . '/');
            }
        }

        return $this->_filePath;
    }

    protected function _removeOldFiles()
    {
        $realPath = $this->_ioFile->getCleanPath($this->_getPath());
        $fileName = $this->_getSitemapFilename();
        $pos = strpos($fileName, ".xml");
        $noExtensionFileName = substr($fileName, 0, $pos);
        $fullFilePath = $realPath . $noExtensionFileName . "_*";
        $files = glob($fullFilePath);
        foreach ($files as $file) {
            if (filemtime($file) < $this->_firstTime) {
                unlink($file);
            }
        }
    }

    protected function _generateProducts()
    {
        if (!$this->getProducts()) {
            return;
        }

        $changefreq = $this->getProductsFrequency();
        $priority = $this->getProductsPriority();
        /** @var \Magento\Sitemap\Model\ResourceModel\Catalog\ProductFactory $productFactory */

        $productCollection = $this->_productFactory->create()->getCollection($this->getStoreId());

        /** @var \Magento\Catalog\Model\Product $product */
        foreach ($productCollection as $product) {
            //load url rewrite
            $productUrl = $this->_getUrl($product->getUrl());
            if ($this->_isUrlToExclude($productUrl)) {
                continue;
            }
            $xmlLine = '<url><loc>%s</loc><priority>%.2f</priority><changefreq>%s</changefreq>';
            $xmlParams = array(
                htmlspecialchars($productUrl),
                $priority,
                $changefreq
            );

            if ($this->getProductsThumbs()) {
                $images = $product->getImages();
                if ($images) {
                    foreach ($images->getCollection() as $imageItem) {
                        $image = $imageItem->getUrl();
                        $xmlLine .= '<image:image>';
                        if ($this->getProductsCaptions()) {
                            $title = $images->getTitle();
                            if ($title == '') {
                                $title = $this->parsePlaceholder($product);
                            }
                            $title = htmlspecialchars($title);
                            if (!empty($title)) {
                                $xmlLine .= '<image:title>%s</image:title>';
                                $xmlParams[] = $title;
                            }
                        }
                        $xmlLine .= '<image:loc>%s</image:loc></image:image>';
                        $xmlParams[] = htmlspecialchars($image);
                    }
                }
            }
            if ($this->getProductsModified()) {
                $xmlLine .= '<lastmod>%s</lastmod>';
                $updateTime = strtotime($product->getUpdatedAt());
                $xmlParams[] = date('c', $updateTime);
            }
            $xmlLine .= '</url>';
            $this->_xml[] = vsprintf($xmlLine, $xmlParams);
        }
    }

    protected function _generateCategories()
    {
        if (!$this->getCategories()) {
            return;
        }

        $changefreq = $this->getCategoriesFrequency();
        $priority = $this->getCategoriesPriority();
        $currentStore = $this->_storeManager->getStore()->getId();
        $this->_storeManager->setCurrentStore($this->_storeId);
        /** @var \Magento\Catalog\Model\ResourceModel\Category\Collection $collection */
        $collection = $this->_categoryCollectionFactory->create();
        $collection->addIsActiveFilter();
        $collection->addUrlRewriteToResult();
        $collection->addAttributeToSelect('image');

        $collection->load();

        /** @var \Magento\Catalog\Model\Category $item */
        foreach ($collection as $item) {
            if (empty($item['request_path'])) {
                continue;
            }

            if ($this->_isUrlToExclude($item['url'])) {
                continue;
            }

            $item['url'] = $this->_baseUrl . $item['request_path'];

            $xmlLine = '<url><loc>%s</loc><priority>%.2f</priority><changefreq>%s</changefreq>';
            $xmlParams = array(
                htmlspecialchars($item['url']),
                $priority,
                $changefreq
            );

            if ($this->getCategoriesThumbs() && $item->getImage()) {
                $xmlLine .= '<image:image>';
                if ($this->getCategoriesCaptions()) {
                    $xmlLine .= '<image:title>%s</image:title>';
                    $xmlParams[] = $item->getName();
                }
                $xmlLine .= '<image:loc>%s</image:loc></image:image>';
                $thumb = $item->getImageUrl();
                $xmlParams[] = htmlspecialchars($thumb);
            }

            if ($this->getCategoriesModified()) {
                $xmlLine .= '<lastmod>%s</lastmod>';
                $updateTime = strtotime($item->getUpdatedAt());
                $xmlParams[] = date('c', $updateTime);
            }

            $xmlLine .= '</url>';
            $this->_xml[] = vsprintf($xmlLine, $xmlParams);

        }

        $this->_storeManager->setCurrentStore($currentStore);

        unset($collection);
    }

    protected function _generateCms()
    {
        if (!$this->getPages()) {
            return;
        }

        $changefreq = $this->getPagesFrequency();
        $priority = $this->getPagesPriority();
        /** @var \Magento\Cms\Model\ResourceModel\Page\Collection $collection */
        $collection = $this->_pageCollectionFactory->create();
        $collection->addStoreFilter($this->_storeId);
        $collection->getSelect()->where('is_active = 1');

        /** @var \Magento\Cms\Model\Page $item */
        foreach ($collection as $item) {
            $pageUrl = $this->_baseUrl . $item->getIdentifier();
            if (($this->getExcludeCmsAliases() != '' && strpos($this->getExcludeCmsAliases(), $item->getIdentifier()) !== false)
                || $this->_isUrlToExclude($pageUrl)
            ) {
                continue;
            }

            $xmlLine = '<url><loc>%s</loc><priority>%.2f</priority><changefreq>%s</changefreq>';
            $xmlParams = array(
                htmlspecialchars($pageUrl),
                $priority,
                $changefreq
            );

            if ($this->getPagesModified()) {
                $xmlLine .= '<lastmod>%s</lastmod>';
                $updateTime = strtotime($item->getUpdateTime());
                $xmlParams[] = date('c', $updateTime);
            }
            $xmlLine .= '</url>';
            $this->_xml[] = vsprintf($xmlLine, $xmlParams);

        }
        unset($collection);
    }

    protected function _generateExtra()
    {

        if (!$this->getExtra()) {
            return;
        }

        $collection = explode(chr(13), $this->getExtraLinks());

        $changefreq = $this->getExtraFrequency();
        $priority = $this->getExtraPriority();

        foreach ($collection as $item) {
            $this->_xml[] = sprintf('<url><loc>%s</loc><changefreq>%s</changefreq><priority>%.2f</priority></url>',
                htmlspecialchars(trim($item)),
                $changefreq,
                $priority
            );
        }
        unset($collection);
    }

    protected function _generateLanding()
    {
        if (!$this->getLanding()) {
            return;
        }

        $changefreq = $this->getLandingFrequency();
        $priority = $this->getLandingPriority();

        /** @var \Amasty\Xlanding\Model\ResourceModel\Page $pageResource */
        $pageResource = $this->_objectManager->create('\Amasty\Xlanding\Model\ResourceModel\Page');
        $collection = $pageResource->getSitemapCollection($this->_storeId);

        foreach ($collection as $item) {
            $landingUrl = $this->_baseUrl . $item->getUrl();
            if ($this->_isUrlToExclude($landingUrl)) {
                continue;
            }
            $this->_xml[] = sprintf('<url><loc>%s</loc><changefreq>%s</changefreq><priority>%.2f</priority></url>',
                htmlspecialchars($landingUrl),
                $changefreq,
                $priority
            );
        }

        unset($landingPages);
    }

    protected function _generateBlog()
    {
        if (!$this->getBlog()) {
            return;
        }

        /** @var \Amasty\Blog\Model\Sitemap $blogSitemap */
        $blogSitemap = $this->_objectManager->create('Amasty\Blog\Model\Sitemap');
        $blogLinks = $blogSitemap->generateLinks();

        $changefreq = $this->getBlogFrequency();
        $priority = $this->getBlogPriority();

        foreach ($blogLinks as $link) {
            if ($link['url']) {
                if ($this->_isUrlToExclude($link['url'])) {
                    continue;
                }
                $this->_xml[] = sprintf('<url><loc>%s</loc><changefreq>%s</changefreq><priority>%.2f</priority></url>',
                    str_replace('index.php/', '', $link['url']),
                    $changefreq,
                    $priority
                );
            }
        }
    }

    protected function _generateBrands()
    {
        if (!$this->getBrands()) {
            return;
        }

        $attrCode = $this->_scopeConfig->getValue('amshopby_brand/general/attribute_code');

        if (!$attrCode) {
            return;
        }

        $changefreq = $this->getBrandsFrequency();
        $priority = $this->getBrandsPriority();

        $brandPages = $this->_eavConfig->getAttribute(\Magento\Catalog\Model\Product::ENTITY, $attrCode);

        /** @var \Amasty\Shopby\Helper\OptionSetting $shopByHelper */
        $shopByHelper = $this->_objectManager->create('Amasty\Shopby\Helper\OptionSetting');

        /** @var \Amasty\ShopbySeo\Helper\Url $shopByHelperUrl */
        $shopByHelperUrl = $this->_objectManager->create('Amasty\ShopbySeo\Helper\Url');

        foreach ($brandPages->getSource()->getAllOptions() as $option) {
            if ($option['value']) {
                $url = $shopByHelper->getSettingByValue($option['value'], \Amasty\Shopby\Helper\FilterSetting::ATTR_PREFIX . $attrCode, $this->_storeId)->getUrlPath();
                if ($shopByHelperUrl->isSeoUrlEnabled()) {
                    $url = $shopByHelperUrl->seofyUrl($url);
                }
                $url = $this->_removeSid($url);
                if ($this->_isUrlToExclude($url) || !$url) {
                    continue;
                }
                $this->_xml[] = sprintf('<url><loc>%s</loc><changefreq>%s</changefreq><priority>%.2f</priority></url>',
                    $this->_getBrandUrl($url),
                    $changefreq,
                    $priority
                );
            }
        }

        unset($brandPages);
    }

    protected function _removeSid($url)
    {
        $baseUrl = substr($url, 0, strpos($url, '?'));
        if (!$baseUrl) {
            return $url;
        }
        $parsed = [];
        parse_str(substr($url, strpos($url, '?') + 1), $parsed);
        if (isset($parsed['SID'])) {
            $url = $baseUrl;
            unset($parsed['SID']);
            if (!empty($parsed)) {
                $url .= '?' . http_build_query($parsed);
            }
        }
        return $url;
    }

    protected function _generateNavigation()
    {
        if (!$this->getNavigation()) {
            return;
        }

        $changefreq = $this->getNavigationFrequency();
        $priority = $this->getNavigationPriority();

        /** @var \Amasty\ShopbyPage\Model\ResourceModel\Page\Collection $collection */
        $collection = $this->_objectManager->create('\Amasty\ShopbyPage\Model\ResourceModel\Page\Collection');
        $collection->addFieldToFilter('url', array('neq' => ''));
        $collection->addStoreFilter($this->_storeId);

        foreach ($collection as $item) {
            $url = $item->getUrl();
            if ($this->_isUrlToExclude($url)) {
                continue;
            }
            $this->_xml[] = sprintf('<url><loc>%s</loc><changefreq>%s</changefreq><priority>%.2f</priority></url>',
                $url,
                $changefreq,
                $priority
            );
        }

        unset($navigationPages);
    }

    protected function _writePortion($chunk, $index = false)
    {
        $pieces = array();
        $path = $this->getPath();

        $name = $this->_getSitemapFileName();
        $this->_iterator++;
        if (!$index) {
            $name = str_replace('.xml', '', $name);
            $name .= '_' . $this->_iterator . '.xml';
        }

        $fullPath = $this->getDirUrl() . $name;

        /** @var \Magento\Framework\Filesystem\File\Write $stream */
        $stream = $this->_directory->openFile($fullPath);
        $pieces[] = $fullPath;

        $stream->write('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        $stream->write('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" ');

        if ($this->getProductsThumbs() || $this->getCategoriesThumbs()) {
            $stream->write('xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"');
        }
        $stream->write('>');

        for ($i = 0; $i < count($chunk); $i++) {
            if (false === strpos($chunk[$i], '<loc></loc>')) {
                $stream->write($chunk[$i] . "\n");
                if (isset($chunk[$i + 1])) {
                    $fileSize = $this->_testFileSize($fullPath, $chunk[$i + 1]);
                    if ($this->getMaxFileSize() && $fileSize > ($this->getMaxFileSize() * 1024)) {
                        $newArray = array_slice($chunk, $i + 1);
                        $pieces = array_merge($pieces, $this->_writePortion($newArray, false));
                        break;
                    }
                }
            }
        }
        $stream->write('</urlset>');
        $stream->close();

        if ($this->_isFirst) {
            $this->_firstTime = $this->_getAbsolutePath($fullPath);
            $this->_isFirst = false;
        }

        return $pieces;
    }

    protected function _renameFirstFile(&$chunks)
    {
        $path = $this->_getPath();
        $this->_ioFile->setAllowCreateFolders(true);
        $this->_ioFile->open(array('path' => $path));

        $name = $this->_getSitemapFileName();
        $newFileName = str_replace('.xml', '', $name);
        $newFileName .= '_1.xml';

        $chunks[0] = $this->getDirUrl() . $newFileName;

        $this->_ioFile->cp($name, $newFileName);
    }

    protected function _getSitemapFileName()
    {
        $filename = pathinfo($this->getFolderName());

        return $filename['basename'];
    }

    protected function _getPath()
    {
        if (is_null($this->_filePath)) {
            $dirname = pathinfo($this->getFolderName());
            if ($dirname['dirname'] == '.') {
                $this->_filePath = str_replace('//', '/', $this->_dir->getRoot() . '/');
            } else {
                $this->_filePath = str_replace('//', '/', $this->_dir->getRoot() . '/' . $dirname['dirname'] . '/');
            }
        }

        return $this->_filePath;
    }

    protected function _testFileSize($testFile, $line)
    {
        $dirUrl = $this->getDirUrl();
        $fileName = 'am_sitemap_test' . rand(1, 1000) . '.xml';
        $tempFile = $dirUrl . $fileName;
        $tempFile = $this->_getAbsolutePath($tempFile);
        $testFile = $this->_getAbsolutePath($testFile);
        $this->_ioFile->cp($testFile, $tempFile);
        $stream = $this->_directory->openFile($tempFile, 'a');
        $stream->write($line);
        $stream->write('</urlset>');
        $stream->close();

        $fileSize = filesize($tempFile);
        unlink($tempFile);

        return $fileSize;
    }

    protected function _getAbsolutePath($path)
    {
        $path = $this->_dir->getRoot() . '/' . $path;

        return $path;
    }

    protected function _writeIndexFile($pieces)
    {
        $this->_date = $this->_dateTime->gmtDate($this->getDateFormat());
        $this->_baseUrl = $this->_storeManager->getStore()->getBaseUrl(XmlUrlInterface::URL_TYPE_WEB);

        $name = $this->getDirUrl() . $this->_getSitemapFileName();
        $stream = $this->_directory->openFile($name);
        $stream->write('<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        $stream->write('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
        foreach ($pieces as $url) {
            $item = sprintf('<sitemap><loc>%s</loc><lastmod>%s</lastmod></sitemap>',
                htmlspecialchars($this->_baseUrl . $url),
                $this->_date
            );
            $stream->write($item . "\n");
        }
        $stream->write('</sitemapindex>');
        $stream->close();
    }

    protected function getDirUrl()
    {
        $dirname = pathinfo($this->getFolderName());

        $dirPath = $dirname['dirname'] . '/';

        if ($dirname['dirname'] == '.') {
            $dirPath = '';
        }

        return $dirPath;
    }

    protected function _isUrlToExclude($url)
    {
        $isToExclude = false;

        if (empty($this->_excludeUrls)) {
            $this->_excludeUrls = $this->getExcludeUrls();
        }

        if ($this->_excludeUrls != '' && strpos($this->_excludeUrls, $url) !== false) {
            $isToExclude = true;
        }

        return $isToExclude;
    }

    /**
     * Get store base url
     *
     * @param string $type
     * @return string
     */
    protected function _getStoreBaseUrl($type = XmlUrlInterface::URL_TYPE_LINK)
    {
        return rtrim($this->_storeManager->getStore($this->getStoreId())->getBaseUrl($type), '/') . '/';
    }

    /**
     * Get url
     *
     * @param string $url
     * @param string $type
     * @return string
     */
    protected function _getUrl($url, $type = XmlUrlInterface::URL_TYPE_LINK)
    {
        return $this->_getStoreBaseUrl($type) . ltrim($url, '/');
    }

    /**
     * Get media url
     *
     * @param string $url
     * @return string
     */
    protected function _getMediaUrl($url)
    {
        return $this->_getUrl($url, XmlUrlInterface::URL_TYPE_MEDIA);
    }

    /**
     * Get brand url
     *
     * @param string $url
     * @param string $type
     * @return string
     */
    protected function _getBrandUrl($url, $type = UrlInterface::URL_TYPE_LINK)
    {
        $urlExplode = parse_url($url);
        $brandUrl   = $urlExplode['path'] . (isset($urlExplode['query'])) ?: '';
        return $this->_getStoreBaseUrl($type) . ltrim($brandUrl, '/');
    }
}
