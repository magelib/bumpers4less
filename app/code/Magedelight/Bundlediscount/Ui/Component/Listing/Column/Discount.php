<?php

/**
 * Magedelight
 * Copyright (C) 2016 Magedelight <info@magedelight.com>.
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
 *
 * @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 */

namespace Magedelight\Bundlediscount\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

/**
 * Class Store.
 */
class Discount extends Column
{
    /**
     * Escaper.
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;
    protected $helper;

    /**
     * Constructor.
     *
     * @param ContextInterface   $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Escaper            $escaper
     * @param array              $components
     * @param array              $data
     */
    public function __construct(
    ContextInterface $context, UiComponentFactory $uiComponentFactory, Escaper $escaper, array $components = [], \Magedelight\Bundlediscount\Helper\Data $helper, array $data = []
    ) {
        $this->escaper = $escaper;
        $this->helper = $helper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source.
     *
     * @param array $dataSource
     *
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $item[$this->getData('name')] = $this->prepareItem($item);
            }
        }

        return $dataSource;
    }

    /**
     * Get data.
     *
     * @param array $item
     *
     * @return string
     */
    protected function prepareItem(array $item)
    {
        $content = '';
        $discount = $item['discount_price'];
        $discount_type = $item['discount_type'];

        if (empty($discount)) {
            return '';
        }
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $priceHelper = $objectManager->create('Magento\Framework\Pricing\Helper\Data');

        if ($discount_type == 0) {
            $content .= '<b>'.$priceHelper->currency($discount, true, false).'</b>';
        } else {
            $content .= '<b>'.$this->helper->formatPercentage($discount).'%'.'</b>';
        }

        return $content;
    }
}
