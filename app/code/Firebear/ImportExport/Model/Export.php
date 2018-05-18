<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use \Firebear\ImportExport\Model\Source\Type\File\Config;

class Export extends \Magento\ImportExport\Model\Export
{
    use \Firebear\ImportExport\Traits\General;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var \Firebear\ImportExport\Model\Export\Dependencies\Config
     */
    protected $configExDi;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @var Config
     */
    protected $fireExportConfig;

    protected $_debugMode;

    /**
     * Export constructor.
     *
     * @param \Psr\Log\LoggerInterface                           $logger
     * @param \Magento\Framework\Filesystem                      $filesystem
     * @param \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig
     * @param \Magento\ImportExport\Model\Export\Entity\Factory  $entityFactory
     * @param \Magento\ImportExport\Model\Export\Adapter\Factory $exportAdapterFac
     * @param ScopeConfigInterface                               $scopeConfig
     * @param array                                              $data
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\ImportExport\Model\Export\ConfigInterface $exportConfig,
        \Magento\ImportExport\Model\Export\Entity\Factory $entityFactory,
        \Magento\ImportExport\Model\Export\Adapter\Factory $exportAdapterFac,
        \Firebear\ImportExport\Helper\Data $helper,
        ConsoleOutput $output,
        ScopeConfigInterface $scopeConfig,
        \Firebear\ImportExport\Model\Export\Dependencies\Config $configExDi,
        \Firebear\ImportExport\Model\Source\Type\File\Config $fireExportConfig,
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->configExDi = $configExDi;
        $this->output = $output;
        $this->fireExportConfig = $fireExportConfig;
        parent::__construct($logger, $filesystem, $exportConfig, $entityFactory, $exportAdapterFac, $data = []);
        $this->_debugMode = $helper->getDebugMode();
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getEntityAdapter()
    {
        $types = $this->configExDi->get();
        foreach ($types as $typeName => $type) {
            if ($typeName == $this->getEntity()) {
                $this->setModel($type['model']);

                return $this->_entityAdapter;
            }
        }

        parent::_getEntityAdapter();

        if ($entity = $this->scopeConfig->getValue(
            'firebear_importexport/entities/' . $this->getEntity(),
            ScopeInterface::SCOPE_STORE
        )
        ) {
            $this->setModel($entity);
        }

        return $this->_entityAdapter;
    }

    /**
     * Export data.
     *
     * @return string
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function export()
    {
        if (isset($this->_data[self::FILTER_ELEMENT_GROUP])) {
            $this->addLogComment(__('Begin export of %1', $this->getEntity()));

            list($result, $count) = $this->_getEntityAdapter()->setLogger($this->_logger)->setWriter($this->_getWriter())->export();

           // $countRows = substr_count(trim($result), $this->getExpersion());
            $countRows = $count;
            if (!$countRows) {
                $this->addLogComment([__('There is no data for the export.')]);
               
                return false;
            }

            if ($result) {
                $this->addLogComment([__('Exported %1 items.', $countRows), __('The export is finished.')]);
            }

            return $result;
        } else {
            throw new \Magento\Framework\Exception\LocalizedException(__('Please provide filter data.'));
        }
    }

    /**
     * @param $entity
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function setModel($entity)
    {
        try {
            $this->_entityAdapter = $this->_entityFactory->create($entity);
            $this->_entityAdapter->setParameters($this->getData());
        } catch (\Exception $e) {
            $this->_logger->critical($e);
            $this->addLogWriteln($e->getMessage(), $this->output, 'error');
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Please enter a correct entity model.')
            );
        }
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _getWriter()
    {
        if (!$this->_writer) {
            $data = $this->fireExportConfig->get();
            $fileFormats = $data['export'];
            if (isset($fileFormats[$this->getFileFormat()])) {
                try {
                    $this->_writer = $this->_exportAdapterFac->create($fileFormats[$this->getFileFormat()]['model']);
                } catch (\Exception $e) {
                    $this->_logger->critical($e);
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('Please enter a correct entity model.')
                    );
                }
                if (!$this->_writer instanceof \Magento\ImportExport\Model\Export\Adapter\AbstractAdapter) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __(
                            'The adapter object must be an instance of %1.',
                            'Magento\ImportExport\Model\Export\Adapter\AbstractAdapter'
                        )
                    );
                }
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(__('Please correct the file format.'));
            }
        }
        if (in_array($this->getData('file_format'), ['csv', 'txt'])) {
            if ($data = $this->getData('behavior_data')) {
                if (isset($data['separator'])) {
                    $this->_writer->setDelimeter($data['separator']);
                }
                if ($this->getData('file_format') == 'csv') {
                    if (isset($data['enclosure'])) {
                        $this->_writer->setEnclosure($data['enclosure']);
                    }
                }
            }
        }

        return $this->_writer;
    }

    /**
     * @return string
     */
    public function getExpersion()
    {
        $str = PHP_EOL;
        if ($this->getData('file_format') == 'xml') {
            $str = '<item>';
        }

        return $str;
    }

    /**
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getFields()
    {
        return $this->_getEntityAdapter()->getFieldsForExport();
    }

    /**
     * @param mixed $debugData
     * @return $this
     */
    public function addLogComment($debugData)
    {
     
        if (is_array($debugData)) {
            $this->_logTrace = array_merge($this->_logTrace, $debugData);
        } else {
            $this->_logTrace[] = $debugData;
        }
  
        if (is_scalar($debugData)) {
            $this->_logger->debug($debugData);
            $this->output->writeln($debugData);
        } else {
            foreach ($debugData as $message) {
                if ($message instanceof \Magento\Framework\Phrase) {
                    $this->output->writeln($message->__toString());
                    $this->_logger->debug($message->__toString());
                } else {
                    $this->output->writeln($message);
                    $this->_logger->debug($message);
                }
            }
        }

        return $this;
    }

    /**
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;
    }
}
