<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */


namespace Amasty\XmlSitemap\Controller\Adminhtml\Sitemap;

use Magento\Backend\App\Action;

class Duplicate extends \Magento\Backend\App\Action
{
    /** @var \Amasty\XmlSitemap\Model\Sitemap $_sitemap */
    protected $_sitemap;
    /** @var \Magento\Framework\Registry $_coreRegistry */
    protected $_coreRegistry;
    /** @var \Magento\Framework\View\Result\PageFactory */
    protected $_resultPageFactory;

    /**
     * Duplicate constructor.
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

        try {
            /** @var \Amasty\XmlSitemap\Model\Sitemap $model */
            $model = $this->_sitemap->load($id);
            $data = $model->getData();
            unset($data['id']);
            $model->unsetData('id');
            $model->save();

            $this->messageManager->addSuccessMessage(__('Sitemap was successfully duplicated'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $this->_redirect('*/*/index');
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
