<?php

/**
 * This file is part of the YFeed package.
 *
 * @author (c) Yakamara Media GmbH & Co. KG
 * @author thomas.blum@redaxo.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class rex_yfeed_helper
{
    /**
     * Generates the data uri for a remote resource
     *
     * @param string $url
     *
     * @return string
     */
    public static function getDataUri($url)
    {
        $response = rex_socket::factoryUrl($url)->doGet();
        $mimeType = $response->getHeader('content-type');
        $uri = 'data:'.$mimeType;

        if (0 === strpos($mimeType, 'text/')) {
            $uri .= ','.rawurlencode($response->getBody());
        } else {
            $uri .= ';base64,'.base64_encode($response->getBody());
        }

        return $uri;
    }
}
