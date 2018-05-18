<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model\Source\Type;

use Kunnu\Dropbox\DropboxApp;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverPool;

class Dropbox extends AbstractType
{
    /**
     * @var string
     */
    protected $code = 'dropbox';

    /**
     * @var null
     */
    protected $accessToken = null;

    /**
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function uploadSource()
    {
        if ($client = $this->_getSourceClient()) {
            $sourceFilePath = $this->getData($this->code . '_file_path');
            $fileName = basename($sourceFilePath);
            $filePath = $this->directory->getAbsolutePath($this->getImportPath() . '/' . $fileName);
            try {
                $dirname = dirname($filePath);
                if (!is_dir($dirname)) {
                    mkdir($dirname, 0775, true);
                }
            } catch (\Exception $e) {
                throw new  \Magento\Framework\Exception\LocalizedException(
                    __(
                        "Can't create local file /var/import/dropbox'. Please check files permissions. "
                        . $e->getMessage()
                    )
                );
            }
            $fileMetadata = $client->download($sourceFilePath);
            file_put_contents($filePath, $fileMetadata->getContents());
            if ($fileMetadata) {
                return $this->getImportPath() . '/' . $fileName;
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__("File not found on Dropbox"));
            }
        } else {
            throw new  \Magento\Framework\Exception\LocalizedException(__("Can't initialize %s client", $this->code));
        }
    }

    /**
     * @param $importImage
     * @param $imageSting
     */
    public function importImage($importImage, $imageSting)
    {
        if ($client = $this->_getSourceClient()) {
            if (preg_match('/\bhttps?:\/\//i', $importImage, $matches)) {
                $this->setUrl($importImage, $imageSting, $matches);
            } else {
                $filePath = $this->directory->getAbsolutePath($this->getMediaImportPath() . $imageSting);
                $sourceFilePath = $this->getData($this->code . '_file_path');
                $sourceDir = dirname($sourceFilePath);
                $dirname = dirname($filePath);
                if (!is_dir($dirname)) {
                    mkdir($dirname, 0775, true);
                }
                try {
                    $fileMetadata = $client->download($sourceDir . '/' . $importImage);
                    file_put_contents($filePath, $fileMetadata->getContents());
                } catch (\Exception $e) {
                }
            }
        }
    }

    /**
     * Check if remote file was modified since the last import
     *
     * @param int $timestamp
     *
     * @return bool|int
     */
    public function checkModified($timestamp)
    {
        if ($client = $this->_getSourceClient()) {
            $sourceFilePath = $this->getData($this->code . '_file_path');

            if (!$this->metadata) {
                $this->metadata = $client->getMetadata($sourceFilePath);
            }

            $modified = strtotime($this->metadata->getClientModified());

            return ($timestamp != $modified) ? $modified : false;
        }

        return false;
    }

    /**
     * Set access token
     *
     * @param $token
     */
    public function setAccessToken($token)
    {
        $this->accessToken = $token;
    }

    /**
     * @return \Kunnu\Dropbox\Dropbox
     */
    protected function _getSourceClient()
    {
        if (!$this->client) {
            $app = new DropboxApp(
                $this->getData('app_key'),
                $this->getData('app_secret'),
                $this->getData('access_token')
            );
            $this->client = new \Kunnu\Dropbox\Dropbox($app);
        }

        return $this->client;
    }
}
