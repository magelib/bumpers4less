<?php
/**
 * ITORIS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the ITORIS's Magento Extensions License Agreement
 * which is available through the world-wide-web at this URL:
 * http://www.itoris.com/magento-extensions-license.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to sales@itoris.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extensions to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to the license agreement or contact sales@itoris.com for more information.
 *
 * @category   ITORIS
 * @package    ITORIS_M2_DYNAMIC_PRODUCT_OPTIONS
 * @copyright  Copyright (c) 2016 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

namespace Itoris\DynamicProductOptions\Model\Rewrite\Product\Type;

class Virtual extends \Magento\Catalog\Model\Product\Type\Virtual
{
    /** @var \Magento\Framework\ObjectManagerInterface|null  */
    protected $_objectManager = null;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Catalog\Model\Product\Option $catalogProductOption
     * @param \Magento\Eav\Model\Config $eavConfig
     * @param \Magento\Catalog\Model\Product\Type $catalogProductType
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Catalog\Model\Product\Option $catalogProductOption,
        \Magento\Eav\Model\Config $eavConfig,
        \Magento\Catalog\Model\Product\Type $catalogProductType,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \Magento\MediaStorage\Helper\File\Storage\Database $fileStorageDb,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Registry $coreRegistry,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->_objectManager = $objectManager;
        parent::__construct(
            $catalogProductOption,
            $eavConfig,
            $catalogProductType,
            $eventManager,
            $fileStorageDb,
            $filesystem,
            $coreRegistry,
            $logger,
            $productRepository
        );
    }

    public function checkProductBuyState($product = null) {
        if ($this->getItorisHelper()->isEnabledOnFrontend()) {
            $this->getItorisProductTypeHelper()->checkDynamicOptions($product);

            foreach ($product->getProductOptionsCollection() as $option) {
                if ($option->getIsRequire() && !$product->getData('skip_required_option' . $option->getId())) {
                    $customOption = $product
                        ->getCustomOption('option_' . $option->getId());
                    if (!$customOption || strlen($customOption->getValue()) == 0) {
                        $product->setSkipCheckRequiredOption(true);
                        throw new \Magento\Framework\Exception\LocalizedException(
                            __('The product has required options.')
                        );
                    }
                }
            }
            return $this;
        }
        return parent::checkProductBuyState($product);
    }

    protected function _prepareOptions(\Magento\Framework\DataObject $buyRequest, $product, $processMode) {
        if ($this->getItorisHelper()->isEnabledOnFrontend()) {
            $optionValues = [];
            $helper = $this->getItorisProductTypeHelper();
            foreach ($product->getProductOptionsCollection() as $_option) {
                if ($_option->getType() == 'file') {
                    continue;
                }

                $group = $_option->groupFactory($_option->getType())
                    ->setOption($_option)
                    ->setProduct($product)
                    ->setRequest($buyRequest)
                    ->setProcessMode('lite')
                    ->validateUserValue($buyRequest->getOptions());

                $preparedValue = $group->prepareForCart();
                if ($preparedValue !== null) {
                    $optionValues[$_option->getId()] = $helper->prepareOptionValue($_option, $preparedValue);
                }
            }
            $this->getItorisProductTypeHelper()->checkDynamicOptions($product, $optionValues);
        }
        return parent::_prepareOptions($buyRequest, $product, $processMode);
    }
    /**
     * @return \Itoris\DynamicProductOptions\Helper\Data
     */
    public function getItorisHelper(){
        return $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\Data');
    }

    /**
     * @return \Itoris\DynamicProductOptions\Helper\ProductType
     */
    public function getItorisProductTypeHelper(){
        return $this->_objectManager->create('Itoris\DynamicProductOptions\Helper\ProductType');
    }
}