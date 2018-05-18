<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Ui\DataProvider\Export\Job\Form\Modifier;

use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Firebear\ImportExport\Model\Source\Export\Config;

/**
 * Data provider for advanced inventory form
 */
class AdvancedExport implements ModifierInterface
{
    /**
     * @var ArrayManager
     */
    private $arrayManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var \Firebear\ImportExport\Model\Export\Dependencies\Config
     */
    protected $configExDi;

    /**
     * AdvancedExport constructor.
     * @param ArrayManager $arrayManager
     * @param Config $config
     * @param \Firebear\ImportExport\Model\Export\Dependencies\Config $configExDi
     */
    public function __construct(
        ArrayManager $arrayManager,
        Config $config,
        \Firebear\ImportExport\Model\Export\Dependencies\Config $configExDi
    ) {
        $this->arrayManager = $arrayManager;
        $this->config = $config;
        $this->configExDi = $configExDi;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return $this->prepareMeta($meta);
    }

    /**
     * @return array
     */
    protected function addFieldSource()
    {
        $childrenArray = [];
        $nameSource = 'export_source_';
        $generalConfig = [
            'componentType' => 'field',
            'component' => 'Firebear_ImportExport/js/form/dep-file',
            'formElement' => 'input',
            'dataType' => 'text',
            'source' => 'export',
            'valueUpdate' => 'afterkeydown'
        ];
        $types = $this->config->get();

        foreach ($types as $typeName => $type) {
            $sortOrder = 20;
            foreach ($type['fields'] as $name => $values) {
                $localConfig = [
                    'label' => $values['label'],
                    'dataScope' => $nameSource . $typeName . "_" . $name,
                    'sortOrder' => $sortOrder,
                    'valuesForOptions' => [
                        $typeName => $typeName
                    ]
                ];
                if (isset($values['required']) && $values['required'] == "true") {
                    $localConfig['validation'] = [
                        'required-entry' => true
                    ];
                }
                if (isset($values['component']) && ($values['component'])) {
                    $localConfig['component'] = $values['component'];
                }
                if (isset($values['validation'])) {
                    $localConfig['validation'][$values['validation']] = true;
                }
                if (isset($values['notice']) && $values['notice']) {
                    $localConfig['notice'] = __($values['notice']);
                }
                $sortOrder += 10;
                $config = array_merge($generalConfig, $localConfig);
                $childrenArray[$nameSource . $typeName . "_" . $name] = [
                    'arguments' => [
                        'data' => [
                            'config' => $config
                        ],
                    ]
                ];
            }
        }

        return $childrenArray;
    }

    protected function addFieldsDependencies()
    {

        $childrenArray = [];
        $nameSource = 'behavior_field_';
        $generalConfig = [
            'componentType' => 'checkboxset',
            'component' => 'Firebear_ImportExport/js/form/dep-entity-file',
            'formElement' => 'checkbox-set',
            'multiple' => 'true',
            'source' => 'export',
            'default' => 0,
            'dataScope' => $nameSource . 'deps',
            'notice' => __('Some items may not be compatible')
        ];
        $entities = $this->configExDi->get();

        foreach ($entities as $typeName => $type) {
            $sortOrder = 90;
            $options = [];
            if (isset($type['fields'])) {
                foreach ($type['fields'] as $name => $values) {
                    $localConfig = [
                        'label' => $values['label'],
                        'sortOrder' => $sortOrder,
                        'valuesForOptions' => [
                            $typeName => $typeName
                        ]
                    ];
                    $options[] = [
                        'label' => $values['label'],
                        'value' => $name,
                        'parent' => isset($values['parent']) ? $values['parent'] : ''
                    ];
                }
                $config = array_merge($generalConfig, $localConfig);
                $config['options'] = $options;

                $childrenArray[$nameSource . $typeName] = [
                    'arguments' => [
                        'data' => [
                            'config' => $config
                        ],
                    ]
                ];
            }
        }

        return $childrenArray;
    }

    /**
     * @return void
     */
    private function prepareMeta()
    {
        $meta['source'] = ['children' => $this->addFieldSource()];
        $meta['behavior'] = ['children' => $this->addFieldsDependencies()];

        return $meta;
    }
}
