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
 
namespace Itoris\DynamicProductOptions\Model;

class Option extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface|null
     */
    protected $_objectManager = null;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ){
        $this->_objectManager = $objectManager;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct() {
        $this->_init('Itoris\DynamicProductOptions\Model\ResourceModel\Option');
    }

    /**
     * @param $option \Magento\Catalog\Model\Product\Option
     * @return $this
     */

    public function saveOption($option) {
        $configuration = [
            'validation'    => $option->getValidation(),
            'default_value' => $option->getDefaultValue(),
            'hide_on_focus' => $option->getHideOnFocus(),
            'comment'       => $option->getComment(),
            'css_class'     => $option->getCssClass(),
            'html_args'     => $option->getHtmlArgs(),
            'section'       => $option->getSection(),
            'order'         => $option->getOrder(),
            'img_src'       => $option->getImgSrc(),
            'img_alt'       => $option->getImgAlt(),
            'img_title'     => $option->getImgTitle(),
            'static_text'   => $option->getStaticText(),
            'section_order' => $option->getSectionOrder(),
            'type'          => $option->getType(),
            'internal_id'   => $option->getInternalId(),
            'visibility'    => $option->getVisibility(),
            'visibility_condition' => $option->getVisibilityCondition(),
            'visibility_action'    => $option->getVisibilityAction(),
            'customer_group'       => $option->getCustomerGroup(),
            'default_select_title' => $option->getDefaultSelectTitle()
        ];
        $this->setCustomerGroup($option->getCustomerGroup());
        if ($option->getType() == 'image' || $option->getType() == 'html') {
            $this->load($option->getItorisOptionId());
            $isDelete = $option->getItorisIsDelete() && $this->getId();
            $option->delete();
            $option->setOptionId(null);
            if ($isDelete) {
                $this->delete();
                return;
            }
        } else {
            $this->load($option->getOptionId(), 'orig_option_id');
        }
        /** @var \Magento\Framework\App\ResourceConnection $resource */
        $resource = $this->_objectManager->get('Magento\Framework\App\ResourceConnection');
        $connection = $resource->getConnection('write');
        
        $rows = $connection->fetchAll( "SHOW COLUMNS FROM `{$resource->getTableName('catalog_product_entity')}`" );
        if ($rows[0]['Field'] == 'row_id') {
            $dummyId = (int)$connection->fetchOne("select `entity_id` from `{$resource->getTableName('catalog_product_entity')}` where `row_id`={$option->getProductId()}");
        } else $dummyId = $option->getProductId();
        
        $configuration = \Zend_Json::encode($configuration);
        $isUseGlobal = !!$this->getRequest()->getPostValue('idpo_use_global');
        if (!$option->getStoreId()) $option->setStoreId((int) $option->getProduct()->getStoreId());
        if ((int) $option->getStoreId() == 0) $isUseGlobal = false;
        if ($isUseGlobal) {
            /*$option->setData('product_id', $option->getProduct()->getId())
                ->setData('store_id', $option->getProduct()->getStoreId());*/
            if ($option->getId()) {
                $option_price_table = $resource->getTableName('catalog_product_option_price');
                $option_title_table = $resource->getTableName('catalog_product_option_title');
                $catalog_product_option_type_title = $resource->getTableName('catalog_product_option_type_title');
                $catalog_product_option_type_value = $resource->getTableName('catalog_product_option_type_value');
                $connection->query("delete from {$option_price_table} where `option_id`={$option->getId()} and `store_id`={$option->getStoreId()}");
                $connection->query("delete from {$option_title_table} where `option_id`={$option->getId()} and `store_id`={$option->getStoreId()}");
                $connection->query("delete from {$catalog_product_option_type_title} where `store_id` = {$option->getStoreId()} and `option_type_id` in (select `option_type_id` from {$catalog_product_option_type_value} where `option_id`={$option->getId()})");
            }
        }

        if (!$this->getId()) {
            if ($isUseGlobal) return $this;
            $this->setOrigOptionId($option->getOptionId())
                ->setStoreId($option->getStoreId())
                ->setProductId($dummyId);
        }

        if (!$isUseGlobal) {
            $this->setConfiguration($configuration);//->save();
            if ($option->getPriceType()) {
                $option_price_table = $resource->getTableName('catalog_product_option_price');
                $id = (int) $connection->fetchOne("select `option_price_id` from {$option_price_table} where `option_id` = ".floatval($option->getOptionId())." and store_id = ".intval($option->getStoreId()));
                if ($id) $connection->query("update {$option_price_table} set `price`=".floatval($option->getPrice()).", `price_type`='{$option->getPriceType()}' where `option_price_id`={$id}");
                else $connection->query("insert into {$option_price_table} set `option_id`={$option->getOptionId()}, `store_id`={$option->getStoreId()}, `price`=".floatval($option->getPrice()).", `price_type`='{$option->getPriceType()}'");
            }
        } else $this->delete();
        
        //fixing magento2 bug, it was not possible to change field's required state from Yes to No
        $option_table = $resource->getTableName('catalog_product_option');
        $connection->query("update {$option_table} set `is_require` = ".intval($option->getIsRequire())." where `option_id`=".intval($option->getOptionId()));
        
        return $this;
    }

    /**
     * @return \Magento\Framework\App\RequestInterface
     */
    protected function getRequest(){
        return $this->_objectManager->get('\Magento\Framework\App\RequestInterface');
    }
}