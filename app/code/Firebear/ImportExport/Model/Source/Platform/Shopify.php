<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Platform;

use Magento\Catalog\Model\Product\Visibility;
use Firebear\ImportExport\Model\Import\Product;
use Magento\Backend\Model\Session;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory;
use Magento\Eav\Model\Entity\Context;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\File\Csv;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Tax\Model\ClassModelFactory;

class Shopify extends AbstractPlatform
{

    /**
     * @param $rowData
     * @return mixed
     */
    public function prepareRow($rowData)
    {
        return $rowData;
    }

    /**
     * @param $rowData
     * @return mixed
     */
    public function prepareColumns($rowData)
    {
        return $rowData;
    }

    /**
     * @param $data
     * @param $maps
     * @return mixed
     */
    public function afterColumns($data, $maps)
    {
        $systems = [];
        foreach ($maps as $field) {
            $systems[] = $field['system'];
        }
        foreach($data as $key => $item) {
            if(!in_array($item, $systems)) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
