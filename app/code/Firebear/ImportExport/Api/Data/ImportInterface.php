<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio GmbH. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Api\Data;

/**
 * Interface ImportInterface
 *
 * @package Firebear\ImportExport\Api\Data
 */
interface ImportInterface extends AbstractInterface
{
    const IMPORT_SOURCE = 'import_source';

    const MAP = 'map';

    /**
     * Get Import Source
     *
     * @return mixed
     */
    public function getImportSource();
    
    /**
     * @return serialize
     */
    public function getMapping();

    /**
     *
     * @return serialize
     */
    public function getPriceRules();

    /**
     * @param string $source
     *
     * @return ImportInterface
     */
    public function setImportSource($source);
    
    /**
     * @param $mapping
     *
     * @return serialize
     */
    public function setMapping($mapping);

    /**
     * @param $priceRules
     *
     * @return serialize
     */
    public function setPriceRules($priceRules);
}
