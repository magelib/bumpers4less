<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export\Job;

class Add extends \Firebear\ImportExport\Controller\Adminhtml\Export\Job
{
    /**
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * Add constructor.
     *
     * @param \Magento\Backend\App\Action\Context                     $context
     * @param \Magento\Framework\Registry                             $coreRegistry
     * @param \Firebear\ImportExport\Model\ExportJobFactory           $exportJobFactory
     * @param \Firebear\ImportExport\Api\ExportJobRepositoryInterface $exportRepository
     * @param \Magento\Backend\Model\View\Result\ForwardFactory       $resultForwardFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Firebear\ImportExport\Model\ExportJobFactory $exportJobFactory,
        \Firebear\ImportExport\Api\ExportJobRepositoryInterface $exportRepository,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context, $coreRegistry, $exportJobFactory, $exportRepository);
    }

    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    }
}
