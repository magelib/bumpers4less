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

namespace  Magedelight\Arp\Controller\Adminhtml\Index;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magedelight\Arp\Model\Productrule;
use Magento\Framework\Controller\Result\JsonFactory;

/**
 * Class InlineEdit
 *
 * @package Magedelight\Megamenu\Controller\Adminhtml\Menu
 */
class InlineEdit extends \Magento\Backend\App\Action
{
    
    /** @var PageFactory  */
    protected $pageFactory;

    /** @var JsonFactory  */
    protected $jsonFactory;
    public $productrule;

    /**
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param JsonFactory $jsonFactory
     */    
    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        Productrule $productrule,
        JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->productrule = $productrule;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        $postItems = $this->getRequest()->getParam('items', []);
        if (!($this->getRequest()->getParam('isAjax') && count($postItems))) {
            return $resultJson->setData([
                'messages' => [__('Please correct the data sent.')],
                'error' => true,
            ]);
        }
                
        $model = $this->productrule;
        
        foreach (array_keys($postItems) as $ruleId){
            try {
                if ($postItems[$ruleId]['rule_id']) {
                    $model->load($ruleId);
                    $model->setData($postItems[$ruleId]);
                    $model->save();
                    $messages[] = __('Menu saved.');
                }
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $messages[] = $this->getErrorWithRuleId($ruleId, $e->getMessage());
                $error = true;
            } catch (\RuntimeException $e) {
                $messages[] = $this->getErrorWithRuleId($ruleId, $e->getMessage());
                $error = true;
            } catch (\Exception $e) {
                $messages[] = $this->getErrorWithRuleId(
                    $ruleId,
                    __('Something went wrong while saving the menu.')
                );
                $error = true;
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
    /**
     * Add menu title to error message
     *
     * @param string $ruleId
     * @param string $errorText
     * @return string
     */
    protected function getErrorWithRuleId($ruleId, $errorText)
    {
        return '[Menu ID: ' . $ruleId . '] ' . $errorText;
    }
}
