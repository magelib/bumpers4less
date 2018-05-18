<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Import\Behavior;

class Product extends \Magento\ImportExport\Model\Source\Import\AbstractBehavior
{
    const  ONLY_UPDATE = 'update';

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND => __('Add/Update'),
            self::ONLY_UPDATE => __('Only Update'),
            \Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE => __('Replace'),
            \Magento\ImportExport\Model\Import::BEHAVIOR_DELETE => __('Delete')
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCode()
    {
        return 'product';
    }

    /**
     * {@inheritdoc}
     */
    public function getNotes($entityCode)
    {
        $messages = ['catalog_product' => [
            \Magento\ImportExport\Model\Import::BEHAVIOR_REPLACE => __("Note: Product IDs will be regenerated.")
        ]];
        return isset($messages[$entityCode]) ? $messages[$entityCode] : [];
    }
}
