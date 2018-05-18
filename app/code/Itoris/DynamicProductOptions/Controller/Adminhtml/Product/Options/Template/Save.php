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

namespace Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options\Template;

class Save extends \Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options\Template
{
    /**
     * @return mixed
     */
    public function execute()
    {
        try {
            $data = $this->getRequest()->getParam('template', []);
            /** @var $template \Itoris\DynamicProductOptions\Model\Template */
            $template = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Template');
            $id = $_id = (int)$this->getRequest()->getParam('id');
            $storeId = (int)$this->getRequest()->getParam('store');
            $useGlobal = !!$this->getRequest()->getParam('idpo_use_global');
            if ($storeId) {
                $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
                $con = $res->getConnection('read');
                $id = (int) $con->fetchOne("select `template_id` from {$res->getTableName('itoris_dynamicproductoptions_template')} where `store_id`={$storeId} and `parent_id`={$id}");
            }            
            if ($id) $template->load($id);
            if (isset($data['name']) && $data['name']) {
                $template->setName($data['name']);
                $templateData = $this->getRequest()->getParam('itoris_dynamicproductoptions');
                if (is_array($templateData)) $template->addData($templateData);
                $template->setStoreId($storeId);
                $template->save();
                if ($storeId) {
                    $con->query("update {$res->getTableName('itoris_dynamicproductoptions_template')} set `parent_id`={$_id} where `template_id`={$template->getId()}");
                    if ($useGlobal) $template->delete();
                }
                $this->messageManager->addSuccess(__('Template has been saved'));
            } else {
                $this->messageManager->addError(__('Template name is required'));
            }

            if (!$_id && !$storeId) $_id = $template->getId();
            
            $configs = $this->mergeStoreOptions($_id);

            if ($this->getRequest()->getParam('apply')) {
                $session = $this->_objectManager->get('Magento\Backend\Model\Session');
                $session->setDpoTemplateUpdateProducts(['templates' => [$_id]]);
            }
            
        } catch (\Exception $e) {
           $this->messageManager->addError(__('Template has not been saved'));
        }

        if ($this->getRequest()->getParam('back')) {
            $this->_redirect('*/*/edit', ['id' => $_id, 'store' => $storeId]);
        } else {
            $this->_redirect('*/*/');
        }
    }
    
    public function mergeStoreOptions($id) {
        $storeId = (int)$this->getRequest()->getParam('store');
        $res = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $con = $res->getConnection('write');
        $defaultConfig = $con->fetchOne("select `configuration` from {$res->getTableName('itoris_dynamicproductoptions_template')} where `template_id`={$id}");
        $defaultConfig = (array)json_decode($defaultConfig, true);
        
        $needsUpdate = false;
        foreach($defaultConfig as $sectionId => $section) {
            if (!isset($section['fields'])) continue;
            foreach($section['fields'] as $pos => $field) {
                if (isset($field['items'])) {
                    $maxId = 0;
                    foreach($field['items'] as $item) {
                        if (isset($item['internal_id']) && $item['internal_id'] > $maxId) $maxId = $item['internal_id'];
                    }
                    foreach($field['items'] as $key => $item) {
                        if (!is_array($item)) continue;
                        if (!isset($item['internal_id'])) {
                            $maxId++;
                            $defaultConfig[$sectionId]['fields'][$pos]['items'][$key]['internal_id'] = $maxId;
                            $needsUpdate = true;
                        }
                    }
                }
            }
        }
        if ($needsUpdate) {
            $con->exec("update {$res->getTableName('itoris_dynamicproductoptions_template')} set `configuration` = ".$con->quote(json_encode($defaultConfig))." where `template_id`={$id}");
        }
        $configs = [0 => $defaultConfig];
        
        $storeConfigs = $con->fetchAll("select `template_id`, `store_id`, `configuration` from {$res->getTableName('itoris_dynamicproductoptions_template')} where `parent_id`={$id}");
        if (!count($storeConfigs)) return $configs;
        
        $defaultFields = [];
        foreach($defaultConfig as $sectionId => $section) {
            if (!isset($section['fields'])) continue;
            foreach($section['fields'] as $pos => $field) {
                if (!is_array($field)) continue;
                $defaultFields[$field['internal_id']] = array_merge($field, ['tmp_position' => ['section' => $sectionId, 'position' => $pos]]);
            }
        }
        foreach($storeConfigs as $_storeConfig) {
            $storeConfig = json_decode($_storeConfig['configuration'], true);
            $storeFields = [];
            foreach($storeConfig as $sectionId => $section) {
                if (!isset($section['fields'])) continue;
                foreach($section['fields'] as $pos => $field) {
                    if (!is_array($field)) continue;
                    $storeFields[$field['internal_id']] = array_merge($field, ['tmp_position' => ['section' => $sectionId, 'position' => $pos]]);
                }
            }
            
            //adding new fields to store-view configs from the default config
            foreach($defaultFields as $index => $field) {
                if (!isset($storeFields[$index])) {
                    //insert into the same position if possible
                    $sectionId = (int)$field['tmp_position']['section'];
                    $positionId = (int)$field['tmp_position']['position'];
                    if (isset($storeConfig[$sectionId]['fields'])) {
                        //position occupied, find next available
                        while (!empty($storeConfig[$sectionId]['fields'][$positionId])) $positionId++;
                    } else {//position not found, insert into the 1st section
                        $sectionId = 1;
                        $positionId = 1;
                        while (!empty($storeConfig[$sectionId]['fields'][$positionId])) $positionId++;
                    }
                    $storeConfig[$sectionId]['fields'][$positionId] = $field;
                    $storeConfig[$sectionId]['fields'][$positionId]['order'] = $positionId;
                    $storeConfig[$sectionId]['fields'][$positionId]['sort_order'] = $positionId;
                    unset($storeConfig[$sectionId]['fields'][$positionId]['tmp_position']);
                    if ($positionId > $storeConfig[$sectionId]['rows'] * $storeConfig[$sectionId]['cols']) {
                        $storeConfig[$sectionId]['rows'] = floor($positionId / $storeConfig[$sectionId]['cols']) + 1;
                    }
                } else if (isset($field['items'])){
                    $defaultFieldValues = [];
                    $storeFieldValues = [];
                    foreach($field['items'] as $key => $item) {
                        if (!is_array($item)) continue;
                        $defaultFieldValues[$item['internal_id']] = $item;
                    }
                    foreach($storeFields[$index]['items'] as $key => $item) {
                        if (!is_array($item)) continue;
                        $item['tmp_index'] = $key;
                        $storeFieldValues[$item['internal_id']] = $item;
                    }
                    
                    $sectionId = (int)$field['tmp_position']['section'];
                    $positionId = (int)$field['tmp_position']['position'];
                    
                    //checking and adding new option values
                    foreach($defaultFieldValues as $key => $defaultFieldValue) {
                        if (!isset($storeFieldValues[$key])) {
                            $defaultFieldValue['order'] = $defaultFieldValue['sort_order'] = count($storeConfig[$sectionId]['fields'][$positionId]['items']);
                            $storeConfig[$sectionId]['fields'][$positionId]['items'][] = $defaultFieldValue;
                        }
                    }
                    
                    //removing unnecessary values from store config
                    foreach($storeFieldValues as $key => $storeFieldValue) {
                        if (!isset($defaultFieldValues[$key])) {
                            unset($storeConfig[$sectionId]['fields'][$positionId]['items'][$storeFieldValue['tmp_index']]);
                        }
                    }
                    
                    //resort values in store config
                    if ($storeConfig[$sectionId]['fields'][$positionId]['items']) {
                        $storeConfig[$sectionId]['fields'][$positionId]['items'] = array_values($storeConfig[$sectionId]['fields'][$positionId]['items']);
                        foreach($storeConfig[$sectionId]['fields'][$positionId]['items'] as $key => $item) {
                            if (!is_array($item)) continue;
                            $storeConfig[$sectionId]['fields'][$positionId]['items'][$key]['order'] = $key;
                            $storeConfig[$sectionId]['fields'][$positionId]['items'][$key]['sort_order'] = $key;
                        }
                    }
                }
            }
            
            //if field removed from default config we have to remove it from the store config as well
            foreach($storeFields as $index => $field) {
                if (!isset($defaultFields[$index])) {
                    $sectionId = $field['tmp_position']['section'];
                    $positionId = $field['tmp_position']['position'];
                    unset($storeConfig[$sectionId]['fields'][$positionId]);
                }
            }
            
            $con->exec("update {$res->getTableName('itoris_dynamicproductoptions_template')} set `configuration` = ".$con->quote(json_encode($storeConfig))." where `template_id`={$_storeConfig['template_id']}");
            
            $configs[(int)$_storeConfig['store_id']] = $storeConfig;
        }
        
        return $configs;
    }
}