<?php

namespace Unirgy\RapidFlow\Model\ResourceModel;

abstract class AbstractResource extends AbstractResourceBase
{
    public function getMyConnection() {
        return $this->getConnection();
    }

    protected function getConnection() {
        return $this->_db->getConnection();
    }
}
