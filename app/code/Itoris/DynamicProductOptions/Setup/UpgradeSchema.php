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

namespace Itoris\DynamicProductOptions\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{

    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        
        if (version_compare($context->getVersion(), '1.8.2') < 0) {
            $setup->run("ALTER TABLE `{$setup->getTable('itoris_dynamicproductoptions_option')}` CHANGE `configuration` `configuration` MEDIUMTEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;");
        } 
        
        if (version_compare($context->getVersion(), '2.0.0') < 0) {
            $setup->run("ALTER TABLE `{$setup->getTable('itoris_dynamicproductoptions_template')}` ADD `store_id` INT UNSIGNED NOT NULL AFTER `template_id`, ADD `parent_id` INT UNSIGNED NOT NULL AFTER `store_id`, ADD INDEX (`store_id`), ADD INDEX (`parent_id`);");
            
            $setup->run("ALTER TABLE `{$setup->getTable('itoris_dynamicproductoptions_template')}` DROP INDEX name;");
            
            $setup->run("CREATE TABLE `{$setup->getTable('itoris_dynamicproductoptions_template_product')}` ( `template_id` INT UNSIGNED NOT NULL , `config_id` INT UNSIGNED NOT NULL ) ENGINE = InnoDB;");
            
            $setup->run("ALTER TABLE `{$setup->getTable('itoris_dynamicproductoptions_template_product')}` ADD CONSTRAINT `FK_ITORIS_DOPTIONS_TEMPLATE_PRODUCT_ID` FOREIGN KEY (`template_id`) REFERENCES `{$setup->getTable('itoris_dynamicproductoptions_template')}`(`template_id`) ON DELETE CASCADE ON UPDATE CASCADE;");
            
            $setup->run("ALTER TABLE `{$setup->getTable('itoris_dynamicproductoptions_template_product')}` ADD CONSTRAINT `FK_ITORIS_DOPTIONS_TEMPLATE_CONFIG_ID` FOREIGN KEY (`config_id`) REFERENCES `{$setup->getTable('itoris_dynamicproductoptions_options')}`(`config_id`) ON DELETE CASCADE ON UPDATE CASCADE;");
        }
        
        if (version_compare($context->getVersion(), '2.1.0') < 0) {
            $setup->run("ALTER TABLE `{$setup->getTable('itoris_dynamicproductoptions_template')}` ADD `absolute_pricing` TINYINT UNSIGNED NOT NULL AFTER `extra_js`;");

            $setup->run("ALTER TABLE `{$setup->getTable('itoris_dynamicproductoptions_options')}` ADD `absolute_pricing` TINYINT UNSIGNED NOT NULL AFTER `extra_js`;");
        }
        
        if (version_compare($context->getVersion(), '2.3.0') < 0) {
            $setup->run("ALTER TABLE `{$setup->getTable('itoris_dynamicproductoptions_template')}` ADD `absolute_sku` TINYINT UNSIGNED NOT NULL AFTER `absolute_pricing`;");

            $setup->run("ALTER TABLE `{$setup->getTable('itoris_dynamicproductoptions_options')}` ADD `absolute_sku` TINYINT UNSIGNED NOT NULL AFTER `absolute_pricing`;");
            
            $setup->run("ALTER TABLE `{$setup->getTable('itoris_dynamicproductoptions_template')}` ADD `absolute_weight` TINYINT UNSIGNED NOT NULL AFTER `absolute_sku`;");

            $setup->run("ALTER TABLE `{$setup->getTable('itoris_dynamicproductoptions_options')}` ADD `absolute_weight` TINYINT UNSIGNED NOT NULL AFTER `absolute_sku`;");
        }
        
        $setup->endSetup();
    }
}