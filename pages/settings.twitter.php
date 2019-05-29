<?php

/**
 * This file is part of the Feeds package.
 *
 * @author (c) Yakamara Media GmbH & Co. KG
 * @author thomas.blum@redaxo.org
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$func = rex_request('func', 'string');

if ($func == 'update') {
    $this->setConfig(rex_post('settings', [
        ['twitter_consumer_key', 'string'],
        ['twitter_consumer_secret', 'string'],
        ['twitter_oauth_token', 'string'],
        ['twitter_oauth_token_secret', 'string'],
    ]));

    echo \rex_view::success($this->i18n('settings_saved'));
}

$content = '';

$formElements = [];
$n = [];
$n['label'] = '<label for="consumer-token">' . $this->i18n('twitter_consumer_key') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="consumer-token" name="settings[twitter_consumer_key]" value="' . htmlspecialchars($this->getConfig('twitter_consumer_key')) . '" />';
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="consumer-secret">' . $this->i18n('twitter_consumer_secret') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="consumer-secret" name="settings[twitter_consumer_secret]" value="' . htmlspecialchars($this->getConfig('twitter_consumer_secret')) . '" />';
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="oauth-token">' . $this->i18n('twitter_oauth_token') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="oauth-token" name="settings[twitter_oauth_token]" value="' . htmlspecialchars($this->getConfig('twitter_oauth_token')) . '" />';
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="oauth-token-secret">' . $this->i18n('twitter_oauth_token_secret') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="oauth-token-secret" name="settings[twitter_oauth_token_secret]" value="' . htmlspecialchars($this->getConfig('twitter_oauth_token_secret')) . '" />';
$formElements[] = $n;

$fragment = new \rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/form.php');

$formElements = [];
$n = [];
$n['field'] = '<a class="btn btn-abort" href="' . \rex_url::currentBackendPage() . '">' . \rex_i18n::msg('form_abort') . '</a>';
$formElements[] = $n;

$n = [];
$n['field'] = '<button class="btn btn-apply rex-form-aligned" type="submit" name="send" value="1"' . \rex::getAccesskey(\rex_i18n::msg('update'), 'apply') . '>' . \rex_i18n::msg('update') . '</button>';
$formElements[] = $n;

$fragment = new \rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

$fragment = new \rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', $this->i18n('twitter_settings'), false);
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$section = $fragment->parse('core/page/section.php');

echo '
    <form action="' . \rex_url::currentBackendPage() . '" method="post">
        <input type="hidden" name="func" value="update" />
        ' . $section . '
    </form>
';
