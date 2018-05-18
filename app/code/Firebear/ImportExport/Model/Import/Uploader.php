<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Import;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverPool;

class Uploader extends \Magento\CatalogImportExport\Model\Import\Uploader
{
    private $httpScheme = 'http://';

    /**
     * Uploader constructor.
     * @param \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb
     * @param \Magento\MediaStorage\Helper\File\Storage $coreFileStorage
     * @param \Magento\Framework\Image\AdapterFactory $imageFactory
     * @param \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $validator
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\Filesystem\File\ReadFactory $readFactory
     * @param \Firebear\ImportExport\Model\Filesystem\File\ReadFactory $fireReadFactory
     * @param null $filePath
     */
    public function __construct(
        \Magento\MediaStorage\Helper\File\Storage\Database $coreFileStorageDb,
        \Magento\MediaStorage\Helper\File\Storage $coreFileStorage,
        \Magento\Framework\Image\AdapterFactory $imageFactory,
        \Magento\MediaStorage\Model\File\Validator\NotProtectedExtension $validator,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\Filesystem\File\ReadFactory $readFactory,
        \Firebear\ImportExport\Model\Filesystem\File\ReadFactory $fireReadFactory,
        $filePath = null
    ) {
        parent::__construct(
            $coreFileStorageDb,
            $coreFileStorage,
            $imageFactory,
            $validator,
            $filesystem,
            $readFactory,
            $filePath
        );

        $this->_readFactory = $fireReadFactory;
    }

    public function move($fileName, $renameFileOff = false)
    {
        if ($renameFileOff) {
            $this->setAllowRenameFiles(false);
        }
        if (preg_match('/\bhttps?:\/\//i', $fileName, $matches)) {

            $url = str_replace($matches[0], '', $fileName);

            $urlProp = $this->parseUrl($this->httpScheme .$url);
             if (isset($urlProp['user']) && isset($urlProp['pass'])) {
             
                $hostname = $urlProp['host'];
                $path = $urlProp['path'];
                $name = str_replace("/", "_", $path);
                $ch = curl_init();
                $url = $this->httpScheme  . $hostname . $path;
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
                curl_setopt($ch, CURLOPT_BINARYTRANSFER,1);  
                curl_setopt($ch, CURLOPT_USERPWD, $urlProp['user'] . ":" . $urlProp['pass']);

                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt($ch, CURLOPT_UNRESTRICTED_AUTH, 1);

                $data = curl_exec($ch);
                $info = curl_getinfo($ch); 
                if ($data === FALSE) {
                //    error_log("cURL Error: " . curl_error($ch));

                }
                curl_close($ch);
               $this->_directory->writeFile($this->_directory->getAbsolutePath() . "var/cache/" . $name, $data);
               $read = $this->_readFactory->create($this->_directory->getAbsolutePath() . "var/cache/" . $name, DriverPool::FILE);
            } else {
                if ($matches[0] === $this->httpScheme) {
                    $read = $this->_readFactory->create($url, DriverPool::HTTP);
                } else {
                    $read = $this->_readFactory->create($url, DriverPool::HTTPS);
                }
            }
                $fileName = preg_replace('/[^a-z0-9\._-]+/i', '', $fileName . ".jpg");
                $this->_directory->writeFile(
                    $this->_directory->getRelativePath($this->getTmpDir() . '/' . $fileName),
                    $read->readAll()
                );
        }

        $filePath = $this->_directory->getRelativePath($this->getTmpDir() . '/' . $fileName);
        $this->_setUploadFile($filePath);
        $destDir = $this->_directory->getAbsolutePath($this->getDestDir());
        $result = $this->save($destDir);
        unset($result['path']);
        $result['name'] = self::getCorrectFileName($result['name']);

        return $result;
    }

    protected function parseUrl($path)
    {
        return parse_url($path);
    }
}
