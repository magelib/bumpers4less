<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */


namespace Amasty\XmlSitemap\Controller\Adminhtml\Sitemap;

use Magento\Backend\App\Action;

class Edit extends \Magento\Backend\App\Action
{
    /** @var \Amasty\XmlSitemap\Model\Sitemap $_sitemap */
    protected $_sitemap;
    /** @var \Magento\Framework\Registry $_coreRegistry */
    protected $_coreRegistry;
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $_resultPageFactory;

    /**
     * Edit constructor.
     * @param Action\Context $context
     * @param \Amasty\XmlSitemap\Model\Sitemap $sitemap
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        Action\Context $context,
        \Amasty\XmlSitemap\Model\Sitemap $sitemap,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    )
    {
        parent::__construct($context);
        $this->_sitemap = $sitemap;
        $this->_coreRegistry = $coreRegistry;
        $this->_resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        /** @var \Amasty\XmlSitemap\Model\Sitemap $model */
        $model = $this->_sitemap->load($id);

        if ($model->getId() || $id == 0) {
            $data = $this->_session->getPageData(true);
            if (!empty($data)) {
                $model->setData($data);
            }

            $this->_coreRegistry->register('amxmlsitemap_profile', $model);

            $this->_view->loadLayout();
            $this->_setActiveMenu('Amasty_XmlSitemap::xml_sitemap')->_addBreadcrumb(__('Table Rates'), __('Table Rates'));

            if ($model->getId()) {
                $title = __('Edit Item `%1`', $model->getTitle());
            } else {
                $title = __("Add Item");
            }
            $this->_view->getPage()->getConfig()->getTitle()->prepend($title);

            $this->_view->renderLayout();
        } else {
            $this->messageManager->addErrorMessage(__('Item does not exist'));
            /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/');
        }
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
