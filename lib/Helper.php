<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FriendsOfRedaxo\Feeds;

use rex_socket;

class Helper
{
    /**
     * Generates the data uri for a remote resource.
     *
     * @param string $url
     *
     * @return string
     */
    public static function getDataUri($url)
    {
        $response = rex_socket::factoryUrl($url)->doGet();
        $mimeType = $response->getHeader('content-type');
        $uri = 'data:' . $mimeType;

        if (str_starts_with($mimeType, 'text/')) {
            $uri .= ',' . rawurlencode($response->getBody());
        } else {
            $uri .= ';base64,' . base64_encode($response->getBody());
        }

        return $uri;
    }
}
