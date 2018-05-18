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
 * @category MD
 * @package MD_Cybersourcesa
 * @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 * 
 */
namespace Magedelight\Arp\Model\Source;
 
class BlockPositionProduct implements \Magento\Framework\Data\OptionSourceInterface
{

    /**#@+
     * Product Status values
     */
    const InsteadOfNative = 1;
    const BeforeOfNative = 2;
    const AfterOfNative = 3;
    const ContentTop = 4;
    const ContentBottom = 5;
    const SidebarTop = 6;
    const SidebarBottom = 7;
    
    
    public static function getOptionArray()
    {
        return [
            self::InsteadOfNative => __('Instead of native related block'),
            self::BeforeOfNative => __('Before native related block'),
            self::AfterOfNative => __('After native related block'),
            self::ContentTop => __('Content top'),
            self::ContentBottom => __('Content bottom'),
            self::SidebarTop => __('Sidebar top'),
            self::SidebarBottom => __('Sidebar bottom')
        ];
    }
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        $availableOptions = self::getOptionArray();
        foreach ($availableOptions as $key => $value) {
            $options[] = [
                'label' => $value,
                'value' => $key,
            ];
        }
        return $options;
    }
    /**
     * Retrieve option text by option value
     *
     * @param string $optionId
     * @return string
     */
    public function getOptionText($optionId)
    {
        $options = self::getOptionArray();

        return isset($options[$optionId]) ? $options[$optionId] : null;
    }
}
