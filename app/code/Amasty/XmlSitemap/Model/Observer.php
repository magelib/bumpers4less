<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */


namespace Amasty\XmlSitemap\Model;

class Observer
{
    /** @var ResourceModel\Sitemap\CollectionFactory $_sitemapCollection */
    protected $_sitemapCollection;

    /**
     * Observer constructor.
     * @param ResourceModel\Sitemap\CollectionFactory $sitemapCollection
     */
    public function __construct(
        \Amasty\XmlSitemap\Model\ResourceModel\Sitemap\CollectionFactory $sitemapCollection
    )
    {
        $this->_sitemapCollection = $sitemapCollection;
    }

    public function process()
    {
        /** @var ResourceModel\Sitemap\Collection $profiles */
        $profiles = $this->_sitemapCollection->create();

        /** @var Sitemap $profile */
        foreach ($profiles as $profile) {
            $profile->generateXml();
        }
    }
}
