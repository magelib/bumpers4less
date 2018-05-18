<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Filesystem;

/**
 * A pool of stream wrappers
 */
class DriverPool extends \Magento\Framework\Filesystem\DriverPool
{
    /**
     * DriverPool constructor.
     * @param array $extraTypes
     */
    public function __construct($extraTypes = [])
    {
        $this->types[self::HTTP] = '\Firebear\ImportExport\Model\Filesystem\Driver\Http';
        parent::__construct($extraTypes);
    }
}
