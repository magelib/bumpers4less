<?php

namespace Magedelight\Arp\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;


/**
 * Sizechart post mysql resource
 * @method array|null getProductsData()
 */

class Productrule extends AbstractDb
{

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        // Table Name and Primary Key column
        $this->_init('md_advance_product_rule', 'rule_id');
    }
    
    public function getProductByCategoriesIds($catIds) {
        $categorySelect = $this->getConnection()->select()->from(
        ['cat' => 'catalog_category_product'], 'cat.product_id')
        ->where(
            $this->getConnection()->prepareSqlCondition(
                'cat.category_id',
                ['in' => $catIds])
        );
        return $this->getConnection()->fetchCol($categorySelect);
    }
}
