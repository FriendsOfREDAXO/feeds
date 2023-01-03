<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
$addon = rex_addon::get('feeds');
$func = rex_request('func', 'string');

if ($func == 'update') {
    $addon->setConfig(rex_post('settings', [
        ['facebook_app_title', 'string'],
        ['facebook_app_id', 'string'],
        ['facebook_app_secret', 'string'],
    ]));

    echo \rex_view::success($addon->i18n('settings_saved'));
}

$content = '';

$formElements = [];
$n = [];
$n['label'] = '<label for="facebook-app-title">' . $addon->i18n('facebook_app_title') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="facebook-app-title" name="settings[facebook_app_title]" value="' . htmlspecialchars($addon->getConfig('facebook_app_title') ?? '') . '" />';
$n['note'] = $addon->i18n('facebook_app_title_note');
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="facebook-app-id">' . $addon->i18n('facebook_app_id') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="facebook-app-id" name="settings[facebook_app_id]" value="' . htmlspecialchars($addon->getConfig('facebook_app_id') ?? '') . '" />';
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="facebook-app-secret">' . $addon->i18n('facebook_app_secret') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="facebook-app-secret" name="settings[facebook_app_secret]" value="' . htmlspecialchars($addon->getConfig('facebook_app_secret') ?? '') . '" />';
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
$fragment->setVar('title', $addon->i18n('facebook_settings'), false);
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$section = $fragment->parse('core/page/section.php');

echo '
    <form action="' . \rex_url::currentBackendPage() . '" method="post">
        <input type="hidden" name="func" value="update" />
        ' . $section . '
    </form>
';
