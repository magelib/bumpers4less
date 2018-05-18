<?php
namespace Magedelight\Arp\Model\ResourceModel\Productrule;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
 
class Collection extends AbstractCollection
{
 
    protected $_idFieldName = \Magedelight\Arp\Model\Productrule::RULE_ID;
     
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Magedelight\Arp\Model\Productrule', 'Magedelight\Arp\Model\ResourceModel\Productrule');
    }
}
