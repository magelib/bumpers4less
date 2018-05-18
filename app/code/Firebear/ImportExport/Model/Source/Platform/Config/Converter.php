<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Platform\Config;

use Magento\Framework\Config\ConverterInterface;

class Converter implements ConverterInterface
{
    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     * @throws \InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function convert($source)
    {
        $result = [];
        /** @var \DOMNode $templateNode */
        foreach ($source->documentElement->childNodes as $typeNode) {
            if ($typeNode->nodeType != XML_ELEMENT_NODE) {
                continue;
            }
            $typeName = $typeNode->attributes->getNamedItem('name')->nodeValue;
            $typeLabel = $typeNode->attributes->getNamedItem('label')->nodeValue;
            $result[$typeName] = [
                'label' => $typeLabel,
                'model' => $typeNode->attributes->getNamedItem('model')
                    ? $typeNode->attributes->getNamedItem('model')->nodeValue
                    : null
            ];

            foreach ($typeNode->childNodes as $childNode) {
                if ($childNode->nodeType != XML_ELEMENT_NODE) {
                    continue;
                }
                if ($childNode->localName == 'attribute') {
                    $result[$typeName]['fields'][$childNode->attributes->getNamedItem('code')->nodeValue] = [
                        'reference' => $childNode->attributes->getNamedItem('reference')->nodeValue,
                        'label' => $childNode->attributes->getNamedItem('label')
                            ? $childNode->attributes->getNamedItem('label')->nodeValue : '',
                        'default' => $childNode->attributes->getNamedItem('default')
                            ? $childNode->attributes->getNamedItem('default')->nodeValue : ''
                    ];
                }

                if ($childNode->localName == 'description') {
                    $result[$typeName]['descs'][] = [
                        'label' => $childNode->attributes->getNamedItem('label')->nodeValue
                    ];
                }

                if ($childNode->localName == 'link') {
                    $result[$typeName]['links'][] = [
                        'label' => $childNode->attributes->getNamedItem('label')->nodeValue,
                        'suffix' => $childNode->attributes->getNamedItem('suffix')
                            ? $childNode->attributes->getNamedItem('suffix')->nodeValue : ''
                    ];
                }
            }
        }

        return $result;
    }
}
