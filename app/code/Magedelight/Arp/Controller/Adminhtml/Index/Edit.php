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
 * @package Magedelight_Megamenu
 * @copyright Copyright (c) 2016 Mage Delight (http://www.magedelight.com/)
 * @license http://opensource.org/licenses/gpl-3.0.html GNU General Public License,version 3 (GPL-3.0)
 * @author Magedelight <info@magedelight.com>
 */

namespace Magedelight\Arp\Controller\Adminhtml\Index;

/**
 * Class Edit
 *
 * @package Magedelight\Megamenu\Controller\Adminhtml\Menu
 */
class Edit extends \Magedelight\Arp\Controller\Adminhtml\Index\Rule
{
    public function execute()
    {
         $id = $this->getRequest()->getParam('id');
        /** @var \Vendor\Rules\Model\Rule $model */
        $model = $this->ruleFactory->create();
 
        if ($id) {
            $model->load($id);
            if (!$model->getRuleId()) {
                $this->messageManager->addErrorMessage(__('This rule no longer exists.'));
                $this->_redirect('*/*/');
                return;
            }
        }
        // set entered data if was error when we do save
        $data = $this->_session->getPageData(true);
        
        if (!empty($data)) {
            $model->addData($data);
        }
        $model->setStoreId(explode(',', $model->getStoreId()));
        $model->setCustomerGroups(explode(',', $model->getCustomerGroups()));
        $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
        $this->coreRegistry->register('product_rule', $model);
        $this->_initAction();
        $this->_addBreadcrumb(
            $id ? __('Edit Rule') : __('New Product Rule'),
            $id ? __('Edit Rule') : __('New Product Rule')
        );
        $this->_view->getPage()->getConfig()->getTitle()->prepend(
            $model->getRuleId() ? $model->getName() : __('New Product Rule')
        );
        $this->_view->renderLayout();
    }
}
