<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Block\Adminhtml\Finder\Edit\Tab\Import\Renderer;


use Magento\Framework\DataObject;

class RunButton extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    protected function _getValue(DataObject $row)
    {
        $url = $this->getUrl('*/finder/runFile', array('_current'=>true, 'file_id'=>$row->getId()));
        $html = '
		<button
		title="'.__('Import').'" type="button" data-url="'.$url.'" data-progress="'.$row->getProgress().'" class="scalable button-import'. ($row->getIsLocked() ? 'disabled' : '').'" onclick="return false;" style=""
		'. ($row->getIsLocked() ? 'disabled' : '').'>
		<span><span><span>'.__('Import').'</span></span></span>
		</button>
		';
        return $html;
    }
}
