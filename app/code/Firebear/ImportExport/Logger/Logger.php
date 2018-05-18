<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Logger;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Psr\Log\LoggerInterface;

/**
 * Web UI Logger
 *
 * @package Magento\Setup\Model
 */
class Logger implements LoggerInterface
{

    /**
     * Log File
     *
     * @var string
     */
    protected $logFile = '';

    /**
     * Currently open file resource
     *
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * Currently open file resource
     *
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $directory;

    /**
     * Indicator of whether inline output is started
     *
     * @var bool
     */
    private $isInline = false;

    /**
     * Constructor
     * @param Filesystem $filesystem
     * @param string $logFile
     */
    public function __construct(Filesystem $filesystem, $logFile = null)
    {
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::LOG);
        if ($logFile) {
            $this->logFile = $logFile;
        }
    }

    public function getFileName()
    {
        return $this->logFile;
    }

    public function setFileName($file)
    {
        $this->logFile = '/firebear/' . $file . '.log';
        $logDir = $this->directory->getDriver()->getParentDirectory(
            $this->directory->getAbsolutePath() . 'firebear/' . $file . '.log'
        );
        if (!$this->directory->getDriver()->isDirectory($logDir)) {
            $this->directory->getDriver()->createDirectory($logDir);
        }
        return $this;
    }

    public function emergency($message, array $context = [])
    {
        $this->terminateLine();
        $this->writeToFile($message);
    }

    public function alert($message, array $context = [])
    {
        $this->terminateLine();
        $this->writeToFile($message);
    }

    public function critical($message, array $context = [])
    {
        $this->terminateLine();
        $this->writeToFile($message);
    }

    public function error($message, array $context = [])
    {
        $this->terminateLine();
        $this->writeToFile($message);
    }

    public function warning($message, array $context = [])
    {
        $this->terminateLine();
        $this->writeToFile($message);
    }

    public function notice($message, array $context = [])
    {
        $this->terminateLine();
        $this->writeToFile($message);
    }

    public function info($message, array $context = [])
    {
        $this->terminateLine();
        $this->writeToFile($message);
    }

    public function debug($message, array $context = [])
    {
        $this->terminateLine();
        $this->writeToFile($message);
    }

    public function log($level, $message, array $context = [])
    {
        $this->terminateLine();
        $this->writeToFile($message);
    }

    /**
     * Write the message to file
     *
     * @param string $message
     * @return void
     */
    private function writeToFile($message)
    {
        $this->directory->writeFile($this->logFile, $message . "\r\n", 'a+');
    }

    /**
     * Gets contents of the log
     *
     * @return array
     */
    public function get()
    {
        $fileContents = explode(PHP_EOL, $this->directory->readFile($this->logFile));
        return $fileContents;
    }

    /**
     * Clears contents of the log
     *
     * @return void
     */
    public function clear()
    {
        if ($this->directory->isExist($this->logFile)) {
            $this->directory->delete($this->logFile);
        }
    }

    /**
     * Checks existence of install.log file
     *
     * @return bool
     */
    public function logfileExists()
    {
        return ($this->directory->isExist($this->logFile));
    }

    /**
     * Terminates line if the inline logging is started
     *
     * @return void
     */
    private function terminateLine()
    {
        if ($this->isInline) {
            $this->writeToFile('<br>');
        }
    }
}
