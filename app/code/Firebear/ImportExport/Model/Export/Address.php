<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Export;

class Address extends \Magento\CustomerImportExport\Model\Export\Address
{
    use \Firebear\ImportExport\Traits\Export\Entity;

    use \Firebear\ImportExport\Traits\General;

    /**
     * @return mixed
     */
    protected function _getHeaderColumns()
    {
        $headers = array_merge(
            $this->_permanentAttributes,
            $this->_getExportAttributeCodes(),
            array_keys(self::$_defaultAddressAttributeMapping)
        );

        return $this->changeHeaders($headers);
    }

    /**
     * @return mixed
     */
    public function getFieldsForExport()
    {
        return array_unique(
            array_merge(
                $this->_permanentAttributes,
                $this->_getExportAttributeCodes(),
                array_keys(self::$_defaultAddressAttributeMapping)
            )
        );
    }

    /**
     * @param $item
     */
    public function exportItem($item)
    {
        $row = $this->_addAttributeValuesToRow($item);

        $customer = $this->_customers[$item->getParentId()];

        foreach (self::$_defaultAddressAttributeMapping as $columnName => $attributeCode) {
            if (!empty($customer[$attributeCode]) && $customer[$attributeCode] == $item->getId()) {
                $row[$columnName] = 1;
            }
        }

        $row[self::COLUMN_ADDRESS_ID] = $item['entity_id'];
        $row[self::COLUMN_EMAIL]      = $customer['email'];
        $row[self::COLUMN_WEBSITE]    = $this->_websiteIdToCode[$customer['website_id']];

        $this->getWriter()->writeRow($this->changeRow($row));
    }

    public function export()
    {
        // skip and filter by customer address attributes
        $this->_prepareEntityCollection($this->_getEntityCollection());
        $this->_getEntityCollection()->setCustomerFilter(array_keys($this->_customers));

        // prepare headers
        $this->getWriter()->setHeaderCols($this->_getHeaderColumns());

        $this->_exportCollectionByPages($this->_getEntityCollection());

        return [$this->getWriter()->getContents(),$this->_getEntityCollection()->getSize()];
    }
}
