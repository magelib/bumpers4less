<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Model;


use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\Session\SaveHandlerInterface;
use Magento\Framework\Session\SidResolverInterface;
use Magento\Framework\Session\StorageInterface;
use Magento\Framework\Session\ValidatorInterface;

class Session extends \Magento\Framework\Session\SessionManager
{
    const SESSION_KEY = 'amfinder_saved_values';

    /**
     * @var array
     */
    protected $data = [];

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        SidResolverInterface $sidResolver, ConfigInterface $sessionConfig,
        SaveHandlerInterface $saveHandler, ValidatorInterface $validator,
        StorageInterface $storage,
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Framework\App\State $appState
    ) {
        parent::__construct(
            $request, $sidResolver, $sessionConfig, $saveHandler, $validator,
            $storage, $cookieManager, $cookieMetadataFactory, $appState
        );
        $this->loadData();
    }

    /**
     * @param int $finderID
     *
     * @return array|null
     */
    public function getFinderData($finderID)
    {
        if(isset($this->data[$finderID])) {
            return $this->data[$finderID];
        }
        return null;
    }

    /**
     * @param int $finderID
     * @param array $finderData
     *
     * @return $this
     */
    public function setFinderData($finderID, $finderData)
    {
        $this->data[$finderID] = $finderData;
        $this->saveData();
        return $this;
    }

    public function getAllFindersData()
    {
        return $this->data;
    }


    /**
     * @param int $finderID
     *
     * @return $this
     */
    public function reset($finderID)
    {
        unset($this->data[$finderID]);
        $this->saveData();
        return $this;
    }


    protected function saveData()
    {
        $this->setData(self::SESSION_KEY, $this->data);
    }

    protected function loadData()
    {
        $this->data = $this->getData(self::SESSION_KEY);
    }
}
