<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Type\Grouped;
use Magento\CatalogImportExport\Model\Import\Product;
use Magento\Framework\App\ResourceConnection;
use Magento\ImportExport\Model\Import;

/**
 * Class Downloadable
 */
class Links extends \Magento\GroupedImportExport\Model\Import\Product\Type\Grouped\Links
{

    protected $fireImportFactory;

public function __construct(
    \Magento\Catalog\Model\ResourceModel\Product\Link $productLink,
    ResourceConnection $resource,
    \Magento\ImportExport\Model\ImportFactory $importFactory,
    \Firebear\ImportExport\Model\ImportFactory $fireImportFactory
) {
    parent::__construct($productLink, $resource, $importFactory);
    $this->fireImportFactory = $fireImportFactory;
}

    /**
     * @return string
     */
    protected function getBehavior()
    {
        if ($this->behavior === null) {
            $this->behavior = $this->fireImportFactory->create()->getFireDataSourceModel()->getBehavior();
        }

        return $this->behavior;
    }

}