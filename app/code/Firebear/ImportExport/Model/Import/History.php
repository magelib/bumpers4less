<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Magento\Framework\Model\AbstractModel;
use Firebear\ImportExport\Api\Data\ImportHistoryInterface;

/**
 * ImportExport job model
 *
 */
class History extends AbstractModel implements ImportHistoryInterface
{
    protected function _construct()
    {
        $this->_init('Firebear\ImportExport\Model\ResourceModel\Import\History');
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->getData(self::HISTORY_ID);
    }

    /**
     * @return int
     */
    public function getJobId()
    {
        return $this->getData(self::JOB_ID);
    }

    /**
     * @return date
     */
    public function getStartedAt()
    {
        return $this->getData(self::STARTED_AT);
    }

    /**
     * @return date
     */
    public function getFinishedAt()
    {
        return $this->getData(self::FINISHED_AT);
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->getData(self::FILE);
    }

    /**
     * @param int $id
     *
     * @return ImportHistoryInterface
     */
    public function setId($id)
    {
        $this->setData(self::HISTORY_ID, $id);

        return $this;
    }

    /**
     * @param int $jobId
     *
     * @return ImportHistoryInterface
     */
    public function setJobId($jobId)
    {
        $this->setData(self::JOB_ID, $jobId);

        return $this;
    }

    /**
     * @param date $start
     *
     * @return ImportHistoryInterface
     */
    public function setStartedAt($start)
    {
        $this->setData(self::STARTED_AT, $start);

        return $this;
    }

    /**
     * @param date $finish
     *
     * @return ImportHistoryInterface
     */
    public function setFinishedAt($finish)
    {
        $this->setData(self::FINISHED_AT, $finish);

        return $this;
    }

    /**
     * @param string $type
     *
     * @return ImportHistoryInterface
     */
    public function setType($type)
    {
        $this->setData(self::TYPE, $type);

        return $this;
    }

    /**
     * @param string $file
     *
     * @return ImportHistoryInterface
     */
    public function setFile($file)
    {
        $this->setData(self::FILE, $file);

        return $this;
    }
}
