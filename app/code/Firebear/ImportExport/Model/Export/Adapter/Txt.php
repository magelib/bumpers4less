<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Adapter;

class Txt extends \Magento\ImportExport\Model\Export\Adapter\Csv
{

    /**
     * Return file extension for downloading.
     *
     * @return string
     */
    public function getFileExtension()
    {
        return 'txt';
    }

    /**
     * Set column names.
     *
     * @param array $headerColumns
     * @throws \Exception
     * @return $this
     */
    public function setHeaderCols(array $headerColumns)
    {
        if (null !== $this->_headerCols) {
            throw new \Magento\Framework\Exception\LocalizedException(__('The header column names are already set.'));
        }
        if ($headerColumns) {
            foreach ($headerColumns as $columnName) {
                $this->_headerCols[$columnName] = false;
            }

            $this->_fileHandler->write(implode($this->_delimiter, array_keys($this->_headerCols)) . "\n");
        }

        return $this;
    }

    /**
     * Write row data to source file.
     *
     * @param array $rowData
     * @throws \Exception
     * @return $this
     */
    public function writeRow(array $rowData)
    {
        if (null === $this->_headerCols) {
            $this->setHeaderCols(array_keys($rowData));
        }

        $writeData = $this->implodeData(
            array_merge($this->_headerCols, array_intersect_key($rowData, $this->_headerCols))
        );
        $this->_fileHandler->write(
            $writeData
        );

        return $this;
    }

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

    protected function implodeData($data)
    {
        $str = "";
        $count = count($data);
        $inc = 0;
        foreach ($data as $key => $element) {
            $flArray = [];
            preg_match('/\n/i', $element, $flArray);
            if (strpos($element, $this->_delimiter) !== false) {
                $element = addslashes($element);
                $element = "\"" . $element . "\"";
            }
            if (strpos($element, "\"") !==0 && strpos($element, " ") !== false && strpos($element, $this->_delimiter) === false) {
                $element = addslashes($element);
                $element = "\"" . $element . "\"";
            }
            if (count($flArray) > 0) {
                $newElement = str_replace("\"", "\\\"", str_replace(["\r", "\n"], '', $element));
                $str .= '"' . $newElement . '"';
            } else {
                $str .= $element;
            }
            if ($inc < $count) {
                $str .= $this->_delimiter;
            }
        }

        return $str . PHP_EOL;
    }
}
