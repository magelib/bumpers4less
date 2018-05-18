<?php
/**
* Magedelight
* Copyright (C) 2016 Magedelight <info@magedelight.com>
*
* @category Magedelight
* @package Magedelight_Arp
* @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
* @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
* @author Magedelight <info@magedelight.com>
*/

namespace Magedelight\Arp\Cron;

class Mdkv 
{
    protected $helper;

    public function __construct(
        \Magedelight\Arp\Helper\Mddata $helper
    ) 
    {
        $this->helper = $helper;
    }

    public function execute()
    {
        $mappedDomains = $this->helper->getAllowedDomainsCollection();
    }
}

