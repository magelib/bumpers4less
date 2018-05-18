<?php

namespace {
    include_once __DIR__ . "/AbstractResourceBase.php";
    /** @var \Unirgy\RapidFlow\Helper\Data $hlp */
    $hlp = \Magento\Framework\App\ObjectManager::getInstance()->get('\Unirgy\RapidFlow\Helper\Data');
    if ($hlp->compareMageVer('2.2.0-dev',null,'<')) {
        include_once __DIR__ . "/AbstractResource21.php";
    } else {
        include_once __DIR__ . "/AbstractResource22.php";
    }
}