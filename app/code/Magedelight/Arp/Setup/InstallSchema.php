<?php
/* Magedelight
 * Copyright (C) 2016 Magedelight <info@magedelight.com>
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
 * @package Magedelight_Faqs
 * @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 * 
 */
namespace Magedelight\Arp\Setup;
 
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;


 
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
     // @codingStandardsIgnoreStart
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {    // @codingStandardsIgnoreEnd
        $installer = $setup;
        $installer->startSetup();
        $tableName = $installer->getTable('md_advance_product_rule');
        $tableComment = 'Advance product management';
        $columns = [
            'rule_id' => [
                'type' => Table::TYPE_INTEGER,
                'size' => null,
                'options' => ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
                'comment' => 'Rule Id',
            ],
            'name' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Name',
            ],
            'block_page' => [
                'type' => Table::TYPE_BOOLEAN,
                'size' => null,
                'options' => ['nullable' => false, 'default' => 0],
                'comment' => 'Block Page',
            ],
            'block_position' => [
                'type' => Table::TYPE_BOOLEAN,
                'size' => null,
                'options' => ['nullable' => false, 'default' => 0],
                'comment' => 'Block Position',
            ],
            'store_id' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Store Id',
            ],
            'customer_groups' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Customer Groups',
            ],
            'priority' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Priority',
            ],
            'status' => [
                    'type' => Table::TYPE_BOOLEAN,
                    'size' => null,
                    'options' => ['nullable' => false, 'default' => 0],
                    'comment' => 'Status',
            ],
            'block_title' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Block Title',
            ],
            'block_layout' => [
                'type' => Table::TYPE_BOOLEAN,
                'size' => null,
                'options' => ['nullable' => false, 'default' => 0],
                'comment' => 'Block Layout',
            ],
            'number_of_rows' => [
                'type' => Table::TYPE_INTEGER,
                'size' => null,
                'options' => ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'comment' => 'Number of rows',
            ],
            'views' => [
                'type' => Table::TYPE_INTEGER,
                'size' => null,
                'options' => ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'comment' => 'Number of views',
            ],
            'clicks' => [
                'type' => Table::TYPE_INTEGER,
                'size' => null,
                'options' => ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'comment' => 'Number of clicks',
            ],
            'page_visitor' => [
                'type' => Table::TYPE_INTEGER,
                'size' => null,
                'options' => ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'comment' => 'Number of page visitor',
            ],
            'display_cart_button' => [
                'type' => Table::TYPE_BOOLEAN,
                'size' => null,
                'options' => ['nullable' => false, 'default' => 0],
                'comment' => 'Display "Add To Cart" button',
            ],
            'max_products_display' => [
                'type' => Table::TYPE_INTEGER,
                'size' => null,
                'options' => ['unsigned' => true, 'nullable' => false, 'default' => 0],
                'comment' => 'Max products to display',
            ],
            'slides_to_scroll' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Block Title',
            ],
            'slides_to_show' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Block Title',
            ],
            'sort_by' => [
                'type' => Table::TYPE_TEXT,
                'size' => 255,
                'options' => ['nullable' => false, 'default' => ''],
                'comment' => 'Sort By',
            ],
            'conditions_serialized' => [
                'type' => Table::TYPE_TEXT,
                'size' => null,
                'options' => ['nullable' => false, 'default' => null],
                'comment' => 'Conditions for where to display',
            ],
            'products_ids_conditions' => [
                'type' => Table::TYPE_TEXT,
                'size' => 2048,
                'options' => ['nullable' => false, 'default' => null],
                'comment' => 'product ids for where to display',
            ],
            'actions_serialized' => [
                'type' => Table::TYPE_TEXT,
                'size' => 2048,
                'options' => ['nullable' => false, 'default' => null],
                'comment' => 'Conditions for what to display',
            ],
            'products_ids_actions' => [
                'type' => Table::TYPE_TEXT,
                'size' => 2048,
                'options' => ['nullable' => false, 'default' => null],
                'comment' => 'product ids for what to display',
            ],
            'products_category' => [
                'type' => Table::TYPE_TEXT,
                'size' => 2048,
                'options' => ['nullable' => false, 'default' => null],
                'comment' => 'Sort Order',
            ],
            'cms_page' => [
                'type' => Table::TYPE_TEXT,
                'size' => 2048,
                'options' => ['nullable' => false, 'default' => null],
                'comment' => 'Sort Order',
            ],
            'creation_time' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    '2M',
                    'nullable' => false,
                    'size' => null,
                    'options'=>  [
                        'nullable' => false,
                        'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT
                    ],
                    'comment' => 'Question Creation Time',
            ],
            'update_time' => [
                        'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                        '2M',
                        'nullable' => false,
                        'size' => null,
                        'options'=> [
                            'nullable' => false,
                            'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE
                        ],
                        'comment' => 'Question Modification Time',
            ]
        ];
        
        $table = $installer->getConnection()->newTable($tableName);
        // Columns creation
        foreach ($columns as $name => $values) {
            $table->addColumn(
                $name,
                $values['type'],
                $values['size'],
                $values['options'],
                $values['comment']
            );
        }
        $table->addIndex(
            $installer->getIdxName($tableName, ['rule_id']),
            ['rule_id']
        )->addIndex(
            $setup->getIdxName($tableName, ['block_title'], AdapterInterface::INDEX_TYPE_FULLTEXT),
            ['block_title'],
            ['type' => AdapterInterface::INDEX_TYPE_FULLTEXT]
        );
        // Table comment
        $table->setComment($tableComment);
        $installer->getConnection()->createTable($table);
        // End Setup
        $installer->endSetup();
    }
}
