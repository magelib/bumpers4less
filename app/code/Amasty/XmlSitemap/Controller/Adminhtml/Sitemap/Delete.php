<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */


namespace Amasty\XmlSitemap\Controller\Adminhtml\Sitemap;

use Magento\Backend\App\Action;

class Delete extends \Magento\Backend\App\Action
{
    /** @var \Amasty\XmlSitemap\Model\Sitemap $_sitemap */
    protected $_sitemap;

    /**
     * Delete constructor.
     * @param Action\Context $context
     * @param \Amasty\XmlSitemap\Model\Sitemap $sitemap
     */
    public function __construct(
        Action\Context $context,
        \Amasty\XmlSitemap\Model\Sitemap $sitemap
    )
    {
        parent::__construct($context);
        $this->_sitemap = $sitemap;
    }

    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $sitemap = $this->_sitemap->load($id);

        if ($id && !$sitemap->getId()) {
            $this->messageManager->addErrorMessage(__('Record does not exist'));
            $this->_redirect('*/*/');
            return;
        }

        try {
            $sitemap->delete();
            $this->messageManager->addSuccessMessage(
                __('Item has been successfully deleted'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
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
