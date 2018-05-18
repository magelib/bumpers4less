<?php

namespace Magedelight\Arp\Controller\Adminhtml\Index;

class Save extends \Magedelight\Arp\Controller\Adminhtml\Index\Rule
{
    public $dataObject;
    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     * @since 100.2.0
     */
    protected $serializer;
    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter
     * @param \Vendor\Rules\Model\RuleFactory $ruleFactory
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Stdlib\DateTime\Filter\Date $dateFilter,
        \Magedelight\Arp\Model\ProductruleFactory $ruleFactory,
        \Magento\Framework\DataObject $DataObject,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Serialize\Serializer\Json $serializer = null
    ) {
        parent::__construct($context, $coreRegistry, $fileFactory, $dateFilter, $ruleFactory, $logger);
        $this->dataObject = $DataObject;
        $this->serializer = $serializer ?: \Magento\Framework\App\ObjectManager::getInstance()->get(
            \Magento\Framework\Serialize\Serializer\Json::class
        );
    }

    /**
     * Rule save action
     *
     * @return void
     */
    public function execute() {
        if (!$this->getRequest()->getPostValue()) {
            $this->_redirect('*/*/');
        }
        /** @var $model \\Magedelight\Arp\Model\Productrule */
        $model = $this->ruleFactory->create();
        $this->_eventManager->dispatch(
                'adminhtml_controller_arp_rules_prepare_save', ['request' => $this->getRequest()]
        );
        $data = $this->getRequest()->getPostValue();


        $data['store_id'] = implode(',', $data['stores']);
        $data['customer_groups'] = implode(',', $data['customer_group_ids']);

        unset($data['stores']);

        unset($data['customer_group_ids']);

        if ($data['block_page'] == '1') {
            $data['block_position'] = $data['block_position_product'];
            unset($data['products_category']);
            unset($data['cms_page']);
        }

        if ($data['block_page'] == '2') {
            $data['block_position'] = $data['block_position_shoppingcart'];
            unset($data['products_category']);
            unset($data['cms_page']);
        }

        if ($data['block_page'] == '3') {
            $data['block_position'] = $data['block_position_category'];
            if (isset($data['products_category'][0])) {
                $data['products_category'] = implode(',', $data['products_category']);
            }
            unset($data['rule']['conditions']);
            unset($data['cms_page']);
        }
        if ($data['block_page'] == '4') {
            unset($data['products_category']);
            unset($data['cms_page']);
        }

        if ($data['block_page'] == '5') {
            $data['block_position'] = $data['block_position_cms'];
            if (isset($data['cms_page'][0])) {
               $data['cms_page'] = implode(',', $data['cms_page']);
            }                
            unset($data['rule']['conditions']);
            unset($data['products_category']);
        }

        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $model->load($id);
        }

        $validateResult = $model->validateData(new \Magento\Framework\DataObject($data));
        if ($validateResult !== true) {
            foreach ($validateResult as $errorMessage) {
                $this->messageManager->addErrorMessage($errorMessage);
            }
            $this->_session->setPageData($data);
            $this->_redirect('*/*/edit', ['id' => $model->getId()]);
            return;
        }

        if (isset($data['rule']['conditions'])) {
            $conditionsData = $this->getRequest()->getPostValue();
            $conditionsData['conditions'] = $conditionsData['rule']['conditions'];
            $conditionsModel = $this->_objectManager->create('Magedelight\Arp\Model\RuleCondition');
            $conditionDataArray = $conditionsModel->dataConvter($conditionsData);
            $data['conditions_serialized'] = $this->serializer->serialize($conditionDataArray);
            $conditionsModel->loadPost($conditionsData);
            $productIds_conditions = $conditionsModel->getListProductIdsInRule();
            if (!empty($productIds_conditions)) {
                $productIds_conditions = implode(",", $productIds_conditions);
            } else {
                $productIds_conditions = null;
            }
            $data['products_ids_conditions'] = $productIds_conditions;
        }
        if (isset($data['rule']['actions'])) {
            $actionsData = $this->getRequest()->getPostValue();
            $actionsData['conditions'] = $actionsData['rule']['actions'];
            $actionsModel = $this->_objectManager->create('Magedelight\Arp\Model\RuleActions');
            $actionDataArray = $actionsModel->dataConvter($actionsData);
            $data['actions_serialized'] = $this->serializer->serialize($actionDataArray);
            $actionsModel->loadPost($actionsData);
            $productIds_actions = $actionsModel->getListProductIdsInRule();
            if (!empty($productIds_actions)) {
                $productIds_actions = implode(",", $productIds_actions);
            } else {
                $productIds_actions = null;
            }
            $data['products_ids_actions'] = $productIds_actions;
        }
        unset($data['rule']);
        $model->setData($data);
        
        if($data['display_cart_button'] == 0) {
            $model->setData('display_cart_button', '2');
        }
        
        $useConfig = [];
        if (isset($data['use_config']) && !empty($data['use_config'])) {
            foreach ($data['use_config'] as $attributeCode => $attributeValue) {
                if ($attributeValue == 'true') {
                    $useConfig[] = $attributeCode;
                    $model->setData($attributeCode, '0');
                }
            }
        }
        
        //echo "<pre>"; print_r($data) ; exit;
        $model->save();
        $this->messageManager->addSuccessMessage(__('You saved the rule.'));
        $this->_session->setPageData($model->getData());
        $this->_session->setPageData(false);

        if ($this->getRequest()->getParam('back')) {
            $this->_redirect('*/*/edit', ['id' => $model->getId()]);
            return;
        }
        $this->_redirect('*/*/');
        return;
        
    }
}
