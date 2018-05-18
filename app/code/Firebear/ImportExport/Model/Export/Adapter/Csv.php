<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Adapter;

class Csv extends \Magento\ImportExport\Model\Export\Adapter\Csv
{
    /**
     * @param $del
     */
    public function setDelimeter($del)
    {
        $this->_delimiter = $del;
    }

    /**
     * @param $enc
     */
    public function setEnclosure($enc)
    {
        $this->_enclosure = $enc;
    }

    public function writeRow(array $rowData)
    {
        foreach ($rowData as &$value) {
            if (substr_count($value, PHP_EOL) > 0) {
                $value = str_replace(PHP_EOL, "", $value);
            }
        }
        if (null === $this->_headerCols) {
            $this->setHeaderCols(array_keys($rowData));
        }
        if (null === $this->_headerCols) {
            $this->_headerCols = [];
        }
        $this->_fileHandler->writeCsv(
            array_merge($this->_headerCols, array_intersect_key($rowData, $this->_headerCols)),
            $this->_delimiter,
            $this->_enclosure
        );
        return $this;
    }
}
