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
        ['google_key', 'string'],
    ]));

    echo \rex_view::success($this->i18n('settings_saved'));
}

$content = '';

$formElements = [];
$n = [];
$n['label'] = '<label for="consumer-token">' . $this->i18n('google_key') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="consumer-token" name="settings[google_key]" value="' . htmlspecialchars($this->getConfig('google_key')) . '" />';
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
$fragment->setVar('title', $this->i18n('google_settings'), false);
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$section = $fragment->parse('core/page/section.php');

echo '
    <form action="' . \rex_url::currentBackendPage() . '" method="post">
        <input type="hidden" name="func" value="update" />
        ' . $section . '
    </form>
';
