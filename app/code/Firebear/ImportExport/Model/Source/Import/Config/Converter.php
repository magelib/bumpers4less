<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Import\Config;

use Magento\Framework\Config\ConverterInterface;
use Magento\Framework\Module\Manager;
use Magento\Framework\App\Utility\Classes;

class Converter implements ConverterInterface
{
    /**
     * @var \Magento\Framework\Module\Manager
     */
    protected $moduleManager;

    /**
     * Converter constructor.
     * @param Manager $moduleManager
     */
    public function __construct(Manager $moduleManager)
    {
        $this->moduleManager = $moduleManager;
    }

    /**
     * Convert dom node tree to array
     *
     * @param \DOMDocument $source
     * @return array
     * @throws \InvalidArgumentException
     */
    public function convert($source)
    {
        $output = ['entities' => []];
        /** @var \DOMNodeList $entities */
        $entities = $source->getElementsByTagName('entity');
        /** @var \DOMNode $entityConfig */
        foreach ($entities as $entityConfig) {
            $attributes = $entityConfig->attributes;
            $name = $attributes->getNamedItem('name')->nodeValue;
            $label = $attributes->getNamedItem('label')->nodeValue;
            $behaviorModel = $attributes->getNamedItem('behaviorModel')->nodeValue;
            $model = $attributes->getNamedItem('model')->nodeValue;
            if (!$this->moduleManager->isOutputEnabled(Classes::getClassModuleName($model))) {
                continue;
            }
            $output['entities'][$name] = [
                'name' => $name,
                'label' => $label,
                'behaviorModel' => $behaviorModel,
                'model' => $model,
                'types' => [],
                'relatedIndexers' => [],
            ];
        }

        /** @var \DOMNodeList $entityTypes */
        $entityTypes = $source->getElementsByTagName('entityType');
        /** @var \DOMNode $entityTypeConfig */
        foreach ($entityTypes as $entityTypeConfig) {
            $attributes = $entityTypeConfig->attributes;
            $name = $attributes->getNamedItem('name')->nodeValue;
            $model = $attributes->getNamedItem('model')->nodeValue;
            $entity = $attributes->getNamedItem('entity')->nodeValue;

            if (isset($output['entities'][$entity])) {
                $output['entities'][$entity]['types'][$name] = ['name' => $name, 'model' => $model];
            }
        }

        /** @var \DOMNodeList $relatedIndexers */
        $relatedIndexers = $source->getElementsByTagName('relatedIndexer');
        /** @var \DOMNode $relatedIndexerConfig */
        foreach ($relatedIndexers as $relatedIndexerConfig) {
            $attributes = $relatedIndexerConfig->attributes;
            $name = $attributes->getNamedItem('name')->nodeValue;
            $entity = $attributes->getNamedItem('entity')->nodeValue;

            if (isset($output['entities'][$entity])) {
                $output['entities'][$entity]['relatedIndexers'][$name] = ['name' => $name];
            }
        }
        return $output;
    }
}
