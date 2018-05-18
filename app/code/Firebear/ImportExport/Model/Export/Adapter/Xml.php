<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export\Adapter;

class Xml extends \Magento\ImportExport\Model\Export\Adapter\AbstractAdapter
{

    /**
     * @var \XMLWriter
     */
    protected $writer;

    /**
     * Xml constructor.
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \XMLWriter $writer
     * @param null $destination
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        \XMLWriter $writer,
        $destination = null
    ) {
        $this->writer = $writer;
        register_shutdown_function([$this, 'destruct']);
        parent::__construct($filesystem, $destination);
    }

    /**
     * Object destructor.
     *
     * @return void
     */
    public function destruct()
    {
       // $this->writer->flush();
    }

    /**
     * @return $this
     */
    protected function _init()
    {
        $this->writer->openURI('php://output');
        $this->writer->openMemory();
        $this->writer->startDocument("1.0", "UTF-8");
        $this->writer->setIndent(1);
        $this->writer->startElement("Items");

        return $this;
    }

    /**
     * MIME-type for 'Content-Type' header.
     *
     * @return string
     */
    public function getContentType()
    {
        return 'text/xml';
    }

    public function getContents()
    {
        $this->writer->endDocument();
        return $this->writer->outputMemory();
    }

    /**
     * Return file extension for downloading.
     *
     * @return string
     */
    public function getFileExtension()
    {
        return 'xml';
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
        $this->writer->startElement('item');
        foreach ($rowData as $key => $value) {
            if (is_array($value)) {
                $this->recursiveAdd($key, $value);
            } else {
                $this->writer->writeElement($key, $value);
            }
        }
        $this->writer->endElement();

        return $this;
    }

    /**
     * @param $key
     * @param array $data
     */
    protected function recursiveAdd($key, array $data)
    {
        if (!is_numeric($key)) {
            $this->writer->startElement($key);
        }
        foreach ($data as $ki => $values) {
            if (is_array($values)) {
                $this->recursiveAdd($ki, $values);
            } else {
                $this->writer->writeElement($ki, $values);
            }
        }
        if (!is_numeric($key)) {
            $this->writer->endElement();
        }
    }
}
