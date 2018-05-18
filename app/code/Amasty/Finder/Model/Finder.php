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

class Finder extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;

    /**
     * @var \Magento\Framework\App\Response\Http
     */
    protected $response;

    /**
     * Finder constructor.
     *
     * @param \Magento\Framework\Model\Context                             $context
     * @param \Magento\Framework\Registry                                  $registry
     * @param \Magento\Framework\ObjectManagerInterface                    $objectManager
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null           $resourceCollection
     * @param array                                                        $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Amasty\Finder\Model\Session $session,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Response\RedirectInterface $redirect,
        \Magento\Framework\App\Response\Http $response,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->objectManager = $objectManager;
        $this->session = $session;
        $this->scopeConfig = $scopeConfig;
        $this->redirect = $redirect;
        $this->response = $response;
        parent::__construct(
            $context, $registry, $resource, $resourceCollection, $data
        );
    }


    /**
     * Constructor
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Amasty\Finder\Model\ResourceModel\Finder');
        parent::_construct();
    }

    /**
     * @return \Amasty\Finder\Model\ResourceModel\Dropdown\Collection
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDropdowns()
    {
        /** @var \Amasty\Finder\Model\Dropdown $dropdown */
        $dropdown = $this->objectManager->create('Amasty\Finder\Model\Dropdown');
        $collection = $dropdown->getResourceCollection()->addFieldToFilter('finder_id', $this->getId());
        $collection->getSelect()->order('pos');

        return $collection;
    }

    public function saveFilter($dropdowns, $categoryId, $applyUrls)
    {
        if (!$dropdowns)
            return false;

        if (!is_array($dropdowns))
            return false;

        $safeValues = array();
        $id      = 0;
        $current = 0;
        foreach ($this->getDropdowns() as $d){
            $id = $d->getId();
            $safeValues[$id] = isset($dropdowns[$id]) ? $dropdowns[$id] : 0;
            if  (isset($dropdowns[$id]) && ($dropdowns[$id])){
                $current = $dropdowns[$id];
            }
        }

        if ($id) {
            $safeValues['last']    = $safeValues[$id];
            $safeValues['current'] = $current;
        }

        $safeValues['filter_category_id'] = $categoryId;
        $safeValues['apply_url'] = array_unique($applyUrls);
        $safeValues['url_param'] = $this->createUrlParam($safeValues);
        if ($safeValues['url_param']) {
            $this->session->setFinderData($this->getId(), $safeValues);
        }

        return true;
    }

    public function resetFilter()
    {
        $this->session->reset($this->getId());
        return true;
    }

    public function applyFilter(\Magento\Catalog\Model\Layer $layer, $isUniversal, $isUniversalLast)
    {
        $id = $this->getSavedValue('current');
        if (!$id) {
            return false;
        }

        if (!$this->isAllowedInCategory($layer->getCurrentCategory()->getId())) {
            return false;
        }

        $finderId = $this->getId();

        $collection = $layer->getProductCollection();
        $cnt = $this->countEmptyDropdowns();
        $this->getResource()->addConditionToProductCollection(
            $collection,
            $id,
            $cnt,
            $finderId,
            $isUniversal,
            $isUniversalLast
        );

        $isSingleProductRedirect = $this->scopeConfig->getValue(
            'amfinder/general/redirect_single_product',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if ($isSingleProductRedirect && count($collection->getItems()) == 1) {
            $url = $collection->getFirstItem()->getProductUrl();
            $this->redirect->redirect($this->response, $url);
        }

        return true;
    }

    public function getSavedValue($dropdownId)
    {
        $values = $this->session->getFinderData($this->getId());

        if (!is_array($values)) {
            return 0;
        }

        if (empty($values[$dropdownId])) {
            return 0;
        }

        return $values[$dropdownId];
    }

    public function importUniversal($file)
    {
        return $this->getResource()->importUniversal($this, $file);
    }

    public function updateLinks()
    {
        return $this->getResource()->updateLinks();
    }

    public function deleteMapRow($id)
    {
        return $this->getResource()->deleteMapRow($id);
    }

    public function isDeletable($id)
    {
        return $this->getResource()->isDeletable($id);
    }

    public function newSetterId($id)
    {
        return $this->getResource()->newSetterId($id);
    }

    public function countEmptyDropdowns()
    {
        $num = 0;

        // we assume the values are always exist.
        $values = $this->session->getFinderData($this->getId());
        foreach ($values as $k=>$dropdown){
            if (is_numeric($k) && !$dropdown){
                $num++;
            }
        }

        return $num;
    }

    public function getDropdownsByCurrent($current)
    {
        $dropdowns = array();
        while ($current){
            $valueModel = $this->objectManager->create('Amasty\Finder\Model\Value')->load($current);
            $dropdowns[$valueModel->getDropdownId()]= $current;
            $current = $valueModel->getParentId();
        }

        return $dropdowns;
    }

    public function getUrlParam()
    {
        $values = '';
        foreach ($this->session->getAllFindersData() as $value) {

            if (!is_array($value)) {
                return null;
            }

            if (empty($value['url_param'])) {
                return null;
            }

            $values .= !$values ? $value['url_param'] : '&' .$value['url_param'];
        }
        return $values;
    }

    /**
     * For current finder creates his description for URL
     *
     * @return string like year-make-model-number.html
     */
    protected function createUrlParam($values)
    {
        $sep    = $this->scopeConfig->getValue('amfinder/general/separator', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $suffix = $this->scopeConfig->getValue('amfinder/general/suffix', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);


        $urlParam = '';

        foreach ($values as $k => $value) {
            if ('current' == $k) {
                $urlParam .= $value . $suffix;
                break;
            }

            if (!empty($value) && is_numeric($k)){
                $valueModel =  $this->objectManager->create('Amasty\Finder\Model\Value')->load($value);
                if ($valueModel->getId()){
                    $urlParam .= strtolower(preg_replace('/[^\da-zA-Z]/', '-', $valueModel->getName())) . $sep;
                }
            }
        }
        if(empty($urlParam)) {
            $urlParam = null;
        }

        return $urlParam;
    }

    /**
     *  Get last `number` part from the year-make-model-number.html string
     *
     * @param string $param like year-make-model-number.html
     *
     * @return string like number
     */
    public function parseUrlParam($param)
    {
        $sep    = $this->scopeConfig->getValue('amfinder/general/separator', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        $suffix = $this->scopeConfig->getValue('amfinder/general/suffix', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $param = explode($sep, $param);
        $param = str_replace($suffix, '', $param[count($param)-1]);

        return $param;
    }



    public function removeGet($url, $name, $amp = true) {
        $url = str_replace("&amp;", "&", $url);
        list($urlPart, $qsPart) = array_pad(explode("?", $url), 2, "");
        parse_str($qsPart, $qsVars);
        unset($qsVars[$name]);

        if (count($qsVars) > 0) {
            $url = $urlPart."?".http_build_query($qsVars);
            if ($amp) {
                $url = str_replace("&", "&amp;", $url);
            }
        } else {
            $url = $urlPart;
        }
        return $url;
    }

    public function getInitialCategoryId()
    {
        $value = $this->session->getFinderData($this->getId());

        return isset($value['filter_category_id']) ? $value['filter_category_id'] : 0;
    }

    public function isAllowedInCategory($currentCategoryId)
    {
        $res = $this->scopeConfig->getValue('amfinder/general/category_search', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if (!$res) {
            return true;
        }

        if (!$this->getInitialCategoryId()) {
            return true;
        }

        return ($this->getInitialCategoryId() == $currentCategoryId);
    }


    public function afterDelete()
    {
        $this->objectManager->create('Amasty\Finder\Model\Import')->afterDeleteFinder($this->getId());
        return parent::afterDelete();
    }
}
