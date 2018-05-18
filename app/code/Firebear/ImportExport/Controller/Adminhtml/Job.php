<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Registry;
use Firebear\ImportExport\Model\JobFactory;
use Firebear\ImportExport\Api\JobRepositoryInterface;

abstract class Job extends Action
{
    const ADMIN_RESOURCE = 'Firebear_ImportExport::job';

    /**
     * Core registry
     *
     * @var Registry
     */
    protected $coreRegistry = null;

    protected $resourceModel;

    /**
     * @var \Firebear\ImportExport\Model\JobFactory
     */
    protected $jobFactory;

    /**
     * @var JobRepositoryInterface
     */
    protected $repository;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        JobFactory $jobFactory,
        JobRepositoryInterface $repository
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->jobFactory = $jobFactory;
        $this->repository = $repository;
        parent::__construct($context);
    }
}
