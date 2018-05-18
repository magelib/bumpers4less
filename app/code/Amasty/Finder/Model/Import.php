<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Model;
use Magento\Framework\Validator\Exception as ValidatorException;

class Import
{
    const CONFIG_MAX_LIMIT_IN_PART = 'amasty/import/limit';
    const MAX_ERRORS_IN_FILE = 1000;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->objectManager = $objectManager;
    }

    protected function _validateImportFile($fileLog)
    {
        if($fileLog->getIsLocked() == 1){
            return false;
        }

        if($fileLog->getStatus() == \Amasty\Finder\Model\ImportLog::STATE_UPLOADED) {
            $fileLog->setStartedAt(date('Y-m-d H:i:s'));
        }

        if($fileLog->getLastStartProcessingLine() != 0 && $fileLog->getLastStartProcessingLine() == $fileLog->getCountProcessingLines()) {
            $this->getErrorLog()->error($fileLog->getId(), 0, 'Error! File is executing the second time');
            $fileLog->error()->setEndedAt(date('Y-m-d H:i:s'))->save()->archive()->delete();
            return false;
        }

        if(!is_file($fileLog->getFilePath())) {
            $this->getErrorLog()->error($fileLog->getId(), 0, 'File not exists');
            $fileLog->error()->setEndedAt(date('Y-m-d H:i:s'))->save()->archive()->delete();
            return false;
        }
        return true;
    }

    protected function _validateRange($cnt, $fileLog, $currentLine)
    {
        $cntRange = 1;
        foreach($cnt as $count) {
            if($count) {
                $cntRange *= $count;
            }
        }

        if($cntRange >= $this->scopeConfig->getValue('amfinder/import/max_rows_per_import')) {
            $this->getErrorLog()->error($fileLog->getId(), $currentLine, 'Line #' . $currentLine . ' contains big range!');
            $fileLog->error();
            return false;
        }

        return true;
    }


    public function runFile($fileLog, &$countProcessedRows)
    {
        ini_set('auto_detect_line_endings', true);
        $fileName = $fileLog->getFileName();
        $finderId = $fileLog->getFinderId();
        $filePath = $fileLog->getFilePath();

        if(!$this->_validateImportFile($fileLog)) {
            return 0;
        }

        $fp = fopen($filePath, 'r');

        if (!$fp){
            $this->getErrorLog()->error($fileLog->getId(), 0, 'Can not open file');
            $fileLog->error()->setEndedAt(date('Y-m-d H:i:s'))->save()->archive()->delete();
            return 0;
        }



        $fileLog->setIsLocked(1);
        $fileLog->setLastStartProcessingLine($fileLog->getCountProcessingLines());
        if($fileLog->getStatus() == \Amasty\Finder\Model\ImportLog::STATE_UPLOADED) {
            $countLines = $this->countLines($fp);
            $fileLog->setCountLines($countLines);
            $fileLog->setStatus(\Amasty\Finder\Model\ImportLog::STATE_PROCESSING);
        }
        $fileLog->save();

        /**
         * @var Amasty_Finder_Model_Finder
         */
        $finder = $this->objectManager->create('Amasty\Finder\Model\Finder')->load($finderId);

        if(!$finder->getId()) {
            $this->getErrorLog()->error($fileLog->getId(), 0, 'Finder id '.$finderId.' does not exists');
            $fileLog->setIsLocked(0)->error()->save();
            return 0;
        }

        if($fileLog->getCountProcessingLines() == 0 && $fileName == 'replace.csv') {
            $this->clearOldData($finder);
        }



        $countProcessedRowsInCurrentFile = $fileLog->getCountProcessingRows();
        $countProcessedLinesInCurrentFile = $fileLog->getCountProcessingLines();
        for($i=1; $i <=$countProcessedLinesInCurrentFile; $i++) {
            fgets($fp);
        }


        //get dropdownds iDs as array
        $dropdowns = array();
        foreach ($finder->getDropdowns() as $d){
            $dropdowns[] = $d->getId();
            $ranges[] = $d->getRange();
        }
        $ranges[count($ranges)] = 0;

        $countDropDowns   = count($dropdowns);

        $names = $this->_parseFile($fp, $countProcessedRows, $countProcessedRowsInCurrentFile,$countProcessedLinesInCurrentFile, $countDropDowns, $fileLog, $ranges);
        $namesIndex = count($names);

        $fileLog->setCountProcessingRows($countProcessedRowsInCurrentFile);
        $fileLog->setCountProcessingLines($countProcessedLinesInCurrentFile);


        if($namesIndex) {
            $parents = $this->_insertValues($names, $dropdowns);
            $this->_createMap($parents, $names);
            $finder->updateLinks();
        }

        if($fileLog->getCountLines() == $countProcessedLinesInCurrentFile) {
            $fileLog->setEndedAt(date('Y-m-d H:i:s'))->archive()->delete();
        } else {
            $fileLog->setIsLocked(0)->save();
        }

        return $countProcessedRows;
    }

    protected function _parseFile($fp, &$countProcessedRows, &$countProcessedRowsInCurrentFile, &$countProcessedLinesInCurrentFile, $countDropDowns, $fileLog, $ranges)
    {
        // convert file portion to the matrix
        // validate and normalize strings
        $names      = array(); // matrix h=BATCH_SIZE, w=dropNum+1;
        $namesIndex = 0;

        // need to handle ranges
        $newIndex = array();
        $tempNames = array();

        while (($line = fgetcsv($fp, \Amasty\Finder\Model\ResourceModel\Finder::MAX_LINE, ',', '"')) !== false && $countProcessedRows < $this->scopeConfig->getValue('amfinder/import/max_rows_per_import')) {
            $countProcessedLinesInCurrentFile++;
            $countValuesInLine = count($line);
            if ($countValuesInLine != $countDropDowns+1 && $countValuesInLine > 1){
                $this->getErrorLog()->error(
                    $fileLog->getId(),
                    $countProcessedLinesInCurrentFile,
                    'Line #' . $countProcessedLinesInCurrentFile . ' has been skipped: expected number of columns is '.($countDropDowns+1)
                );
                $fileLog->error();
                continue;
            } elseif($countValuesInLine != $countDropDowns+1){
                continue;
            }

            $cnt = array();
            for ($i = 0; $i < $countDropDowns+1; $i++) {
                $line[$i] = trim($line[$i], "\r\n\t' ".'"');
                //$line[$i] = mb_convert_encoding($line[$i], "UTF-8", "CP1251");
                if (!$line[$i]){
                    $this->getErrorLog()->error(
                        $fileLog->getId(),
                        $countProcessedLinesInCurrentFile,
                        'Line #' . $countProcessedLinesInCurrentFile . ' contains empty columns. Possible error.'
                    );
                    $fileLog->error();
                }

                $match = array();
                if ($ranges[$i] && preg_match('/^(\d+)\-(\d+)$/', $line[$i], $match)){
                    $cnt[$i] = abs($match[1] - $match[2]);
                }
            }

            if(!$this->_validateRange($cnt, $fileLog, $countProcessedLinesInCurrentFile))
            {
                continue;
            }

            ///// ***************** START old import code ************************ ////
            for ($i = 0; $i < $countDropDowns+1; $i++) {

                $match = array();
                if ($ranges[$i] && preg_match('/^(\d+)\-(\d+)$/', $line[$i], $match)){

                    $cnt = abs($match[1] - $match[2]);
                    if ($cnt) {
                        $startValue = min($match[1], $match[2]);
                        for ($k = 0; $k < $cnt + 1; $k++){
                            $names[$namesIndex + $k][$i]     = $startValue + $k;
                            $tempNames[$namesIndex + $k][$i] = $startValue + $k;
                            $newIndex[$i] =  $namesIndex + $k;
                        }
                    }
                    else {
                        $this->getErrorLog()->error(
                            $fileLog->getId(),
                            $countProcessedLinesInCurrentFile,
                            'Line #' . $countProcessedLinesInCurrentFile . ' contains the same values for the range. Possible error.'
                        );
                        $fileLog->error();
                        $names[$namesIndex][$i] = $line[$i];
                        $newIndex[$i] = $namesIndex;
                    }

                }
                else {
                    $names[$namesIndex][$i] = $line[$i];
                    $newIndex[$i] = $namesIndex;
                }

            }

            // multiply rows with ranges
            $multiplierRange = 1;
            $flagRange       = false;

            for ($i = 0; $i < $countDropDowns+1; $i++) {
                if ($newIndex[$i] != $namesIndex){
                    $flagRange = true;
                    if (($newIndex[$i] - $namesIndex + 1) > 0){
                        $multiplierRange = $multiplierRange * ($newIndex[$i] - $namesIndex + 1);
                    }
                }
            }

            if ($flagRange){
                $currMultiply = $multiplierRange;
                for ($i = 0; $i < $countDropDowns+1; $i++) {
                    $currMultiply = intVal($currMultiply / ($newIndex[$i] - $namesIndex + 1));  // current multiplier for the column
                    for ($l = 0; $l < $multiplierRange; $l++){
                        $index = $namesIndex + intVal(( $l % ($currMultiply * ($newIndex[$i] - $namesIndex + 1)) )  / ($currMultiply));
                        if (isset($tempNames[$index][$i])){
                            $names[$namesIndex+$l][$i] = $tempNames[$index][$i];
                        }
                        else {
                            $names[$namesIndex+$l][$i] = $names[$index][$i];
                        }
                    }
                }
            }
            $namesIndex =  $namesIndex +  $multiplierRange;
            $tempNames  = array();

            $countProcessedRowsInCurrentFile += $multiplierRange;
            $countProcessedRows += $multiplierRange;
            ///// *****************  END old import code ************************ ////
        }

        return $names;
    }

    protected function _insertValues($names, $dropdowns)
    {
        $namesIndex = count($names);
        $countDropDowns = count($dropdowns);
        // like names, but
        // a) contains real IDs from db
        // b) has additional first column=0 as artificial parent_id for the frist dropdown
        // c) has no SKU
        // d) initilized by 0
        $parents = array_fill(0, $namesIndex, array_fill(0, $countDropDowns, 0));
        /**
         * @var $db \Magento\Framework\DB\Adapter\AdapterInterface
         */
        $db = $this->objectManager->create('Amasty\Finder\Model\ResourceModel\Finder')->getConnection();

        for ($j=0; $j < $countDropDowns; ++$j){ // columns
            $sql = 'INSERT IGNORE INTO `' . $this->getTable('amasty_finder_value') . '` (parent_id, dropdown_id, name) VALUES ';

            $insertedData = [];
            for ($i=0; $i < $namesIndex; ++$i) { //rows
                $names[$i][$j] = $this->cleanName($names[$i][$j]);
                $key = $parents[$i][$j] . '-' . $names[$i][$j];
                if (!isset($insertedData[$key])) {
                    $insertedData[$key] = $parents[$i][$j];
                    $sql .= '(' . $parents[$i][$j] . ','
                        . $dropdowns[$j] . ','
                        . $db->quote($names[$i][$j]) . "),";
                }
            }

            //insert current column
            $sql = substr($sql, 0, -1);

            $db->query($sql);

            // now we need to select just inserted data to get IDs
            // we can create long where statement or select a bit more data that we actually need.
            // we are implementing the second approach
            $affectedParents = array_keys(array_flip($insertedData));
            $key = new \Zend_Db_Expr('CONCAT(parent_id, "-", LOWER(name))');
            $sql = $db->select()
                ->from($this->getTable('amasty_finder_value'), array($key, 'value_id'))
                ->where('parent_id IN(?)', $affectedParents)
                ->where('dropdown_id = ?', $dropdowns[$j])
            ;

            $map = $db->fetchPairs($sql);

            for ($i=0; $i < $namesIndex; ++$i) { //rows
                $key = $parents[$i][$j] . '-' . $names[$i][$j];
                $parents[$i][$j+1] = $map[$key];
            }
        } //end columns

        return $parents;
    }

    protected function cleanName($name)
    {
        $name = mb_strtolower($name, 'UTF-8');
        $name = trim($name, '﻿'); //symbol with ascii code 92
        $name = trim($name);
        return $name;
    }

    protected function _createMap($listValues, $listSkus)
    {
        $db = $this->objectManager->create('Amasty\Finder\Model\ResourceModel\Finder')->getConnection();
        // now insert SKU as we know the last value_id
        $sql = 'INSERT IGNORE INTO `' . $this->getTable('amasty_finder_map') . '` (value_id, sku) VALUES ';
        $insertedData  = array();
        $namesIndex = count($listValues);
        for ($i=0; $i < $namesIndex; ++$i){
            $valueId = array_pop($listValues[$i]);
            $skus = explode(',', array_pop($listSkus[$i]));
            foreach($skus as $sku){
                $key = $valueId . '-' . $sku;
                if (!isset($insertedData[$key])){
                    $insertedData[$key] = 1;
                    $sql .= '(' . $valueId . "," . $db->quote($sku) . "),";
                }
            }
        }
        $sql = substr($sql, 0, -1);

        $db->query($sql);
    }



    public function runAll()
    {
        $dir = $this->objectManager->get('Amasty\Finder\Helper\Data')->getFtpImportDir();

        $finderIds = array();
        if($dirHandle = opendir($dir)) {
            while (false !== ($childrenDir = readdir($dirHandle))) {
                if(!is_dir($dir.$childrenDir) || intval($childrenDir) != $childrenDir) {
                    continue;
                }
                $finderIds[] = $childrenDir;
                //$this->loadNewFilesFromFtp($childrenDir);
            }
            closedir($dirHandle);
        }

        if(count($finderIds) > 0) {
            /**
             * @var $collectionFinder \Amasty\Finder\Model\ResourceModel\Finder\Collection
             */
            $collectionFinder = $this->objectManager->create('Amasty\Finder\Model\Finder')->getCollection();
            $collectionFinder->addFieldToFilter('finder_id', array('in'=>$finderIds));
            foreach ($collectionFinder as $finder) {
                $this->loadNewFilesFromFtp($finder->getId());
            }
        }


        /**
         * @var $collection \Amasty\Finder\Model\ResourceModel\ImportLog\Collection
         */
        $collection = $this->objectManager->create('Amasty\Finder\Model\ImportLog')->getCollection();
        $collection
            ->addFieldToFilter('is_locked', 0)
            ->orderForImport();

        $countProcessedRows = 0;
        foreach($collection as $fileLog) {
            $this->runFile($fileLog,$countProcessedRows);
            if($countProcessedRows >= $this->scopeConfig->getValue('amfinder/import/max_rows_per_import')){
                break;
            }
        }


    }

    public function getLog($fileName, $finderId)
    {
        return $this->objectManager->create('Amasty\Finder\Model\ImportLog')->loadByNameAndFinder($fileName, $finderId);
    }

    /**
     * @return \Amasty\Finder\Model\ImportErrors
     */
    public function getErrorLog()
    {
        return $this->objectManager->create('Amasty\Finder\Model\ImportErrors');
    }




    public function loadNewFilesFromFtp($finderId)
    {
        $dir = $this->objectManager->get('Amasty\Finder\Helper\Data')->getFtpImportDir().$finderId."/";
        if(!is_dir($dir)){
            return;
        }
        $hasDeleteAllFiles = false;
        $dirHandle = opendir($dir);
        while (false !== ($file = readdir($dirHandle))) {
            if(is_file($dir.$file) && $file != '..' && $file != '.') {
                $this->objectManager->create('Amasty\Finder\Model\ImportLog')->addUniqueFile($file, $finderId);
                if($file == 'replace.csv') {
                    $hasDeleteAllFiles = true;
                }
            }
        }
        closedir($dirHandle);

        if($hasDeleteAllFiles) {
            $this->objectManager->create('Amasty\Finder\Model\ImportLog')
                ->getCollection()
                ->addFieldToFilter('finder_id', $finderId)
                ->addFieldToFilter('file_name', array('neq'=>'replace.csv'))
                ->walk('delete');
        }
    }



    public function upload($fileField, $finderId, $fileName = null)
    {
        $dir = $this->objectManager->get('Amasty\Finder\Helper\Data')->getFtpImportDir().$finderId."/";

        /**
         * @var \Magento\Framework\File\Uploader
         */
        $uploader = $this->objectManager->create(
            'Magento\Framework\File\Uploader',
            ['fileId' => $fileField]
        );
        $uploader->setAllowedExtensions(['csv']);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(false);

        if($this->objectManager->create('Amasty\Finder\Model\ImportLog')->hasIssetReplaceFile($finderId)) {
            throw new ValidatorException(__('Upload is impossible, there is a file replace.csv'));
        }

        if(!is_null($fileName) && is_file($dir.$fileName)) {
            throw new ValidatorException(__('The file with the same name already exists! '.$fileName));
        }

        $result = $uploader->save($dir,$fileName);

        if(!$result) {
            throw new ValidatorException(__('Error occurred save file'));
        }

        $fileName = $uploader->getUploadedFileName();
        if(function_exists('mime_content_type')) {
            $mimeType = mime_content_type($dir.$fileName);
            if($mimeType != 'text/plain') {
                @unlink($dir.$fileName);
                throw new ValidatorException(__('Incorrect file type. CSV needed'));
            }
        }
        $this->loadNewFilesFromFtp($finderId);
        return $fileName;
    }


    /**
     * @param \Amasty\Finder\Model\Finder $finder
     */
    public function clearOldData(\Amasty\Finder\Model\Finder $finder)
    {
        $ids = array();
        foreach($finder->getDropdowns() as $dropdown) {
            $ids[] = $dropdown->getId();
        }

        $this->objectManager->create('Amasty\Finder\Model\ResourceModel\Value')->deleteValuesByDropDowns($ids);
    }

    public function countLines($fileHandle)
    {
        $i = 0;
        while(fgets($fileHandle) !== false){
            $i++;
        }

        rewind($fileHandle);

        return $i;
    }


    public function getTable($tableName)
    {
        return $this->objectManager->create('Amasty\Finder\Model\ResourceModel\ImportLog')->getTable($tableName);
    }


    public function afterDeleteFinder($finderId)
    {
        $collection = $this->objectManager->create('Amasty\Finder\Model\ImportLog')->getCollection()->addFieldToFilter('finder_id', $finderId);
        $collection->walk('delete');
        $collectionHistory = $this->objectManager->create('Amasty\Finder\Model\ImportHistory')->getCollection()->addFieldToFilter('finder_id', $finderId);
        $collectionHistory->walk('delete');
    }


}
