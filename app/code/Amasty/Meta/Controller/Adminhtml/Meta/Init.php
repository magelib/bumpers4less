<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Meta
 */

namespace Amasty\Meta\Controller\Adminhtml\Meta;

class Init extends \Amasty\Meta\Controller\Adminhtml\Meta
{
    public function execute()
    {
        $request  = $this->getRequest();
        $key = $request->getParam('store_key');
        $storeKeys = array(($key ? $key : 'admin'));
        $total = $this->_helperUrl->estimate($storeKeys);

        $this->getResponse()->setBody(
            $this->jsonHelper->jsonEncode(array(
                'total' => $total,
                'page_size' => $this->_helperUrl->getPageSize()
            ))
        );


    }
}
