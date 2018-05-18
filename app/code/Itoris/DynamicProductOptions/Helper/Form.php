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

namespace Itoris\DynamicProductOptions\Helper;

class Form extends Data
{

    private $yesNoValues = [
        [
            'value' => 1,
            'label' => 'Yes',
        ],
        [
            'value' => 0,
            'label' => 'No',
        ],
    ];

    public function getYesNoOptionValues() {
        return $this->prepareValues($this->yesNoValues);
    }

    private function prepareValues($values, $withoutValues = []) {
        foreach ($values as $key => $value) {
            if (!in_array($value['value'], $withoutValues)) {
                $values[$key]['label'] = __(sprintf($value['label']));
            } else {
                unset($values[$key]);
            }
        }

        return $values;
    }

    /**
     * Prepare elements values for form
     *
     * @param $form \Magento\Framework\Data\Form
     *
     * @return array
     */
    public function prepareElementsValues($form) {
        $values = [];
        $fieldsets = $form->getElements();
        $checkStore = (bool)$this->_request->getParam('store');
        $useScopeLabel = $checkStore ? __('Use Default') : null;
        foreach ($fieldsets as $fieldset) {
            if (get_class($fieldset) == 'Magento\Framework\Data\Form\Element\Fieldset') {
                foreach ($fieldset->getElements() as $element) {
                    if ($id = $element->getId()) {
                        $value = $this->getSettings(true)->getSettingsValue($id);
                        if ($value !== null) {
                            $values[$id] = $value;
                        }
                        if ($element->getType() == 'checkbox' && $value) {
                            $element->setIsChecked($value);
                        }
                        $element->setUseParentValue($this->getIsParentValue($id));
                        $element->setUseScopeLabel($useScopeLabel);
                    }
                }
            }
        }

        return $values;
    }

    public function getIsParentValue($settingId) {
        return $this->getSettings(true)->isParentValue($settingId);
    }

    /**
     * Is setting value default
     *
     * @return bool
     */
    protected function isDefaultSettings() {
        return !$this->_request->getParam('store');
    }

}