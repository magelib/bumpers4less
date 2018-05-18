<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;


class InstallSchema implements InstallSchemaInterface
{
    /**
     * @var \Amasty\Finder\Helper\Pub\Deploy
     */
    protected $deployHelper;

    public function __construct(\Amasty\Base\Helper\Deploy $deploy)
    {
        $this->deployHelper = $deploy;
    }


    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('amasty_finder_finder'))
            ->addColumn(
                'finder_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'cnt',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['default' => 0, 'nullable' => false],
                'Count dropdowns'
            )
            ->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => false],
                'Name'
            )
            ->addColumn(
                'template',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => false],
                'Template'
            )
        ->addColumn(
            'meta_title',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['default' => null, 'nullable' => false],
            'Meta Title'
        )
        ->addColumn(
            'meta_descr',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            null,
            ['default' => null, 'nullable' => false],
            'Meta description'
        )
        ->addColumn(
            'custom_url',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['default' => null, 'nullable' => false],
            'Custom url'
        );

        $installer->getConnection()->createTable($table);

        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('amasty_finder_dropdown'))
            ->addColumn(
                'dropdown_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'finder_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'pos',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => false],
                'name'
            )
            ->addColumn(
                'sort',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'range',
                \Magento\Framework\DB\Ddl\Table::TYPE_SMALLINT,
                null,
                ['nullable' => false]
            )
            ->addIndex('finder_id', 'finder_id')
            ->addForeignKey(
                $installer->getFkName(
                    'amasty_finder_dropdown',
                    'finder_id',
                    'amasty_finder_finder',
                    'finder_id'
                ),
                'finder_id',
                $installer->getTable('amasty_finder_finder'),
                'finder_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );

        $installer->getConnection()->createTable($table);


        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('amasty_finder_import_file_log'))
            ->addColumn(
                'file_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'file_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => false],
                'file_name'
            )
            ->addColumn(
                'finder_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'started_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'ended_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'count_lines',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default'=>0]
            )
            ->addColumn(
                'count_processing_lines',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default'=>0]
            )
            ->addColumn(
                'last_start_processing_line',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default'=>0]
            )
            ->addColumn(
                'count_processing_rows',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default'=>0]
            )
            ->addColumn(
                'count_errors',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default'=>0]
            )
            ->addColumn(
                'status',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default'=>0]
            )
            ->addColumn(
                'is_locked',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['unsigned' => true, 'nullable' => false, 'default'=>false]
            )
            ->addIndex('finder_file', ['file_name', 'finder_id'], ['type'=>\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE]);

        $installer->getConnection()->createTable($table);

        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('amasty_finder_import_file_log_history'))
            ->addColumn(
                'file_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'file_name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => false],
                'file_name'
            )
            ->addColumn(
                'finder_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'started_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'updated_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'ended_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'count_lines',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default'=>0]
            )
            ->addColumn(
                'count_processing_lines',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default'=>0]
            )
            ->addColumn(
                'count_processing_rows',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default'=>0]
            )
            ->addColumn(
                'count_errors',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default'=>0]
            );

        $installer->getConnection()->createTable($table);

        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('amasty_finder_import_file_log_errors'))
            ->addColumn(
                'error_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'import_file_log_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true]
            )
            ->addColumn(
                'import_file_log_history_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => true]
            )
            ->addColumn(
                'created_at',
                \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'line',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false, 'default'=>0]
            )
            ->addColumn(
                'message',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => false],
                'file_name'
            )
            ->addForeignKey(
                $installer->getFkName(
                    'amasty_finder_import_file_log_errors',
                    'import_file_log_id',
                    'amasty_finder_import_file_log',
                    'file_id'
                ),
                'import_file_log_id',
                $installer->getTable('amasty_finder_import_file_log'),
                'file_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->addForeignKey(
                $installer->getFkName(
                    'amasty_finder_import_file_log_errors',
                    'import_file_log_history_id',
                    'amasty_finder_import_file_log_history',
                    'file_id'
                ),
                'import_file_log_history_id',
                $installer->getTable('amasty_finder_import_file_log_history'),
                'file_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );


        $installer->getConnection()->createTable($table);




        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('amasty_finder_map'))
            ->addColumn(
                'id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'value_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'pid',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'sku',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => false]
            )
            ->addIndex('map_uniq', ['value_id', 'sku'], ['type'=>\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE])
            ->addForeignKey(
                $installer->getFkName(
                    'amasty_finder_map',
                    'value_id',
                    'amasty_finder_value',
                    'value_id'
                ),
                'value_id',
                $installer->getTable('amasty_finder_value'),
                'value_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );

        $installer->getConnection()->createTable($table);

        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('amasty_finder_universal'))
            ->addColumn(
                'universal_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'finder_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'sku',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => false]
            )
            ->addColumn(
                'pid',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addIndex('FK_DROPODOWN_UNIVERSAL', ['finder_id'], ['type'=>\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX])
            ->addForeignKey(
                $installer->getFkName(
                    'amasty_finder_universal',
                    'finder_id',
                    'amasty_finder_finder',
                    'finder_id'
                ),
                'finder_id',
                $installer->getTable('amasty_finder_finder'),
                'finder_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );

        $installer->getConnection()->createTable($table);

        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('amasty_finder_value'))
            ->addColumn(
                'value_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'parent_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'dropdown_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => false]
            )
            ->addIndex('value_uniq', ['parent_id', 'dropdown_id', 'name'], ['type'=>\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_UNIQUE])
            ->addIndex('FK_VALUE_DROPDOWN', ['dropdown_id'], ['type'=>\Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX])
            ->addForeignKey(
                $installer->getFkName(
                    'amasty_finder_value',
                    'dropdown_id',
                    'amasty_finder_dropdown',
                    'dropdown_id'
                ),
                'dropdown_id',
                $installer->getTable('amasty_finder_dropdown'),
                'dropdown_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );

        $installer->getConnection()->createTable($table);

        $this->deployHelper->deployFolder(dirname(__DIR__) . '/pub');

        $installer->endSetup();
    }
}
