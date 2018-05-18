<?php
/**
 * @copyright: Copyright © 2017 Firebear Studio. All rights reserved.
 * @author   : Firebear Studio <fbeardev@gmail.com>
 */

namespace Firebear\ImportExport\Traits;

use Symfony\Component\Console\Output\OutputInterface;
use Magento\ImportExport\Model\Import;

trait General
{
    /**
     * @param $debugData
     * @param OutputInterface|null $output
     * @param null $type
     * @return $this
     */
    public function addLogWriteln($debugData, OutputInterface $output = null, $type = null)
    {
        $text = $debugData;
        if ($debugData instanceof \Magento\Framework\Phrase) {
            $text = $debugData->__toString();
        }
        $this->_logger->info($text);
        if ($output) {
            switch ($type) {
                case 'error':
                    $debugData = '<error>' . $text . '</error>';
                    break;
                case 'info':
                    $debugData = '<info>' . $text . '</info>';
                    break;
                default:
                    $debugData = '<comment>' . $text . '</comment>';
                    break;
            }
            $output->writeln($text);
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getDuplicateFields()
    {
        return $this->duplicateFields;
    }

    /**
     * @param $logger
     * @return $this
     */
    public function setLogger($logger)
    {
        $this->_logger = $logger;

        return $this;
    }

    /**
     * @param $errorAggregator
     */
    public function setErrorAggregator($errorAggregator)
    {
        return $this->errorAggregator = $errorAggregator;
    }

    /**
     * import product data
     */
    public function importDataPart($file, $offset, $job)
    {
        $this->_dataSourceModel->setFile($file);
        $this->_dataSourceModel->setJob($job);
        $this->_dataSourceModel->setOffset($offset);

        $this->importData();

        return true;
    }

    /**
     * @param int $saveBunches
     * @return mixed
     */
    public function validateData($saveBunches = 1)
    {
        if (isset($this->_parameters['output'])) {
            $this->output = $this->_parameters['output'];
        }

        if (!$this->_dataValidated) {
            $this->getErrorAggregator()->clear();
            // do all permanent columns exist?
            $absentColumns = array_diff($this->_permanentAttributes, $this->getSource()->getColNames());
            $this->addErrors(self::ERROR_CODE_COLUMN_NOT_FOUND, $absentColumns);

            if (Import::BEHAVIOR_DELETE != $this->getBehavior()) {
                // check attribute columns names validity
                $columnNumber = 0;
                $emptyHeaderColumns = [];
                $invalidColumns = [];
                $invalidAttributes = [];
                foreach ($this->getSource()->getColNames() as $columnName) {
                    $this->addLogWriteln(__('Checked column %1', $columnNumber), $this->output);
                    $isNewAttribute = true;
                    $columnNumber++;
                    if (!$this->isAttributeParticular($columnName)) {
                        if (trim($columnName) == '') {
                            $emptyHeaderColumns[] = $columnNumber;
                        } elseif (!preg_match('/^[a-z][a-z0-9_]*$/', $columnName)) {
                            $invalidColumns[] = $columnName;
                        } elseif ($this->needColumnCheck && !in_array($columnName, $this->getValidColumnNames())) {
                            $invalidAttributes[] = $columnName;
                        }
                    }
                }


                $this->addErrors(self::ERROR_CODE_INVALID_ATTRIBUTE, $invalidAttributes);
                $this->addErrors(self::ERROR_CODE_COLUMN_EMPTY_HEADER, $emptyHeaderColumns);
                $this->addErrors(self::ERROR_CODE_COLUMN_NAME_INVALID, $invalidColumns);
                $this->addLogWriteln(__('Finish checking columns'), $this->output);
                $this->addLogWriteln(
                    __('Errors count: %1', $this->getErrorAggregator()->getErrorsCount()),
                    $this->output
                );
            }

            if (!$this->getErrorAggregator()->getErrorsCount()) {
                if ($saveBunches) {
                    $this->addLogWriteln(__('Start saving bunches'), $this->output);
                    $this->_saveValidatedBunches();
                    $this->addLogWriteln(__('Finish saving bunches'), $this->output);
                }
                $this->_dataValidated = true;
            }
        }
        return $this->getErrorAggregator();
    }


    public function setErrorMessages()
    {
        return true;
    }

    public function setOutput($output)
    {
        $this->output = $output;
    }

    public function joinIdenticalyData($data)
    {
        $reverts = [];
        foreach ($this->_parameters['map'] as $item) {
            if ($item['import']) {
                $reverts[$item['import']][] = $item['system'];
            }
        }
        if (!empty($this->_parameters['identicaly'])) {
            foreach ($this->_parameters['identicaly'] as $elem) {
                $data[$elem['system']] = $data[$reverts[$elem['import']][0]];
            }
        }

        return $data;
    }
}
