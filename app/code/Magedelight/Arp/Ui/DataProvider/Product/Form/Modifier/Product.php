<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magedelight\Arp\Ui\DataProvider\Product\Form\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Field;


class Product extends AbstractModifier
{
    const ARP_OVERRIDE = 'arp_override';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $modelId = $this->locator->getProduct()->getId();
        $value = '';

        if (isset($data[$modelId][static::DATA_SOURCE_DEFAULT][static::ARP_OVERRIDE])) {
            $value = $data[$modelId][static::DATA_SOURCE_DEFAULT][static::ARP_OVERRIDE];
        }

        if ('' === $value) {
            $data[$modelId][static::DATA_SOURCE_DEFAULT][static::ARP_OVERRIDE] = '0';
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {   $groupCode = $this->getGroupCodeByField($meta, 'container_' . static::ARP_OVERRIDE);

        if (!$groupCode) {
            return $meta;
        }

        $containerPath = $this->arrayManager->findPath(
            'container_' . static::ARP_OVERRIDE,
            $meta,
            null,
            'children'
        );
        $fieldPath = $this->arrayManager->findPath(static::ARP_OVERRIDE, $meta, null, 'children');
        $groupConfig = $this->arrayManager->get($containerPath, $meta);
        $fieldConfig = $this->arrayManager->get($fieldPath, $meta);

        $meta = $this->arrayManager->merge($containerPath, $meta, [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => 'container',
                        'componentType' => 'container',
                        'component' => 'Magento_Ui/js/form/components/group',
                        'label' => $groupConfig['arguments']['data']['config']['label'],
                        'breakLine' => false,
                        'sortOrder' => $fieldConfig['arguments']['data']['config']['sortOrder'],
                        'dataScope' => '',
                    ],
                ],
            ],
        ]);
        $meta = $this->arrayManager->merge(
            $containerPath,
            $meta,
            [
            'children' => [
                    static::ARP_OVERRIDE => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataScope' => 'data.product.arp_override',
                                    'imports' => [
                                        'disabled' =>
                                            '${$.parentName}.use_config_'
                                            . static::ARP_OVERRIDE
                                            . ':checked',
                                    ],
                                    'additionalClasses' => 'admin__field-x-small-cc',
                                    'formElement' => Checkbox::NAME,
                                    'componentType' => Field::NAME,
                                    'prefer' => 'toggle',
                                    'valueMap' => [
                                        'false' => '0',
                                        'true' => '1',
                                    ],
                                    'label' => __('Override Automatic Related Product'),
                                ],
                            ],
                        ],
                    ]
                ],
            ]
        );

        return $meta;
    }
}
