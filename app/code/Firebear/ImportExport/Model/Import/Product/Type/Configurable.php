<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import\Product\Type;

use Magento\CatalogImportExport\Model\Import\Product as ImportProduct;

class Configurable extends \Magento\ConfigurableImportExport\Model\Import\Product\Type\Configurable
{

    const ERROR_INVALID_PRICE_CORRECTION = 'invalidPriceCorr';

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory
     */
    protected $attributeFactory;

    protected $_specialAttributes = [
        '_super_products_sku',
        '_super_attribute_code',
        '_super_attribute_option',
        '_super_attribute_price_corr',
        '_super_attribute_price_website',
    ];

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;


    /**
     * @var \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory
     */
    protected $resourceFactory;

    /**
     * Configurable constructor.
     * @param \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac
     * @param \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param array $params
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypesConfig
     * @param \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $_productColFac
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Eav\Model\ResourceModel\Entity\Attribute\Set\CollectionFactory $attrSetColFac,
        \Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory $prodAttrColFac,
        \Magento\Framework\App\ResourceConnection $resource,
        array $params,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypesConfig,
        \Magento\ImportExport\Model\ResourceModel\Helper $resourceHelper,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $_productColFac,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory $attributeFactory,
        \Magento\CatalogImportExport\Model\Import\Proxy\Product\ResourceModelFactory $resourceModelFactory
    ) {
        parent::__construct(
            $attrSetColFac,
            $prodAttrColFac,
            $resource,
            $params,
            $productTypesConfig,
            $resourceHelper,
            $_productColFac
        );
        $this->registry = $registry;
        $this->_messageTemplates[self::ERROR_INVALID_PRICE_CORRECTION] = 'Super attribute price correction value is invalid';
        $this->attributeFactory = $attributeFactory;
        $this->resourceFactory = $resourceModelFactory;
    }

    /**
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     */
    protected function _isParticularAttributesValid(array $rowData, $rowNum)
    {
        $options = $this->registry->registry('firebear_create_attr');
        if (!empty($rowData['_super_attribute_code'])) {
            $superAttrCode = $rowData['_super_attribute_code'];
            if (!$this->_isAttributeSuper($superAttrCode)) {
                // check attribute superity
                $this->_entityModel->addRowError(self::ERROR_ATTRIBUTE_CODE_IS_NOT_SUPER, $rowNum);
                return false;
            } elseif (isset($rowData['_super_attribute_option']) && $rowData['_super_attribute_option'] !== '') {
                $optionKey = strtolower($rowData['_super_attribute_option']);
                if (!empty($options) && isset($options[$superAttrCode])) {
                    $this->_superAttributes[$superAttrCode]['options'] = $options[$superAttrCode];
                }

                if (!isset($this->_superAttributes[$superAttrCode]['options'][$optionKey])) {
                    if (!$this->createAttributeValues($superAttrCode, $optionKey)) {
                        $this->_entityModel->addRowError(self::ERROR_INVALID_OPTION_VALUE, $rowNum);
                        return false;
                    }
                }

                if (!empty($rowData['super_attribute_price_corr'])
                    && !$this->_isPriceCorr($rowData['super_attribute_price_corr'])) {
                    $this->_entityModel->addRowError(self::ERROR_INVALID_PRICE_CORRECTION, $rowNum);
                    return false;
                }
            }
        }

        return true;
    }

    public function saveData()
    {
        $newSku = $this->_entityModel->getNewSku();
        $oldSku = $this->_entityModel->getOldSku();
        $this->_productSuperData = [];
        $this->_productData = null;
        while ($bunch = $this->_entityModel->getNextBunch()) {
            $bunch = $this->changeData($bunch);
            if ($this->_entityModel->getBehavior() == \Magento\ImportExport\Model\Import::BEHAVIOR_APPEND) {
                $this->_loadSkuSuperDataForBunch($bunch);
            }
            if (!$this->configurableInBunch($bunch)) {
                continue;
            }

            $this->_superAttributesData = [
                'attributes' => [],
                'labels' => [],
                'super_link' => [],
                'relation' => [],
            ];

            $this->_simpleIdsToDelete = [];

            $this->_loadSkuSuperAttributeValues($bunch, $newSku, $oldSku);

            foreach ($bunch as $rowNum => $rowData) {
                if (!$this->_entityModel->isRowAllowedToImport($rowData, $rowNum)) {
                    continue;
                }
                // remember SCOPE_DEFAULT row data
                $scope = $this->_entityModel->getRowScope($rowData);
                if (ImportProduct::SCOPE_DEFAULT == $scope &&
                    !empty($rowData[ImportProduct::COL_SKU])) {
                    if (strpos($this->_entityModel->getProductMetadata()->getVersion(), '2.2') !== false) {
                        $sku = strtolower($rowData[ImportProduct::COL_SKU]);
                    } else {
                        $sku = $rowData[ImportProduct::COL_SKU];
                    }
                    $this->_productData = isset($newSku[$sku]) ? $newSku[$sku] : $oldSku[$sku];

                    if ($this->_type != $this->_productData['type_id']) {
                        $this->_productData = null;
                        continue;
                    }
                    $this->_collectSuperData($rowData);
                }
            }
            $this->_processSuperData();

            $this->_deleteData();

            $this->_insertData();
        }

        return $this;
    }

    /**
     * @param $bunch
     * @return array
     */
    protected function changeData($bunch)
    {
        $newBunch = [];
        foreach ($bunch as $key => $data) {
            if (!in_array(strtolower($data['sku']), $this->_entityModel->getNotValidSkus())) {
                $newBunch[$key] = $data;
            }
        }

        return $newBunch;
    }


    public function createAttributeValues($attrCode, $attrValue)
    {
        $options = [];
        $attribute = $this->attributeFactory->create();
        $attribute->loadByCode(\Magento\Catalog\Model\Product::ENTITY, $attrCode);
        //   $attrParams = $productTypeModel->retrieveAttribute($attrCode, $attributeSet);
        $optionsCount = count($attribute->getOptions());
        switch ($attribute->getFrontendInput()) {
            case 'select':
                $options[$attribute->getId()][] = [
                    'sort_order' => $optionsCount + 1,
                    'value' => $attrValue,
                    'code' => $attrCode
                ];
                break;
            case 'multiselect':
                foreach (explode(Product::PSEUDO_MULTI_LINE_SEPARATOR, $attrCode) as $value) {
                    $options[$attribute->getId()][] = [
                        'sort_order' => $optionsCount + 1,
                        'value' => $value,
                        'code' => $attrCode
                    ];
                }
                break;
            default:
                break;
        }

        if (!empty($options)) {
            foreach ($options as $attributeId => $optionsArray) {
                foreach ($optionsArray as $option) {
                    /**
                     * @see \Magento\Eav\Model\ResourceModel\Entity\Attribute::_updateAttributeOption()
                     */
                    $connection = $this->_connection;
                    $resource = $this->resourceFactory->create();
                    $table = $resource->getTable('eav_attribute_option');
                    $data = ['attribute_id' => $attributeId, 'sort_order' => $option['sort_order']];
                    $connection->insert($table, $data);
                    $intOptionId = $connection->lastInsertId($table);

                    $table = $resource->getTable('eav_attribute_option_value');
                    $data = ['option_id' => $intOptionId, 'store_id' => 0, 'value' => $option['value']];
                    $connection->insert($table, $data);
                    $this->addAttributeOption($option['code'], strtolower($option['value']), $intOptionId);
                }
            }

            return true;
        }

        return false;
    }
}
