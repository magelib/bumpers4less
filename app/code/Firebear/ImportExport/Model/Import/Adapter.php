<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */
namespace Firebear\ImportExport\Model\Import;

use Magento\Framework\Filesystem\Directory\Write;
use Magento\ImportExport\Model\Import\AbstractSource;

class Adapter
{

    /**
     * @param $type
     * @param $directory
     * @param $source
     * @param null $options
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public static function factory($class, $directory, $source, $options = null)
    {

        if (!class_exists($class)) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('\'%1\' model extension is not supported', $class)
            );
        }

        $adapter = new $class($source, $directory, $options);

        if (!$adapter instanceof AbstractSource) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Adapter must be an instance of \Magento\ImportExport\Model\Import\AbstractSource')
            );
        }
        
        return $adapter;
    }

    /**
     * @param $source
     * @param $directory
     * @param null $options
     * @return mixed
     */
    public static function findAdapterFor($class, $source, $directory, $options = null)
    {
        return self::factory($class, $directory, $source, $options);
    }
}
