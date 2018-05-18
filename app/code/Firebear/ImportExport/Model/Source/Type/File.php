<?php

namespace Firebear\ImportExport\Model\Source\Type;

class File extends AbstractType
{
    /**
     * @var string
     */
    protected $code = 'file';

    /**
     * @return null
     */
    public function uploadSource()
    {
        return null;
    }

    /**
     * @param $importImage
     * @param $imageSting
     *
     * @return null
     */
    public function importImage($importImage, $imageSting)
    {
        return null;
    }

    /**
     * @param $timestamp
     *
     * @return null
     */
    public function checkModified($timestamp)
    {
        return null;
    }

    /**
     * @return null
     */
    protected function _getSourceClient()
    {
        return null;
    }

    /**
     * @param $model
     * @return array
     */
    public function run($model)
    {
        $result = true;
        $errors = [];
        try {
            $this->setExportModel($model);
            $data = $model->getData(\Firebear\ImportExport\Model\ExportJob\Processor::EXPORT_SOURCE);
            $this->writeFile($data['file_path']);
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            $result = false;
            $errors[] = __('Folder for import / export don\'t have enough permissions! Please set 775');
        }

        return [$result, $data['file_path'], $errors];
    }
}
