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


class Value extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    /**
     * Model Initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('amasty_finder_value', 'value_id');
    }


    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array                                     $data
     *
     * @return mixed
     */
    public function saveNewFinder(\Magento\Framework\ObjectManagerInterface $objectManager, array $data)
    {
        $db = $this->getConnection();
        $insertData = [];
        $parentId = 0;
        foreach ($data as $element => $value)
        {
            if (substr($element, 0, 6) == 'label_')
            {
                $insertData[] = ['dropdown_id' => substr($element,6), 'name' => $value];
            }

        }

        foreach ($insertData as $key => $row) {
            $name[$key]  = $row['name'];
            $dropdown_id[$key] = $row['dropdown_id'];
        }
        array_multisort($dropdown_id, SORT_ASC, $name, SORT_ASC, $insertData);

        foreach ($insertData as $insertElement){

            $sql = 'INSERT IGNORE INTO `' . $this->getTable('amasty_finder_value') . "` (parent_id, dropdown_id, name) VALUES ('"
                .$parentId."','".$insertElement['dropdown_id']."',".$db->quote($insertElement['name']).")";
            $db->query($sql);
            $selectSql = $db->select()
                ->from($this->getTable('amasty_finder_value'))
                ->where('dropdown_id =?',$insertElement['dropdown_id'])
                ->where('parent_id =?',$parentId)
                ->where('name =?',$insertElement['name']);
            $result = $db->fetchRow($selectSql) ;

            $parentId =  $result['value_id'];
        }

        $sql = 'INSERT IGNORE INTO `' . $this->getTable('amasty_finder_map') . "` (`value_id`, `sku`) VALUES ('"
            .$parentId."',".$db->quote($data['sku']).")";

        $db->query($sql);
        $objectManager->create('Amasty\Finder\Model\Finder')->updateLinks();
        $dropdown = $objectManager->create('Amasty\Finder\Model\Dropdown')->load($insertElement['dropdown_id']);
        $finderId = $dropdown->getFinderId();

        return $finderId;

    }


    public function getSkuById($newId, $id)
    {
        $db = $this->getConnection();
        $selectSql = $db->select()
            ->from($this->getTable('amasty_finder_map'))
            ->where('value_id = ?',$id)
            ->where('id = ?',$newId);
        $result = $db->fetchRow($selectSql) ;
        return $result['sku'];
    }

    public function deleteValuesByDropDowns($dropDownIds)
    {
        $db = $this->getConnection();
        $ids = implode(',', $dropDownIds);
        $db->delete($this->getMainTable(), "dropdown_id IN ($ids)");
    }
}
