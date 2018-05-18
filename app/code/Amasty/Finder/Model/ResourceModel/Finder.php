<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Model\ResourceModel;

class Finder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    const MAX_LINE   = 2000;
    const BATCH_SIZE = 1000;


    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('amasty_finder_finder', 'finder_id');
    }

    public function importUniversal($finder, $file)
    {
        $listErrors = [];
        $db = $this->getConnection();
        $id = intVal($finder->getId());


        if (empty($file['name'])){
            if ($finder->getData('importuniversal_clear') && $id){
                $db->delete($this->getTable('amasty_finder_universal'), "finder_id = $id");
            }
            return $listErrors; //ok, no data
        }

        $fileName = $file['tmp_name'];
        $fileNamePart = pathinfo($file['name']);
        if(function_exists('mime_content_type')) {
            $mimeType = mime_content_type($fileName);
        } else {
            $mimeType = 'text/plain';
        }
        if($fileNamePart['extension'] != 'csv' || $mimeType != 'text/plain') {
            throw new \Magento\Framework\Exception\LocalizedException(__('Incorrect file type. CSV needed'));
        }

        //for Mac OS
        ini_set('auto_detect_line_endings', 1);

        //file can be very big, so we read it by small chunks
        $fp = fopen($fileName, 'r');
        if (!$fp){
            throw new \Exception('Can not open file');
        }

        if ($finder->getData('importuniversal_clear') && $id){
            $db->delete($this->getTable('amasty_finder_universal'), "finder_id = $id");
        }

        $currRow = 0;
        $line = true;
        while (($line = fgetcsv($fp, self::MAX_LINE, ',', '"')) !== false) {
            $i = 0;

            $sql = 'INSERT IGNORE INTO `' . $this->getTable('amasty_finder_universal') . '` (finder_id, sku, pid) VALUES ';
            foreach($line as $sku){
                $sku = trim($sku, "\r\n\t' ".'"');
                $sku = $db->quote($sku);
                $sql .= '("'.$id.'","'.trim($sku, "\r\n\t' ".'"').'","0"),';
            }
            $sql = substr($sql, 0, -1);
            $db->query($sql);

        }

        $t1 = $this->getTable('amasty_finder_universal');
        $t2 = $this->getTable('catalog_product_entity');

        $sql = "UPDATE $t1, $t2  SET pid = entity_id WHERE $t1.sku=$t2.sku";
        $db->query($sql);

        return $listErrors;
    }


    public function newSetterId($id)
    {
        $db = $this->getConnection();
        $table = $this->getTable('amasty_finder_map');
        $selectSql = $db->select()
            ->from($table)
            ->where('id = ?',$id);
        $result = $db->fetchRow($selectSql) ;
        return $result['value_id'];
    }

    public function deleteMapRow($id)
    {
        $db = $this->getConnection();
        $table = $this->getTable('amasty_finder_map');
        $sql = "DELETE FROM $table WHERE `id` = $id";
        $db->query($sql);
        return true;
    }


    public function updateLinks()
    {
        $db = $this->getConnection();
        $t1 = $this->getTable('amasty_finder_map');
        $t2 = $this->getTable('catalog_product_entity');

        if (false){ // set it to true to update parent products as well
            $relation = $this->getTable('catalog_product_relation');
            $entity = $this->getTable('catalog_product_entity');

            $sql = "
			insert ignore into $t1 (pid, value_id, sku)
			select parent_id, value_id, sku

			from $relation rel

			inner join
			(SELECT entity_id, value_id
			FROM `$entity` e
			inner join
			(select value_id, sku
			from $t1) map on map.sku = e.sku)
			entities on entities.entity_id = rel.child_id

			left join $entity et on rel.parent_id = et.entity_id

			group by parent_id, value_id, sku";

            $db->query($sql);
        }

        $sql = "UPDATE $t1, $t2 SET pid = entity_id WHERE $t1.sku=$t2.sku";
        $db->query($sql);

        $sql = $db->select()
            ->from($t1, array('sku'))
            ->where('pid=0')
            ->limit(10);
        return $db->fetchCol($sql);
    }


    public function isDeletable($id)
    {
        $db = $this->getConnection();
        $table = $this->getTable('amasty_finder_map');
        $selectSql = $db->select()
            ->from($table)
            ->where('value_id = ?',$id);
        $result = $db->fetchRow($selectSql) ;
        //die($selectSql->__toString());
        if (isset($result['value_id']))
        {
            if ($result['value_id'])
            {
                //die("!!!");
                return false;
            }
        }

        $table2 = $this->getTable('amasty_finder_value');
        $selectSql2 = $db->select()
            ->from($table2)
            ->where('parent_id = ?',$id);

        $result2 = $db->fetchRow($selectSql2) ;
        if (isset($result2['value_id']))
        {
            if ($result2['value_id'])
            {

                return false;
            }
        }
        return true;
    }



    public function addConditionToProductCollection($collection, $valueId, $countOfEmptyDropdowns, $finderId, $isUniversal, $isUniversalLast)
    {
        /** @var \Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection $collection */

        $db = $this->getConnection();

        $ids = array($valueId);
        for ($i = 0; $i < $countOfEmptyDropdowns; $i++){
            $selectChild = $db->select()
                ->from(array('vf'=>$collection->getTable('amasty_finder_value')), 'value_id')
                ->where('vf.parent_id IN (?)', $ids);
            $ids = $db->fetchCol($selectChild);
        }

        $select = $collection->getSelect();

        if ($isUniversal){
            // we need sub selects
            $univProducts = $db->select()
                ->from(array('fu' => $collection->getTable('amasty_finder_universal')), ['id'=>'universal_id', 'sku'])
                ->where('fu.finder_id = ?', $finderId);

            $productIdsSelect =  $db->select()
                ->from(array('fm' => $collection->getTable('amasty_finder_map')), ['pid', 'sku'])
                ->where('fm.value_id IN (?)', $ids);

            $allProducts = $db->select()
                ->union(array($univProducts, $productIdsSelect));

            $query = $db->select()->from($allProducts, ['id', 'sku']);

            $entityIds = $db->fetchPairs($query);

            $collection->addFieldToFilter('sku', $entityIds);

            if ($isUniversalLast) {
                $select->distinct()
                    ->joinLeft(
                        array('fu' => $collection->getTable('amasty_finder_universal')),
                        'fu.pid = e.entity_id',
                        array()
                    )
                    ->order('fu.pid ASC')
                ;
            }
        }
        else {
            $entityIds = $db->fetchPairs($db->select()->from($collection->getTable('amasty_finder_map'), ['id','sku'])->where('value_id IN(?)', $ids));
            $collection->addFieldToFilter('sku', $entityIds);
        }

        return true;
    }
}
