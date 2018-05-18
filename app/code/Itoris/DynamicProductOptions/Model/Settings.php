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


namespace Itoris\DynamicProductOptions\Model;

class Settings extends \Magento\Framework\DataObject
{

    /** @var  \Magento\Framework\App\ResourceConnection */
    private $_resource = null;
    /** @var \Magento\Framework\DB\Adapter\AdapterInterface */
    private $_connection = null;
    private $_table = 'core_config_data';
    private $_path = 'itoris_dynamicproductoptions/general/enabled';
    private $_textOptions = [];

    private $_settings = [
        'by_default' => [
            'enabled' => 1
        ]
    ];
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager = null;
    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $data
     */

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ){
        $this->_objectManager = $objectManager;
        /** @var \Magento\Framework\App\ResourceConnection $_resource */
        $this->_resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $this->_connection = $this->_resource->getConnection('write');
        $this->_table = $this->_resource->getTableName($this->_table);
        parent::__construct($data);
    }

    public function load($websiteId, $storeId) {
        $websiteId = (int)$websiteId;
        $storeId = (int)$storeId;
        $settings = $this->_connection->fetchAll("select * from {$this->_table} where `path` = '{$this->_path}'");
        $data = $this->_connection->fetchOne("select `value` from {$this->_table} where `path` = 'itoris_core/installed/Itoris_DynamicProductOptions'");
        $isReg = count(explode('|', $data)) == 2;        
        
        foreach ($settings as $setting) {
            $settingValue = (int) $setting['value'];
            if ($setting['scope'] == 'stores') {
                $this->_settings['store']['enabled'] = $settingValue && $isReg;
            } elseif ($setting['scope'] == 'websites') {
                $this->_settings['website']['enabled'] = $settingValue && $isReg;
            } else {
                $this->_settings['default']['enabled'] = $settingValue && $isReg;
            }
        }

        return $this;
    }

    public function getSettingsValue($key) {
        return $this->__call('get' . $key, []);
    }

    public function __call($method, $args) {
        if (substr($method, 0, 3) == 'get') {            
            $key = $this->_underscore(substr($method,3));
            if (isset($this->_settings['store'][$key])) {
                return $this->_settings['store'][$key];
            } elseif (isset($this->_settings['website'][$key])) {
                return $this->_settings['website'][$key];
            } elseif (isset($this->_settings['default'][$key])) {
                return $this->_settings['default'][$key];
            } elseif (isset($this->_settings['by_default'][$key])) {
                return $this->_settings['by_default'][$key];
            }
            return $this->getData($key, isset($args[0]) ? $args[0] : null);
        } else {
            parent::__call($method,$args);
        }
    }

    public function isParentValue($key) {
        if (isset($this->_settings['store'][$key])) {
            return false;
        }

        return true;
    }

    public function _isValid($settings) {
        $errors = [];

        if (empty($errors)) {
            return true;
        }

        return $errors;
    }

    private function _isTextOption($key) {
        return in_array($key, $this->_textOptions);
    }
}