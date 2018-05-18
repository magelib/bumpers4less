<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Source;

use Magento\ImportExport\Model\Import\AbstractEntity;

/**
 * CSV import adapter
 */
class Csv extends \Magento\ImportExport\Model\Import\AbstractSource
{
    use \Firebear\ImportExport\Traits\Import\Map;

    /**
     * @var \Magento\Framework\Filesystem\File\Write
     */
    protected $file;

    /**
     * Delimiter.
     *
     * @var string
     */
    protected $delimiter = ',';

    /**
     * @var string
     */
    protected $enclosure = '';

    protected $maps;

    protected $extension = 'csv';

    protected $mimeTypes = [
      /*  'text/csv',
        'text/plain',
        'application/csv',
        'text/comma-separated-values',
        'text/anytext',
        'application/octet-stream' */
    ];

    protected $platform;

    /**
     * Csv constructor.
     * @param array $file
     * @param \Magento\Framework\Filesystem\Directory\Read $directory
     * @param string $delimiter
     * @param string $enclosure
     * @throws \Exception
     */
    public function __construct(
        $file,
        \Magento\Framework\Filesystem\Directory\Read $directory,
        $delimiter = ',',
        $enclosure = '"'
    ) {
        register_shutdown_function([$this, 'destruct']);
        try {
            $result = $this->checkMimeType($directory->getRelativePath($file));

            if ($result !== true) {
                throw new \Exception($result);
            }
            $this->file = $directory->openFile($directory->getRelativePath($file), 'r');
        } catch (\Magento\Framework\Exception\FileSystemException $e) {
            throw new \LogicException("Unable to open file: '{$file}'");
        }
        if ($delimiter) {
            $this->delimiter = $delimiter;
        }
        $this->enclosure = $enclosure;
        parent::__construct($this->_getNextRow());
    }

    /**
     * Close file handle
     *
     * @return void
     */
    public function destruct()
    {
        if (is_object($this->file)) {
            $this->file->close();
        }
    }

    /**
     * Read next line from CSV-file
     *
     * @return array|bool
     */
    protected function _getNextRow()
    {
        if ($this->delimiter == '\\t') {
            $this->delimiter = "\t";
        }
        $parsed = $this->file->readCsv(0, $this->delimiter, $this->enclosure);
        if (is_array($parsed) && count($parsed) != $this->_colQty) {
            foreach ($parsed as $key => $element) {
                if (strpos($element, "'") !== false) {
                    $this->_foundWrongQuoteFlag = true;
                    break;
                }
            }
        } else {
            $this->_foundWrongQuoteFlag = false;
        }

        return is_array($parsed) ? $parsed : [];
    }

    /**
     * Rewind the \Iterator to the first element (\Iterator interface)
     *
     * @return void
     */
    public function rewind()
    {
        $this->file->seek(0);
        $this->_getNextRow();
        // skip first line with the header
        parent::rewind();
    }

    /**
     * @return array
     */
    public function current()
    {
        $row = $this->_row;
        if (count($row) != $this->_colQty) {
            if ($this->_foundWrongQuoteFlag) {
                throw new \InvalidArgumentException(AbstractEntity::ERROR_CODE_WRONG_QUOTES);
            } else {
                throw new \InvalidArgumentException(AbstractEntity::ERROR_CODE_COLUMNS_NUMBER);
            }
        }
        $array = array_combine($this->_colNames, $row);

        $array = $this->replaceValue($this->changeFields($array));

        return $array;
    }

    /**
     * @return mixed
     */
    public function getColNames()
    {
        return $this->replaceColumns($this->_colNames);
    }

    /**
     * @param $platform
     * @return $this
     */
    public function setPlatform($platform)
    {
        $this->platform = $platform;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getPlatform()
    {
        return $this->platform;
    }
}
