<?php
/**
 * ITORIS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the ITORIS's Magento Extensions License Agreement
 * which is available through the world-wide-web at this URL:
 * http://www.itoris.com/magento-extensions-license.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to sales@itoris.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade the extensions to newer
 * versions in the future. If you wish to customize the extension for your
 * needs please refer to the license agreement or contact sales@itoris.com for more information.
 *
 * @category   ITORIS
 * @package    ITORIS_M2_DYNAMIC_PRODUCT_OPTIONS
 * @copyright  Copyright (c) 2016 ITORIS INC. (http://www.itoris.com)
 * @license    http://www.itoris.com/magento-extensions-license.html  Commercial License
 */

namespace Itoris\DynamicProductOptions\Helper;

use Magento\Framework\App\Filesystem\DirectoryList;

class Image extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * Media Directory object (writable).
     *
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $_mediaDirectory;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem $filesystem
    ){

        $this->_mediaDirectory = $filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        parent::__construct($context);
    }

    public function uploadFile($fileId) {
        $uploader = new  \Magento\Framework\File\Uploader($fileId);
        $uploader->setAllowRenameFiles(true);
        $uploader->setFilesDispersion(true);
        $dir = $this->checkFilesDir();
        $result = $uploader->save($dir);

        return $result;
    }

    /**
     * Create helpdesk dir if it not exists
     *
     * @return string
     */
    private function checkFilesDir() {
        $dir = $this->_mediaDirectory->getAbsolutePath() . DIRECTORY_SEPARATOR . 'itoris'. DIRECTORY_SEPARATOR . 'files';
        if (!is_dir($dir)) {
            @mkdir($dir);
        }
        /*
        $dir .=  DS . 'dynamicoptions';
        if (!is_dir($dir)) {
            @mkdir($dir);
        }
        */

        return $dir;
    }

    public function getBaseOptionsDir() {
        return $this->_mediaDirectory->getAbsolutePath(). DIRECTORY_SEPARATOR . 'itoris'. DIRECTORY_SEPARATOR . 'options';
    }
}