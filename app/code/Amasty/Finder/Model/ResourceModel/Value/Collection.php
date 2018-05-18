<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */
namespace Amasty\Finder\Model\ResourceModel\Value;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Amasty\Finder\Model\Value', 'Amasty\Finder\Model\ResourceModel\Value');
    }

    public function joinAllFor(\Amasty\Finder\Model\Finder $finder)
    {
        $select = $this->getSelect();
        $select->reset(\Zend_Db_Select::FROM);
        $select->reset(\Zend_Db_Select::COLUMNS);

        $i=0;
        foreach ($finder->getDropdowns() as $d) {
            $i = $d->getPos();

            $table  = ["d$i" => $this->getTable('amasty_finder_value')];
            $fields = ["name$i" => "d$i.name"];
            if (0 == $i) {
                $select->from($table, $fields);
                $select->where("d$i.dropdown_id=" . $d->getId() );
            }
            else {
                $bind = "d$i.parent_id = d".($i-1).".value_id";
                $select->joinInner($table, $bind, $fields);
            }

        }

        $select->joinInner(
            array('m'=>$this->getTable('amasty_finder_map')),
            "d$i.value_id = m.value_id",
            array('sku', 'val'=> 'm.value_id', 'vid'=>'m.id', 'finder_id'=>new \Zend_Db_Expr($finder->getId()))
        );

        return $this;
    }
}
