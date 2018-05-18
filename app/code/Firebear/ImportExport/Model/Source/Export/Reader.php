<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Export;

use Magento\Framework\Config\FileResolverInterface;
use Magento\Framework\Config\Reader\Filesystem;
use Magento\Framework\Config\ValidationStateInterface;
use Firebear\ImportExport\Model\Source\Config\Converter;
use Firebear\ImportExport\Model\Source\Config\SchemaLocator;

class Reader extends \Firebear\ImportExport\Model\Source\Config\Reader
{
    /**
     * @var array
     */
    protected $_idAttributes = [
        '/config/type' => 'name'
    ];

    /**
     * Reader constructor.
     *
     * @param FileResolverInterface    $fileResolver
     * @param Converter                                          $converter
     * @param SchemaLocator                                      $schemaLocator
     * @param ValidationStateInterface $validationState
     * @param string                                             $fileName
     * @param array                                              $idAttributes
     * @param string                                             $domDocumentClass
     * @param string                                             $defaultScope
     */
    public function __construct(
        FileResolverInterface $fileResolver,
        Converter $converter,
        SchemaLocator $schemaLocator,
        ValidationStateInterface $validationState,
        $fileName = 'source_types_export.xml',
        $idAttributes = [],
        $domDocumentClass = 'Magento\Framework\Config\Dom',
        $defaultScope = 'global'
    ) {
        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            $fileName,
            $idAttributes,
            $domDocumentClass,
            $defaultScope
        );
    }
}
