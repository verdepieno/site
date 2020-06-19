<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Shiprules
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Shiprules\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;


class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('amasty_shiprules_rule'))
            ->addColumn(
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'is_active',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'calc',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'discount_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'ignore_promo',
                \Magento\Framework\DB\Ddl\Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false, 'unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'pos',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'price_from',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,2',
                ['nullable' => false, 'unsigned' => true, 'default' => '0.00']
            )
            ->addColumn(
                'price_to',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,2',
                ['nullable' => false, 'unsigned' => true, 'default' => '0.00']
            )
            ->addColumn(
                'weight_from',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                ['nullable' => false, 'unsigned' => true, 'default' => '0.0000']
            )
            ->addColumn(
                'weight_to',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,4',
                ['nullable' => false, 'unsigned' => true, 'default' => '0.0000']
            )
            ->addColumn(
                'qty_from',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'qty_to',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'rate_base',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,2',
                ['nullable' => false, 'unsigned' => true, 'default' => '0.00']
            )
            ->addColumn(
                'rate_fixed',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,2',
                ['nullable' => false, 'unsigned' => true, 'default' => '0.00']
            )
            ->addColumn(
                'weight_fixed',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,2',
                ['nullable' => false, 'unsigned' => true, 'default' => '0.00']
            )
            ->addColumn(
                'rate_percent',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                '12,2',
                ['nullable' => false, 'unsigned' => true, 'default' => 0]
            )
            ->addColumn(
                'rate_min',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,2',
                ['nullable' => false, 'unsigned' => true, 'default' => '0.00']
            )
            ->addColumn(
                'rate_max',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,2',
                ['nullable' => false, 'unsigned' => true, 'default' => '0.00']
            )
            ->addColumn(
                'ship_min',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,2',
                ['nullable' => false, 'unsigned' => true, 'default' => '0.00']
            )
            ->addColumn(
                'ship_max',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                '12,2',
                ['nullable' => false, 'unsigned' => true, 'default' => '0.00']
            )
            ->addColumn(
                'handling',
                \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
                null,
                ['nullable' => false]
            )
            ->addColumn(
                'name',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => false]
            )
            ->addColumn(
                'days',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => '', 'nullable' => false]
            )
            ->addColumn(
                'stores',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => '', 'nullable' => false]
            )
            ->addColumn(
                'cust_groups',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => '', 'nullable' => false]
            )
            ->addColumn(
                'carriers',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'methods',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'coupon',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'conditions_serialized',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => true]
            )
            ->addColumn(
                'actions_serialized',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                null,
                ['default' => null, 'nullable' => true]
            );

        $installer->getConnection()->createTable($table);

        $table = $installer
            ->getConnection()
            ->newTable($installer->getTable('amasty_shiprules_attribute'))
            ->addColumn(
                'attr_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true]
            )
            ->addColumn(
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                null,
                ['unsigned' => true, 'nullable' => false]
            )
            ->addColumn(
                'code',
                \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                255,
                ['default' => null, 'nullable' => true]
            )
            ->addIndex('rule_id', 'rule_id')
            ->addForeignKey(
                $installer->getFkName(
                    'amasty_shiprules_attribute',
                    'rule_id',
                    'amasty_shiprules_rule',
                    'rule_id'
                ),
                'rule_id',
                $installer->getTable('amasty_shiprules_rule'),
                'rule_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_CASCADE
            );

        $installer->getConnection()->createTable($table);

        $installer->endSetup();
    }
}