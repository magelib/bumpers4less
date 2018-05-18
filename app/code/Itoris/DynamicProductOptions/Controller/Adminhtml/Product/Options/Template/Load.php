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

class Load extends \Itoris\DynamicProductOptions\Controller\Adminhtml\Product\Options\Template
{
    /**
     * @return mixed
     */
    public function execute()
    {
        $result = ['error' => false];
        $error = null;
        $templateId = (int)$this->getRequest()->getParam('template_id');
        $productId = (int)$this->getRequest()->getParam('product_id');
        $method = (int)$this->getRequest()->getParam('method');
        $prevSections = $sections = json_decode($this->getRequest()->getParam('sections'), true);
        try {
            $template = $this->_objectManager->create('Itoris\DynamicProductOptions\Model\Template')->load($templateId);
            if ($template->getId()) {
                $result['message'] = __(sprintf('Template %s has been loaded', $template->getName()));
                $result['template'] = $template->getData();
                $newSections = json_decode($template->getConfiguration(), true);
                if ($method == 2 || $method == 3) {
                    foreach($newSections as $key => $section) {
                        if (!is_array($section['fields'])) continue;
                        $newSections[$key]['template_id'] = $templateId;
                    }
                }
                if ($method == 1 || $method == 3) {
                    $sectionOrder = -1;
                    $maxInternalId = 0;
                    foreach($sections as $key => $section) {
                        $sectionOrder++;
                        if (!is_array($section['fields'])) continue;
                        if (isset($section['template_id']) && $section['template_id'] == $templateId) {
                            unset($sections[$key]);
                            $sectionOrder--;
                            continue;
                        }
                        $section['order'] = $sectionOrder;
                        foreach($section['fields'] as $key2 => $field) {
                            if (!is_array($field)) continue;
                            $sections[$key]['fields'][$key2]['section_order'] = $sectionOrder;
                            if (isset($field['internal_id']) && $field['internal_id'] > $maxInternalId) $maxInternalId = $field['internal_id'];
                        }
                    }
                    $sections = array_values($sections);
                    foreach($newSections as $key => $section) {
                        if (!is_array($section['fields'])) continue;
                        $section['order'] = count($sections);
                        foreach($section['fields'] as $key2 => $field) {
                            if (!is_array($field)) continue;
                            $section['fields'][$key2]['section_order'] = $section['order'];
                            if (isset($field['internal_id'])) $section['fields'][$key2]['internal_id'] += $maxInternalId;
                            if (isset($field['visibility_condition']) && $field['visibility_condition']) {
                                $_condition = json_decode($field['visibility_condition'], true);
                                if ($_condition && isset($_condition['conditions']) && is_array($_condition['conditions'])) {
                                    foreach($_condition['conditions'] as $key => $condition) $_condition['conditions'][$key]['field'] += $maxInternalId;
                                    $section['fields'][$key2]['visibility_condition'] = json_encode($_condition);
                                }
                            }
                        }
                        if (isset($section['visibility_condition']) && $section['visibility_condition']) {
                            $_condition = json_decode($section['visibility_condition'], true);
                            if ($_condition && isset($_condition['conditions']) && is_array($_condition['conditions'])) {
                                foreach($_condition['conditions'] as $key => $condition) $_condition['conditions'][$key]['field'] += $maxInternalId;
                                $section['visibility_condition'] = json_encode($_condition);
                            }
                        }
                        $sections[] = $section;
                    }
                    $result['template']['form_style'] = 'table_sections';
                } else {
                    $sections = $newSections;
                }
                $result['template']['configuration'] = json_encode($sections);
            } else {
                $error = __('Template not found');
            }
        } catch (\Exception $e) {
            $error = __('Template has not been loaded');
        }

        if ($error) {
            $result['error'] = $error;
        }

        $this->getResponse()->setBody(\Zend_Json::encode($result));
    }
}