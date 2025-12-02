<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FriendsOfRedaxo\Feeds\Stream;

use rex_i18n;

class Mastodon extends Rss
{
    public function getTypeName()
    {
        return rex_i18n::msg('feeds_mastodon');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('feeds_mastodon_instance'),
                'name' => 'instance',
                'type' => 'string',
                'notice' => rex_i18n::msg('feeds_mastodon_instance_notice'),
            ],
            [
                'label' => rex_i18n::msg('feeds_mastodon_username'),
                'name' => 'username',
                'type' => 'string',
                'notice' => rex_i18n::msg('feeds_mastodon_username_notice'),
            ],
        ];
    }

    public function fetch()
    {
        $instance = rtrim($this->typeParams['instance'], '/');
        $username = trim($this->typeParams['username'], '@');

        $this->typeParams['url'] = "https://{$instance}/users/{$username}.rss";
        parent::fetch();
    }
}
