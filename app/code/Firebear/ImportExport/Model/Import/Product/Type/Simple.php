<?php
/**
 * @copyright: Copyright © 2018 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Type;
use Magento\Framework\EntityManager\MetadataPool;
/**
 * Class Downloadable
 */
class Simple extends \Magento\CatalogImportExport\Model\Import\Product\Type\Simple
{
	/**
     * AbstractType constructor
     *
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac
     * @param ResourceConnection $resource
     * @param array $params
     * @param MetadataPool|null $metadataPool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
	protected $eavConfig;
	
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac,
        \Magento\Framework\App\ResourceConnection $resource,
        array $params,
        MetadataPool $metadataPool = null,
		\Magento\Eav\Model\Config $eavConfig
    ) {
		$this->eavConfig = $eavConfig;
		parent::__construct($attrSetColFac, $prodAttrColFac, $resource, $params, $metadataPool);
    }
    public function prepareAttributesWithDefaultValueForSave(array $rowData, $withDefaultValue = true)
    {
		$resultAttrs = [];

        foreach ($this->_getProductAttributes($rowData) as $attrCode => $attrParams) {

            if ($attrParams['is_static']) {
                continue;
            }

            if (isset($rowData[$attrCode]) && strlen($rowData[$attrCode])) {
                if (in_array($attrParams['type'], ['select', 'boolean'])) {
					$attribute = $this->eavConfig->getAttribute('catalog_product', $attrCode);
					if($attribute->getId()){
						$options = $attribute->getSource()->getAllOptions();
						foreach($options as $optionArr){
                            $label = $optionArr['label'];
                            if ($optionArr['label'] instanceof \Magento\Framework\Phrase) {
                               $label = $label->__toString();
                            }
							if ($rowData[$attrCode] == $label){
								$resultAttrs[$attrCode] = $optionArr['value'];
								break;
							}
						}
					}
                } elseif ('multiselect' == $attrParams['type']) {
                    $resultAttrs[$attrCode] = [];
                    foreach ($this->_entityModel->parseMultiselectValues($rowData[$attrCode]) as $value) {
                        $resultAttrs[$attrCode][] = $attrParams['options'][strtolower($value)];
                    }

                    $resultAttrs[$attrCode] = implode(',', $resultAttrs[$attrCode]);
                } else {
                    $resultAttrs[$attrCode] = $rowData[$attrCode];
                }
            } elseif (array_key_exists($attrCode, $rowData)) {
                $resultAttrs[$attrCode] = $rowData[$attrCode];
            } elseif ($withDefaultValue && null !== $attrParams['default_value']) {
                $resultAttrs[$attrCode] = $attrParams['default_value'];
            }
        }
        return $resultAttrs;
    }
}
