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

namespace Itoris\DynamicProductOptions\Controller\Adminhtml\Product;

class ImportAll extends \Magento\Framework\App\Action\Action
{
    public function execute()
    {        
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $importFile = $this->getRequest()->getFiles()->get('dynamic_product_options_import');
        if (!empty($importFile)) {
            $str = file_get_contents($importFile['tmp_name']);
            $configs = @json_decode($str, true);
            if ($configs && !is_null($configs)) {
                $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
                $con = $res->getConnection('write');
                $processed = []; $skippedSkus = [];
                foreach($configs as $config) {
                    $sku = $config['product_sku'];
                    unset($config['product_sku']);
                    $productId = (int) $con->fetchOne("select `entity_id` from {$res->getTableName('catalog_product_entity')} where `sku` LIKE ".$con->quote($sku));
                    if ($productId) {
                        if (!isset($processed[$productId])) $con->query("delete from {$res->getTableName('itoris_dynamicproductoptions_options')} where `product_id`={$productId}"); //clean up
                        $processed[$productId] = $sku;
                        //inserting options configs
                        $con->exec("insert into {$res->getTableName('itoris_dynamicproductoptions_options')} set ".$this->getQueryString($con, array_merge($config, ['product_id' => $productId])));
                        //generate options
                        $this->_objectManager->get('Itoris\DynamicProductOptions\Model\Rewrite\Option')->duplicate($productId, $productId);
                    } else {
                       $skippedSkus[$sku] = true;
                    }
                }
                if (!empty($processed)) {
                    $this->messageManager->addSuccess(__('%1 SKUs processed successfully', count($processed)));
                    //invalidate FPC
                    $cacheTypeList = $this->_objectManager->create('\Magento\Framework\App\Cache\TypeList');
                    $cacheTypeList->invalidate('full_page');
                }
                if (!empty($skippedSkus)) $this->messageManager->addError(__('%1 SKUs were skipped: %2', count($skippedSkus), implode(', ', array_keys($skippedSkus))));
            } else {
                $this->messageManager->addError(__('Incorrect file format. Should be JSON with dynamic options.'));
            }
        } else {
            $this->messageManager->addError(__('Error uploading file'));
        }
        $this->_redirect($this->_redirect->getRefererUrl());
    }
    
    public function getQueryString($con, $array, $primary = '') {
        $pairs = [];
        if ($primary) unset($array[$primary]); //do not include primary column
        foreach($array as $key => $value) $pairs[] = "`$key`=".(!is_null($value) ? $con->quote($value) : 'NULL');
        return implode(',', $pairs);
    }
}