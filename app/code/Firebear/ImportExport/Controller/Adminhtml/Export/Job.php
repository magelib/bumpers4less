<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml\Export;

/**
 * Class ExportJob
 *
 * @package Firebear\ImportExport\Controller\Adminhtml
 */
abstract class Job extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Firebear_ImportExport::export_job';

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Firebear\ImportExport\Model\ExportJobFactory
     */
    protected $exportJobFactory;

    /**
     * @var \Firebear\ImportExport\Api\ExportJobRepositoryInterface
     */
    protected $exportRepository;

    /**
     * Job constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Firebear\ImportExport\Model\ExportJobFactory $exportJobFactory
     * @param \Firebear\ImportExport\Api\ExportJobRepositoryInterface $exportRepository
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Firebear\ImportExport\Model\ExportJobFactory $exportJobFactory,
        \Firebear\ImportExport\Api\ExportJobRepositoryInterface $exportRepository
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->exportJobFactory = $exportJobFactory;
        $this->exportRepository = $exportRepository;
        parent::__construct($context);
    }
}
