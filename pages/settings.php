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

if ('save' === $func) {
    $settings = [
        'http_timeout' => rex_request('http_timeout', 'int', 10),
        'http_max_duration' => rex_request('http_max_duration', 'int', 30),
        'media_max_size' => rex_request('media_max_size', 'int', 50),
        'log_level' => rex_request('log_level', 'string', 'warning'),
    ];
    
    // Validate
    if ($settings['http_timeout'] < 5 || $settings['http_timeout'] > 120) {
        echo rex_view::error($this->i18n('feeds_settings_timeout_range'));
    } elseif ($settings['http_max_duration'] < $settings['http_timeout']) {
        echo rex_view::error($this->i18n('feeds_settings_duration_min'));
    } elseif ($settings['media_max_size'] < 1 || $settings['media_max_size'] > 500) {
        echo rex_view::error($this->i18n('feeds_settings_media_size_range'));
    } else {
        foreach ($settings as $key => $value) {
            $addon->setConfig($key, $value);
        }
        echo rex_view::success($this->i18n('feeds_settings_saved'));
    }
}

echo '<form action="' . rex_url::currentBackendPage() . '" method="post">';

// HTTP & Network Settings Section
$content = [];
$formElements = [];

$n = [];
$n['label'] = '<label for="feeds-http-timeout">' . $this->i18n('feeds_settings_http_timeout') . '</label>';
$n['field'] = '<div class="input-group"><input class="form-control" id="feeds-http-timeout" type="number" name="http_timeout" value="' . $addon->getConfig('http_timeout', 10) . '" min="5" max="120" /><span class="input-group-addon">Sek.</span></div>';
$n['note'] = $this->i18n('feeds_settings_http_timeout_note');
$formElements[] = $n;

$n = [];
$n['label'] = '<label for="feeds-http-max-duration">' . $this->i18n('feeds_settings_http_max_duration') . '</label>';
$n['field'] = '<div class="input-group"><input class="form-control" id="feeds-http-max-duration" type="number" name="http_max_duration" value="' . $addon->getConfig('http_max_duration', 30) . '" min="10" max="180" /><span class="input-group-addon">Sek.</span></div>';
$n['note'] = $this->i18n('feeds_settings_http_max_duration_note');
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content[] = $fragment->parse('core/form/form.php');

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', '<i class="rex-icon fa-globe"></i> ' . $this->i18n('feeds_settings_http'), false);
$fragment->setVar('body', implode('', $content), false);
echo $fragment->parse('core/page/section.php');

// Media Settings Section
$content = [];
$formElements = [];

$n = [];
$n['label'] = '<label for="feeds-media-max-size">' . $this->i18n('feeds_settings_media_max_size') . '</label>';
$n['field'] = '<div class="input-group"><input class="form-control" id="feeds-media-max-size" type="number" name="media_max_size" value="' . $addon->getConfig('media_max_size', 50) . '" min="1" max="500" /><span class="input-group-addon">MB</span></div>';
$n['note'] = $this->i18n('feeds_settings_media_max_size_note');
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content[] = $fragment->parse('core/form/form.php');

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', '<i class="rex-icon fa-picture-o"></i> ' . $this->i18n('feeds_settings_media'), false);
$fragment->setVar('body', implode('', $content), false);
echo $fragment->parse('core/page/section.php');

// Logging Settings Section
$content = [];
$formElements = [];

$n = [];
$n['label'] = '<label for="feeds-log-level">' . $this->i18n('feeds_settings_log_level') . '</label>';
$select = new rex_select();
$select->setId('feeds-log-level');
$select->setName('log_level');
$select->setAttribute('class', 'form-control selectpicker');
$select->addOption($this->i18n('feeds_log_level_error'), 'error');
$select->addOption($this->i18n('feeds_log_level_warning'), 'warning');
$select->addOption($this->i18n('feeds_log_level_info'), 'info');
$select->setSelected($addon->getConfig('log_level', 'warning'));
$n['field'] = $select->get();
$n['note'] = $this->i18n('feeds_settings_log_level_note');
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content[] = $fragment->parse('core/form/form.php');

$fragment = new rex_fragment();
$fragment->setVar('class', 'edit', false);
$fragment->setVar('title', '<i class="rex-icon fa-file-text-o"></i> ' . $this->i18n('feeds_settings_logging'), false);
$fragment->setVar('body', implode('', $content), false);
echo $fragment->parse('core/page/section.php');

// Submit Button
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="func" value="save"><i class="rex-icon rex-icon-save"></i> ' . $this->i18n('feeds_settings_save') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

$fragment = new rex_fragment();
$fragment->setVar('buttons', $buttons, false);
echo $fragment->parse('core/page/section.php');

echo '</form>';
