<?php
/**
 * ITORIS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the ITORIS's Magento Extensions License Agreement
 * which is available through the world-wide-web at this URL:
 * http://www.itoris.com/magento-extensions-license.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to sales@itoris.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extensions to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to the license agreement or contact sales@itoris.com for more information.
 *
 * @category   ITORIS
 * @package    ITORIS_M2_DYNAMIC_PRODUCT_OPTIONS
 * @copyright  Copyright (c) 2016 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

//app/code/Itoris/DynamicProductOptions/Controller/Catalog/Product/View.php
namespace Itoris\DynamicProductOptions\Controller\Catalog\Product;

class View extends \Magento\Catalog\Controller\Product\View
{
    /**
     * Product view action
     *
     * @return \Magento\Framework\Controller\Result\Forward|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        /** @var \Magento\Catalog\Model\Session $catalogSession */
        $catalogSession = $this->_objectManager->get('Magento\Catalog\Model\Session');

        if ($catalogSession->getDynamicOptionsBuyRequest()) {
            /** @var \Magento\Catalog\Helper\Product $productHelper */
            $productHelper = $this->_objectManager->create('Magento\Catalog\Helper\Product');
            if (!method_exists($productHelper, 'prepareProductOptions')) {
                return parent::execute();
            }
            // Get initial data from request
            $categoryId = (int) $this->getRequest()->getParam('category', false);
            $productId  = (int) $this->getRequest()->getParam('id');
            $specifyOptions = $this->getRequest()->getParam('options');

            // Prepare helper and params
            /** @var \Magento\Catalog\Helper\Product\View $viewHelper */
            $viewHelper = $this->_objectManager->create('Magento\Catalog\Helper\Product\View');

            $params = new \Magento\Framework\DataObject();
            $params->setCategoryId($categoryId);
            $params->setSpecifyOptions($specifyOptions);

            $buyRequest = new \Magento\Framework\DataObject($catalogSession->getDynamicOptionsBuyRequest(true));
            if (isset($buyRequest['product']) && $buyRequest['product'] == $productId) {
                $params->setBuyRequest($buyRequest);
            } else {
                $catalogSession->getDynamicOptionsErrorOptionId(true);
                $catalogSession->getDynamicOptionsErrorMessage(true);
            }

            // Render page
            try {
                $page = $this->resultPageFactory->create(false, ['isIsolated' => true]);
                $this->viewHelper->prepareAndRender($page, $productId, $this, $params);
                return $page;
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                return $this->noProductRedirect();
            } catch (\Exception $e) {
                $this->_objectManager->get('Psr\Log\LoggerInterface')->critical($e);
                $resultForward = $this->resultForwardFactory->create();
                $resultForward->forward('noroute');
                return $resultForward;
            }

        } else {
            parent::execute();
        }
    }
}