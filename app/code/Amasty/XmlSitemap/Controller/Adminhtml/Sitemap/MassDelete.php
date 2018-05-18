<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */


namespace Amasty\XmlSitemap\Controller\Adminhtml\Sitemap;

use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Cms\Model\ResourceModel\Block\CollectionFactory;

class MassDelete extends \Magento\Backend\App\Action
{
    /** @var Filter */
    protected $filter;

    /** @var \Amasty\XmlSitemap\Model\ResourceModel\Sitemap\Collection */
    protected $_sitemapCollection;

    /**
     * MassDelete constructor.
     * @param Context $context
     * @param Filter $filter
     * @param \Amasty\XmlSitemap\Model\ResourceModel\Sitemap\Collection $sitemapCollection
     */
    public function __construct(
        Context $context,
        Filter $filter,
        \Amasty\XmlSitemap\Model\ResourceModel\Sitemap\Collection $sitemapCollection
    )
    {
        $this->filter = $filter;
        $this->_sitemapCollection = $sitemapCollection;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     * @throws \Magento\Framework\Exception\LocalizedException|\Exception
     */
    public function execute()
    {
        $ids = $this->getRequest()->getParam('selected');

        try {
            $this->_sitemapCollection->addFieldToFilter('id', array('in' => $ids));

            $this->_sitemapCollection->walk('delete');

            $this->messageManager->addSuccessMessage(__('Items(s) were successfully deleted'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('We can\'t delete item(s) right now. Please review the log and try again. ') . $e->getMessage()
            );
            $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
        }


        $this->_redirect('*/*/');
    }

    /**
     * Check if Amasty XML Sitemap is allowed
     *
     * @return bool
     */
    protected function isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_XmlSitemap::sitemap');
    }
}
