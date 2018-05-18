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


class ImportLog extends \Amasty\Finder\Model\Import\ImportLogAbstract
{
    const STATE_UPLOADED = 0;
    const STATE_PROCESSING = 1;

    const FILE_STATE = 'processing';

    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->_init('Amasty\Finder\Model\ResourceModel\ImportLog');

    }



    public function loadByNameAndFinder($fileName, $finderId)
    {
        return $this->getCollection()->addFieldToFilter('file_name', $fileName)->addFieldToFilter('finder_id', $finderId)->getFirstItem();
    }

    protected function _beforeSave()
    {
        $this->setUpdatedAt(date('Y-m-d H:i:s'));
        return parent::_beforeSave();
    }


    public function addUniqueFile($fileName, $finderId)
    {
        $this->getResource()->addUniqueFile($fileName, $finderId);
    }

    public function getState()
    {
        if($this->getStatus() == self::STATE_UPLOADED) {
            $state = __('Uploaded');
        } else {
            $state = __('Processing');
            $state .= " ".$this->getCountProcessingLines() . " " . __('lines of') . " " . $this->getCountLines() . ".";
            if($this->getCountErrors() > 0) {

                $state .= " " . $this->getCountErrors() . " " . __('errors') . ".";
            }

        }
        return $state;
    }

    public function isProcessing()
    {
        return $this->getStatus() == self::STATE_PROCESSING;
    }



    public function afterDelete()
    {
        $filePath = $this->getFilePath();
        if(is_file($filePath)) {
            unlink($filePath);
        }
        return parent::afterDelete();
    }

    public function getFileState()
    {
        return \Amasty\Finder\Helper\Data::FILE_STATE_PROCESSING;
    }

    public function getFieldInErrorLog()
    {
        return 'import_file_log_id';
    }


    public function archive()
    {
        $data = $this->getData();
        $data['file_id'] = null;
        $fileLogHistory = $this->objectManager->create('Amasty\Finder\Model\ImportHistory');
        $fileLogHistory->setData($data);
        $fileLogHistory->save();
        $this->setFileLogHistoryId($fileLogHistory->getId());
        $this->objectManager->create('Amasty\Finder\Model\ImportErrors')->archiveErrorHistory($this->getId(), $fileLogHistory->getId());


        $filePath = $this->getFilePath();
        $newFilePath = $this->objectManager->get('Amasty\Finder\Helper\Data')->getImportArchiveDir().$fileLogHistory->getId().".csv";
        if(is_file($filePath)) {
            rename($filePath, $newFilePath);
        }

        return $this;
    }

    public function getFilePath()
    {
        return $this->objectManager->get('Amasty\Finder\Helper\Data')->getFtpImportDir().$this->getFinderId().'/'.$this->getFileName();
    }

    public function getProgress()
    {
        return ($this->getCountLines()) ? floor($this->getCountProcessingLines()/$this->getCountLines() * 100) : 100;
    }

    public function error()
    {
        $this->setCountErrors($this->getCountErrors()+1);
        return $this;
    }

    public function getMode()
    {
        if($this->getFileName() == 'replace.csv') {
            return __('Replace Products');
        }
        return __('Add Products');
    }


    public function hasIssetReplaceFile($finderId)
    {
        return $this->getResource()->hasIssetReplaceFile($finderId);
    }
}
