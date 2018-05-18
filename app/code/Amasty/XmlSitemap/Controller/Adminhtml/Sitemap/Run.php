<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */

namespace Amasty\XmlSitemap\Controller\Adminhtml\Sitemap;

use Magento\Backend\App\Action;

class Run extends Action
{
    /** @var \Amasty\XmlSitemap\Model\Sitemap $_sitemap */
    protected $_sitemap;

    /**
     * Run constructor.
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

        $this->_sitemap->load($id);
        if (!$this->_sitemap->getId()) {
            $this->messageManager->addErrorMessage(__('Item does not exist'));
            $this->_redirect('*/*/');
        }

        try {
            $this->_sitemap->run();
            $this->messageManager->addSuccessMessage(__('Sitemap has been saved'));
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
