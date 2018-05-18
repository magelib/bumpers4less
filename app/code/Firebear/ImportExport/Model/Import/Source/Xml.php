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
class Xml extends \Magento\ImportExport\Model\Import\AbstractSource
{

    use \Firebear\ImportExport\Traits\Import\Map;

    const CREATE_ATTRIBUTE = 'create_attribute';

    /**
     * @var XMLReader
     */
    protected $reader;

    private $lastRead;

    private $elementStack;

    protected $maps;

    protected $extension = 'xml';

    protected $mimeTypes = [
        'text/xml',
        'text/plain',
        'application/excel',
        'application/xml',
        'application/vnd.ms-excel',
        'application/vnd.msexcel'
    ];

    protected $platform;

    /**
     * Xml constructor.
     * @param array $file
     * @param \Magento\Framework\Filesystem\Directory\Read $directory
     * @throws \Exception
     * @throws \Firebear\ImportExport\Exception\XmlException
     */
    public function __construct(
        $file,
        \Magento\Framework\Filesystem\Directory\Read $directory
    ) {
        $result = $this->checkMimeType($directory->getAbsolutePath($file));

        if ($result !== true) {
            throw new \Exception($result);
        }
        libxml_use_internal_errors(true);
        $this->reader = simplexml_load_file($directory->getAbsolutePath($file), "SimpleXMLIterator");
        if (!$this->reader) {
            throw new \Firebear\ImportExport\Exception\XmlException(libxml_get_errors());
        }

        $this->reader->rewind();

        $this->getColumns();
    }

    /**
     * Close file handle
     *
     * @return void
     */
    public function destruct()
    {
        $this->reader->close();
    }

    /**
     * Read next line from CSV-file
     *
     * @return array|bool
     */
    protected function _getNextRow()
    {
        $parsed = [];

        if ($this->reader->hasChildren()) {
            foreach ($this->reader->getChildren() as $name => $data) {
                if ($name == self::CREATE_ATTRIBUTE) {
                    $text = 'attribute';
                    $valueText = '';
                    foreach ($data as $nameAttribute => $valueAttribute) {
                        if ($nameAttribute != 'value') {
                            $text .= "|" . $nameAttribute . ":" . $valueAttribute->__toString();
                        } else {
                            $valueText = $valueAttribute->__toString();
                        }
                    }
                    $parsed[$text] = $valueText;
                    continue;
                }
                $value = $data->__toString();
                if (strpos($value, "'") !== false) {
                    $this->_foundWrongQuoteFlag = true;
                }
                $parsed[$name] = $value;
            }
        }

        return is_array($parsed) ? $parsed : [];
    }

    protected function getColumns()
    {
        for ($this->reader->rewind(); $this->reader->valid(); $this->reader->next()) {
            $colNames = array_keys($this->_getNextRow());
            if (empty($colNames)) {
                throw new \InvalidArgumentException('Empty column names');
            }
            if (count(array_unique($colNames)) != count($colNames)) {
                throw new \InvalidArgumentException('Duplicates found in column names: ' . var_export($colNames, 1));
            }

            $diffArray = array_diff($colNames, $this->_colNames);

            $this->_colNames = array_merge($this->_colNames, $diffArray);
            $this->_colQty = count($this->_colNames);
        }
    }

    /**
     * Rewind the \Iterator to the first element (\Iterator interface)
     *
     * @return void
     */
    public function rewind()
    {
        $this->_key = -1;
        $this->_row = [];
        $this->reader->rewind();
        $row = $this->_getNextRow();
        $this->_row = $row;
    }

    /**
     * @return array
     */
    public function current()
    {

        $row = $this->_row;

        $diffArray = array_diff($this->_colNames, array_keys($row));

        if (count($diffArray)) {
            foreach ($diffArray as $name) {
                $row[$name] = '';
            }
        }

        $array = $this->replaceValue($this->changeFields($row));

        return $array;
    }

    public function next()
    {
        $this->reader->next();
        parent::next();
    }

    public function valid()
    {
        return $this->reader->valid();
    }

    /**
     * @param $data
     * @return $this
     */
    public function setMap($data)
    {
        $this->maps = $data;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getMap()
    {
        return $this->maps;
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
