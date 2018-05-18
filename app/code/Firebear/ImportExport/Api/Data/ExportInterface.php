<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api\Data;

/**
 * Interface ExportInterface
 *
 * @package Firebear\ImportExport\Api\Data
 */
interface ExportInterface extends AbstractInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
     const EXPORT_SOURCE = 'export_source';

    /**
     * @return string
     */
    public function getExportSource();
    
    /**
     * @param $source
     *
     * @return ExportInterface
     */
    public function setExportSource($source);
}
