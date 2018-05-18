<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Traits\Export;

trait Products
{
    use Entity;
    
    /**
     * @return mixed
     */
    public function getFieldsForExport()
    {
        $stockItemRows =  $this->fieldsCatalogInventory();
        $this->setHeaderColumns(1, $stockItemRows);
        $this->_headerColumns = $this->rowCustomizer->addHeaderColumns($this->_headerColumns);

        return array_unique($this->_headerColumns);
    }
}
