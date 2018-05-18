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

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {

        $setup->startSetup();
        
        $con = $setup->getConnection();
        
        $tmp = $con->fetchRow("SHOW COLUMNS FROM `{$setup->getTable('customer_group')}` where Field = 'customer_group_id'");
        $groupFkType = $tmp['Type']; //compatibility with M2.2       

        $setup->run("
            INSERT INTO {$setup->getTable('core_config_data')} (`scope`, `scope_id`, `path`, `value`) VALUES ('default', 0, 'itoris_dynamicproductoptions/general/enabled', '1');
        ");
        
        $setup->run("
            create table {$setup->getTable('itoris_dynamicproductoptions_template')} (
            `template_id` int unsigned not null auto_increment primary key,
            `name` varchar(255) not null unique,
            `configuration` mediumtext not null,
            `form_style` varchar(30) null,
            `appearance` varchar(30) null,
            `css_adjustments` text null,
            `extra_js` text null
        ) engine = InnoDB default charset = utf8;");

        $setup->run("
            create table {$setup->getTable('itoris_dynamicproductoptions_options')} (
            `config_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `product_id` int(10) unsigned not null,
            `store_id` smallint(5) unsigned not null,
            `configuration` mediumtext not null,
            `form_style` varchar(30) null,
            `appearance` varchar(30) null,
            `css_adjustments` text null,
            `extra_js` text null,
            CONSTRAINT `FK_ITORIS_DOPTIONS_OPTIONS_PRODUCT_ID` foreign key (`product_id`) references {$setup->getTable('catalog_product_entity')} (`entity_id`) on delete cascade on update cascade,
            CONSTRAINT `FK_ITORIS_DOPTIONS_OPTIONS_STORE_ID` foreign key (`store_id`) references {$setup->getTable('store')} (`store_id`) on delete cascade on update cascade
        ) engine = InnoDB default charset = utf8;");

        $setup->run("
            create table {$setup->getTable('itoris_dynamicproductoptions_option')} (
            `option_id` int(10) unsigned not null auto_increment primary key,
            `orig_option_id` int(10) unsigned null,
            `product_id` int(10) unsigned not null,
            `store_id` smallint(5) unsigned not null,
            `configuration` mediumtext not null,
            CONSTRAINT `FK_ITORIS_DOPTIONS_OPTION_OPTION_ID` foreign key (`orig_option_id`) references {$setup->getTable('catalog_product_option')} (`option_id`) on delete cascade on update cascade,
            CONSTRAINT `FK_ITORIS_DOPTIONS_OPTION_PRODUCT_ID` foreign key (`product_id`) references {$setup->getTable('catalog_product_entity')} (`entity_id`) on delete cascade on update cascade,
            CONSTRAINT `FK_ITORIS_DOPTIONS_OPTION_STORE_ID` foreign key (`store_id`) references {$setup->getTable('store')} (`store_id`) on delete cascade on update cascade
        ) engine = InnoDB default charset = utf8;");

        $setup->run("
            create table {$setup->getTable('itoris_dynamicproductoptions_option_customergroup')} (
            `option_id` int(10) unsigned not null,
            `group_id` {$groupFkType} not null,
            CONSTRAINT `FK_ITORIS_DOPTIONS_OPTION_CUSTOMERGROUP_OPTION_ID` foreign key (`option_id`) references {$setup->getTable('itoris_dynamicproductoptions_option')} (`option_id`) on delete cascade on update cascade,
            CONSTRAINT `FK_ITORIS_DOPTIONS_OPTION_CUSTOMERGROUP_GROUP_ID` foreign key (`group_id`) references {$setup->getTable('customer_group')} (`customer_group_id`) on delete cascade on update cascade
        ) engine = InnoDB default charset = utf8;");

        $setup->run("
            create table {$setup->getTable('itoris_dynamicproductoptions_option_value')} (
            `value_id` int(10) unsigned not null auto_increment primary key,
            `orig_value_id` int(10) unsigned null,
            `product_id` int(10) unsigned not null,
            `store_id` smallint(5) unsigned not null,
            `configuration` text not null,
            CONSTRAINT `FK_ITORIS_DOPTIONS_VALUE_VALUE_ID` foreign key (`orig_value_id`) references {$setup->getTable('catalog_product_option_type_value')} (`option_type_id`) on delete cascade on update cascade,
            CONSTRAINT `FK_ITORIS_DOPTIONS_VALUE_PRODUCT_ID` foreign key (`product_id`) references {$setup->getTable('catalog_product_entity')} (`entity_id`) on delete cascade on update cascade,
            CONSTRAINT `FK_ITORIS_DOPTIONS_VALUE_STORE_ID` foreign key (`store_id`) references {$setup->getTable('store')} (`store_id`) on delete cascade on update cascade
        ) engine = InnoDB default charset = utf8;");

        $setup->run("
            create table {$setup->getTable('itoris_dynamicproductoptions_option_value_customergroup')} (
            `value_id` int(10) unsigned not null,
            `group_id` {$groupFkType} not null,
            CONSTRAINT `FK_ITORIS_DOPTIONS_VALUE_CUSTOMERGROUP_VALUE_ID` foreign key (`value_id`) references {$setup->getTable('itoris_dynamicproductoptions_option_value')} (`value_id`) on delete cascade on update cascade,
            CONSTRAINT `FK_ITORIS_DOPTIONS_VALUE_CUSTOMERGROUP_GROUP_ID` foreign key (`group_id`) references {$setup->getTable('customer_group')} (`customer_group_id`) on delete cascade on update cascade
        ) engine = InnoDB default charset = utf8;");
        $setup->endSetup();
    }
}