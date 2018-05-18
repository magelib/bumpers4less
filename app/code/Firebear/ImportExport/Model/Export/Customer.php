<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

class Customer extends \Magento\CustomerImportExport\Model\Export\Customer
{
    use \Firebear\ImportExport\Traits\Export\Entity;

    use \Firebear\ImportExport\Traits\General;

    /**
     * @return array
     */
    protected function _getHeaderColumns()
    {
        $validAttributeCodes = $this->_getExportAttributeCodes();
        $headers             = array_merge($this->_permanentAttributes, $validAttributeCodes, ['password']);

        return $this->changeHeaders($headers);
    }

    /**
     * @return mixed
     */
    public function getFieldsForExport()
    {
        $validAttributeCodes = $this->_getExportAttributeCodes();

        return array_unique(array_merge($this->_permanentAttributes, $validAttributeCodes, ['password']));
    }

    /**
     * @param $item
     */
    public function exportItem($item)
    {
        $row                       = $this->_addAttributeValuesToRow($item);
        $row[self::COLUMN_WEBSITE] = $this->_websiteIdToCode[$item->getWebsiteId()];
        $row[self::COLUMN_STORE]   = $this->_storeIdToCode[$item->getStoreId()];

        if ($row['gender'] == "0") {
            $row['gender'] = '';
        }

        $this->getWriter()->writeRow($this->changeRow($row));
    }

    public function export()
    {
        $this->_prepareEntityCollection($this->_getEntityCollection());
        $writer = $this->getWriter();

        // create export file
        $writer->setHeaderCols($this->_getHeaderColumns());
        $this->_exportCollectionByPages($this->_getEntityCollection());

        return [$writer->getContents(), $this->_getEntityCollection()->getSize()];
    }
}
