<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Finder
 */

/**
 * Copyright © 2016 Amasty. All rights reserved.
 */

namespace Amasty\Finder\Helper;


class Url
{
    const FINDER_URL_PARAM = 'find';


    public function getUrlWithFinderParam($targetUrl, $param)
    {
        $path  = $targetUrl;
        $query = array();

        if (strpos($targetUrl, '?') !== false){
            list($path, $query) = explode('?', $targetUrl, 2);
            if ($query){
                $query = explode('&', $query);
                $params = array();
                foreach ($query as $pair){
                    if (strpos($pair, '=') !== false){
                        $pair = explode('=', $pair);
                        $params[$pair[0]] = $pair[1];
                    }
                }
                $query = $params;
            }
        }

        $query[self::FINDER_URL_PARAM] = $param;

        $query = http_build_query($query);
        $query = str_replace('%2F', '/', $query);
        if ($query){
            $query = '?' . $query;
        }

        $backUrl = $path . $query;

        return $backUrl;
    }

    public function hasFinderParamInUri($targetUri)
    {
        if(
            strpos($targetUri, '&'.self::FINDER_URL_PARAM.'=') !== false ||
            strpos($targetUri, '?'.self::FINDER_URL_PARAM.'=') !== false ||
            strpos($targetUri, '&amp;'.self::FINDER_URL_PARAM.'=') !== false
        ) {
            return true;
        }
        return false;
    }

}
