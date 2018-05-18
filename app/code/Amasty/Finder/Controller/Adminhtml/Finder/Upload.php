<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Controller\Adminhtml\Finder;


class Upload extends \Amasty\Finder\Controller\Adminhtml\Finder
{
    use \Amasty\Finder\MyTrait\FinderController;

    protected $jsonEncoder;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder
    ) {
        parent::__construct(
            $context, $coreRegistry, $resultForwardFactory, $resultPageFactory
        );
        $this->jsonEncoder = $jsonEncoder;
    }

    public function execute()
    {
        $finder = $this->_initFinder();
        $isDeleteExistingData = $this->getRequest()->getParam('delete_existing_data');
        $newFileName = $isDeleteExistingData ? 'replace.csv' : null;
        $error = null;
        $content = "";

        try {
            $fileName = $this->_objectManager->create('Amasty\Finder\Model\Import')->upload('file', $finder->getId(), $newFileName);
            $content = __('The file %s has been uploaded', $fileName);
            if($fileName == 'replace.csv') {
                $content .= __(', all other files in the queue have been removed');
            }
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }

        $response = array('status'=>'ok');
        if(!is_null($error)) {
            $response = array("error"=>$error);
        }
        $response = $this->jsonEncoder->encode($response);
        return $this->getResponse()->setBody($response);
    }
}
