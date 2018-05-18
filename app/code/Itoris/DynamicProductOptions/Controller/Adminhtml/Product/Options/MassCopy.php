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

namespace Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options;

class MassCopy extends \Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $filter = $this->_objectManager->create('Magento\Ui\Component\MassAction\Filter');
        $collectionFactory = $this->_objectManager->create('Magento\Catalog\Model\ResourceModel\Product\CollectionFactory');
        $collection = $filter->getCollection($collectionFactory->create());
        $productIds = $collection->getAllIds();
        
        $fromProductId = (int)$this->getRequest()->getParam('from_product_id');
        if (is_array($productIds)) {
            //loading configs for all stores
            $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
            $con = $res->getConnection('read');
            $configs = $con->fetchAll("select `config_id`, `store_id` from {$res->getTableName('itoris_dynamicproductoptions_options')} where `product_id`={$fromProductId} order by `store_id`");
            $options = [];
            foreach($configs as $config) $options[$config['store_id']] = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Options')->setStoreId($config['store_id'])->load($fromProductId);
            if (count($options) > 0) {
                $saved = 0;
                foreach ($productIds as $newProductId) {
                    if ($this->applyToProduct($newProductId, $options)) {
                        $saved++;
                    }
                }
                $this->messageManager->addSuccess(__(sprintf('%s products have been changed', $saved)));
                
                //invalidate FPC
                $cacheTypeList = $this->_objectManager->create('\Magento\Framework\App\Cache\TypeList');
                $cacheTypeList->invalidate('full_page');
                    
            } else {
                $this->messageManager->addError(__(sprintf('Product doesn\'t have custom options')));
            }
        } else {
            $this->messageManager->addError(__('Please select product ids'));
        }

        $this->_redirect('catalog/product/', ['_current' => true]);
    }
}