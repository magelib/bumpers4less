<?php

namespace Unirgy\RapidFlow\Model\Product;

use \Magento\Catalog\Model\Product\Image as BaseImage;
use \Magento\Catalog\Model\Product;

class Image extends BaseImage
{
    public function urfRemoveFile($filename)
    {
        if ($this->_mediaDirectory->isFile($filename)) {
            $this->_mediaDirectory->delete($filename);
        }
    }
}