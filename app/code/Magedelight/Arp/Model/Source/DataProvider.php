<?php
/* Magedelight
 * Copyright (C) 2016 Magedelight <info@magedelight.com>
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see http://opensource.org/licenses/gpl-3.0.html.
 *
 * @category Magedelight
 * @package Magedelight_Giftcard
 * @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 */
namespace Magedelight\Arp\Model\Source;

use Magedelight\Arp\Model\ResourceModel\Productrule\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;

/**
 * Class DataProvider
 *
 * @package Magedelight\Giftcard\Model\Code
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
    /**
     * @var \Magedelight\Giftcard\Model\ResourceModel\Code\Collection
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $pageCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $codeCollectionFactory,
        DataPersistorInterface $dataPersistor,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $codeCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->meta = $this->prepareMeta($this->meta);
    }

    /**
     * Prepares Meta
     *
     * @param array $meta
     * @return array
     */
    public function prepareMeta(array $meta)
    {
        return $meta;
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();
        $use_config = [];
        foreach ($items as $page) {
            $page->setStores(explode(',', $page->getStoreId()));
            $page->setCustomerGroupIds(explode(',', $page->getCustomerGroups()));
            $page->setProductsCategory(explode(',', $page->getProductsCategory()));
            $page->setCmsPage(explode(',', $page->getCmsPage()));
            if($page->getBlockLayout()) {
                $use_config['block_layout'] = false;
            }  else {
                $page->setBlockLayout(2);
            }
            if($page->getNumberOfRows()) {
                $use_config['number_of_rows'] = false;
            }
            if($page->getMaxProductsDisplay()) {
                $use_config['max_products_display'] = false;
            }
            if($page->getSlidesToShow()) {
                $use_config['slides_to_show'] = false;
            }
            if($page->getSlidesToScroll()) {
                $use_config['slides_to_scroll'] = false;
            }
            if($page->getDisplayCartButton()) {
                $use_config['display_cart_button'] = false;
            }
            if($page->getBlockPage() == '1') {
                $page->setBlockPositionProduct($page->getBlockPosition());
            }
            if($page->getBlockPage() == '2') {
                $page->setBlockPositionShoppingcart($page->getBlockPosition());
            }
            if($page->getBlockPage() == '3') {
                $page->setBlockPositionCategory($page->getBlockPosition());
            }
            if($page->getBlockPage() == '5') {
                $page->setBlockPositionCms($page->getBlockPosition());
            }
            $page->setUseConfig($use_config);
            $this->loadedData[$page->getId()] = $page->getData();
        }

        $data = $this->dataPersistor->get('arp_productrule');
        if (!empty($data)) {
            $page = $this->collection->getNewEmptyItem();
            $page->setData($data);
            $this->loadedData[$page->getId()] = $page->getData();
            $this->dataPersistor->clear('arp_productrule');
        }

        return $this->loadedData;
    }
}
