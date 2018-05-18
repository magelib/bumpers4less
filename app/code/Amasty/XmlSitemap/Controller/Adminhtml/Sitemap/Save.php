<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */

namespace Amasty\XmlSitemap\Controller\Adminhtml\Sitemap;

use Magento\Backend\App\Action;

class Save extends \Magento\Backend\App\Action
{
    /** @var \Amasty\XmlSitemap\Model\Sitemap $_sitemap */
    protected $_sitemap;

    /**
     * Save constructor.
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
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $profile = $this->_sitemap;
            $id = $this->getRequest()->getParam('id');
            $profile->setData($data)->setId($id);

            try {
                $profile->getResource()->save($profile);
                $profileId = $profile->getId();

                $this->messageManager->addSuccessMessage(__('Profile was successfully saved'));
                $this->_session->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $profileId));
                    return;
                }
                $this->_redirect('*/*/');

                return;
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->_session->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));

                return;
            }
        }
        $this->messageManager->addErrorMessage(__('Unable to find item to save'));
        $this->_redirect('*/*/');
    }

    protected function isAllowed()
    {
        return $this->_authorization->isAllowed('Amasty_XmlSitemap::sitemap');
    }
}
