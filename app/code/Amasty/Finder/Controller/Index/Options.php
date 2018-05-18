<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Controller\Index;


use Magento\Framework\App\Action\Context;

class Options extends \Magento\Framework\App\Action\Action
{
    protected $jsonEncoder;
    public function __construct(Context $context, \Magento\Framework\Json\EncoderInterface $jsonEncoder)
    {
        $this->jsonEncoder = $jsonEncoder;
        parent::__construct($context);
    }


    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        $parentId = $this->getRequest()->getParam('parent_id', false);
        $dropdownId = $this->getRequest()->getParam('dropdown_id');
        $useSavedValues = $this->getRequest()->getParam('use_saved_values', "false");
        $options = [];

        if ($parentId !== false && $dropdownId) {
            /** @var \Amasty\Finder\Model\Dropdown $dropdown */
            $dropdown = $this->_objectManager->create('Amasty\Finder\Model\Dropdown')->load($dropdownId);
            $selectedValue = 0;
            if($useSavedValues === "true") {
                $selectedValue = $dropdown->getFinder()->getSavedValue($dropdown->getId());
            }
            $options  = $dropdown->getValues($parentId, $selectedValue);

            if(count($options) == 2) {
                $options[1]['selected'] = true;
            }
        }

        $response = $this->jsonEncoder->encode($options);
        return $this->getResponse()->setBody($response);
    }
}
