<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_XmlSitemap
 */


namespace Amasty\XmlSitemap\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class Url extends Column
{
    /** @var \Magento\Framework\App\Filesystem\DirectoryList $_directoryList */
    protected $_directoryList;

    /** @var \Magento\Store\Model\StoreManagerInterface $_storeManager */
    protected $_storeManager;

    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        array $components = [],
        array $data = []
    )
    {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->_directoryList = $directoryList;
        $this->_storeManager = $storeManager;
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        foreach ($dataSource['data']['items'] as $key => $item) {
            if (file_exists(BP . DIRECTORY_SEPARATOR . $item['folder_name'])) {
                $url = $this->_storeManager->getStore()->getBaseUrl() . $item['folder_name'];
                $dataSource['data']['items'][$key]['result_link'] = "<a href=\"$url\" target=\"_blank\">$url</a>";
            }
        }

        return $dataSource;
    }
}
