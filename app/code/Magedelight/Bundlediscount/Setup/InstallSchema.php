<?php

/**
 * Magedelight
 * Copyright (C) 2016 Magedelight <info@magedelight.com>.
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @category Magedelight
 *
 * @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 */

namespace Magedelight\Bundlediscount\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\InstallSchemaInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $table1 = $installer->getConnection()->newTable(
                        $installer->getTable('md_bundlediscount_bundles')
                )->addColumn(
                        'bundle_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['identity' => true, 'nullable' => false, 'primary' => true], 'Bundle Id'
                )->addColumn(
                        'product_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, '10', ['unsigned' => true, 'nullable' => true], 'Product ID'
                )->addColumn(
                        'name', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, '255', [], 'Name'
                )->addColumn(
                        'discount_type', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, '5', ['unsigned' => true, 'nullable' => true, 'default' => 0], 'Discount Type'
                )->addColumn(
                        'discount_price', \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL, '12,4', [], 'Discount Price'
                )->addColumn(
                        'status', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, '5', ['unsigned' => true, 'nullable' => true, 'default' => 1], 'Status'
                )->addColumn(
                        'exclude_base_product', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, '5', ['unsigned' => true, 'nullable' => true, 'default' => 0], 'Exclude Base Product'
                )->addColumn(
                        'sort_order', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, '5', ['unsigned' => true, 'nullable' => true, 'default' => 0], 'Sort Order'
                )->addColumn(
                        'store_ids', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, '255', [], 'Store Ids'
                )->addColumn(
                        'customer_groups', \Magento\Framework\DB\Ddl\Table::TYPE_TEXT, '255', [], 'Customer Groups'
                )->addColumn(
                        'created_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT], 'Creation Time'
                )->addColumn(
                        'updated_at', \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP, null, ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE], 'Update Time'
                )->addColumn(
                        'qty', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, '5', ['unsigned' => true, 'nullable' => true, 'default' => 1], 'Quantity'
                )->addColumn(
                        'date_from', \Magento\Framework\DB\Ddl\Table::TYPE_DATE, null, ['nullable' => true, 'default' => null], 'From date'
                )->addColumn(
                'date_to', \Magento\Framework\DB\Ddl\Table::TYPE_DATE, null, ['nullable' => true, 'default' => null], 'To date'
        );

        $installer->getConnection()->createTable($table1);

        $table2 = $installer->getConnection()->newTable(
                        $installer->getTable('md_bundlediscount_items')
                )->addColumn(
                        'item_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, null, ['identity' => true, 'nullable' => false, 'primary' => true], 'Item Id'
                )->addColumn(
                        'bundle_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, '10', ['unsigned' => true, 'nullable' => true], 'Bundle ID'
                )->addColumn(
                        'product_id', \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER, '10', ['unsigned' => true, 'nullable' => true], 'Product ID'
                )->addColumn(
                        'qty', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, '5', ['unsigned' => true, 'nullable' => true, 'default' => 1], 'Quantity'
                )->addColumn(
                'sort_order', \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT, '5', ['unsigned' => true, 'nullable' => true, 'default' => 0], 'Sort Order'
        );
        $installer->getConnection()->createTable($table2);

        $installer->getConnection()->addColumn(
                $setup->getTable('quote'), 'bundle_ids', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            'length' => '12,4',
            'nullable' => false,
            'comment' => 'Bundle ids',
                ]
        );

        $installer->getConnection()->addColumn(
                $setup->getTable('quote'), 'bundle_discount_amount', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => 32,
            'nullable' => false,
            'default' => '0.0000',
            'comment' => 'Bundle Discount Amount',
                ]
        );

        $installer->getConnection()->addColumn(
                $setup->getTable('quote'), 'base_bundle_discount_amount', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => 32,
            'nullable' => false,
            'default' => '0.0000',
            'comment' => 'Base Bundle Discount Amount',
                ]
        );

        $installer->getConnection()->addColumn(
                $setup->getTable('sales_order'), 'bundle_discount_amount', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => 32,
            'nullable' => false,
            'default' => '0.0000',
            'comment' => 'Bundle Discount Amount',
                ]
        );

        $installer->getConnection()->addColumn(
                $setup->getTable('sales_order'), 'base_bundle_discount_amount', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => 32,
            'nullable' => false,
            'default' => '0.0000',
            'comment' => 'Base Bundle Discount Amount',
                ]
        );

        $installer->getConnection()->addColumn(
                $setup->getTable('sales_invoice'), 'bundle_discount_amount', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => 32,
            'nullable' => false,
            'default' => '0.0000',
            'comment' => 'Bundle Discount Amount',
                ]
        );

        $installer->getConnection()->addColumn(
                $setup->getTable('sales_invoice'), 'base_bundle_discount_amount', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => 32,
            'nullable' => false,
            'default' => '0.0000',
            'comment' => 'Base Bundle Discount Amount',
                ]
        );

        $installer->getConnection()->addColumn(
                $setup->getTable('sales_creditmemo'), 'bundle_discount_amount', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => 32,
            'nullable' => false,
            'default' => '0.0000',
            'comment' => 'Bundle Discount Amount',
                ]
        );

        $installer->getConnection()->addColumn(
                $setup->getTable('sales_creditmemo'), 'base_bundle_discount_amount', [
            'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
            'length' => 32,
            'nullable' => false,
            'default' => '0.0000',
            'comment' => 'Base Bundle Discount Amount',
                ]
        );

        $connection = $installer->getConnection();

        $installer->endSetup();
    }
}
