<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use FriendsOfRedaxo\Feeds\Item;
use FriendsOfRedaxo\Feeds\Stream;

$func = rex_request('func', 'string');
$id = rex_request('id', 'integer');

if ('setstatus' == $func) {
    $status = (rex_request('oldstatus', 'int') + 1) % 2;
    rex_sql::factory()
        ->setTable(Item::table())
        ->setWhere('id = :id', ['id' => $id])
        ->setValue('status', $status)
        ->addGlobalUpdateFields()
        ->update();
    echo rex_view::success($this->i18n('item_status_saved'));
    $func = '';
}

if ('' == $func) {
    // Suchparameter und Filter
    $search = rex_request('search', 'string', '');
    $namespace_filter = rex_request('namespace_filter', 'string', '');

    // Hole verfügbare Namespaces für den Filter
    $sql = rex_sql::factory();
    $namespaces = $sql->getArray('SELECT DISTINCT namespace FROM ' . Stream::table() . ' ORDER BY namespace');

    // Base Query
    $query = "SELECT
                i.id,
                s.namespace,
                i.date,
                i.media_filename,
                s.type,
                (CASE WHEN (i.title IS NULL or i.title = '')
                    THEN i.content
                    ELSE i.title
                END) as title,
                i.url,
                i.status,
                i.author,
                s.type as stream_type
            FROM
                " . Item::table() . ' AS i
                LEFT JOIN
                    ' . Stream::table() . ' AS s
                    ON  i.stream_id = s.id';

    // WHERE Bedingungen
    $where = [];
    if ($search) {
        $where[] = "(i.title LIKE '%" . $search . "%'
                    OR i.content LIKE '%" . $search . "%'
                    OR s.namespace LIKE '%" . $search . "%'
                    OR i.author LIKE '%" . $search . "%')";
    }
    if ($namespace_filter) {
        $where[] = "s.namespace = '" . $namespace_filter . "'";
    }

    // WHERE Bedingungen zusammenführen
    if (!empty($where)) {
        $query .= ' WHERE ' . implode(' AND ', $where);
    }

    $query .= ' ORDER BY i.date DESC, id DESC';

    $list = rex_list::factory($query);

    // Parameter an Liste übergeben
    if ($search) {
        $list->addParam('search', $search);
    }
    if ($namespace_filter) {
        $list->addParam('namespace_filter', $namespace_filter);
    }

    // Suchformular mit Filter erstellen
    $searchForm = '
    <form action="' . rex_url::currentBackendPage() . '" method="get">
        <input type="hidden" name="page" value="feeds/items" />
        <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <select class="form-control selectpicker" data-live-search="true" name="namespace_filter" onchange="this.form.submit()">
                        <option value="">' . rex_i18n::msg('feeds_all_namespaces') . '</option>';
    foreach ($namespaces as $n) {
        $searchForm .= '<option value="' . htmlspecialchars($n['namespace']) . '"'
            . ($namespace_filter == $n['namespace'] ? ' selected' : '')
            . '>' . htmlspecialchars($n['namespace']) . '</option>';
    }
    $searchForm .= '</select>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="input-group">
                    <input class="form-control" type="text" name="search" value="' . htmlspecialchars($search) . '" placeholder="' . rex_i18n::msg('feeds_search_term') . '" />
                    <span class="input-group-btn">
                        <button class="btn btn-primary" type="submit"><i class="rex-icon fa-search"></i> ' . rex_i18n::msg('feeds_search') . '</button>
                        ' . (($search || $namespace_filter) ? '<a class="btn btn-default" href="' . rex_url::currentBackendPage() . '"><i class="rex-icon fa-times"></i> ' . rex_i18n::msg('feeds_clear') . '</a>' : '') . '
                    </span>
                </div>
            </div>
            ' . (($search || $namespace_filter) ? '
            <div class="col-sm-12">
                <div class="alert alert-info">
                    ' . rex_i18n::msg('feeds_search_results') . ': ' . $list->getRows() . '
                </div>
            </div>' : '') . '
        </div>
    </form>';

    $fragment = new rex_fragment();
    $fragment->setVar('body', $searchForm, false);
    $searchPanel = $fragment->parse('core/page/section.php');

    $list->addTableAttribute('class', 'table-striped');

    $list->addColumn('', '', 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams('', ['func' => 'edit', 'id' => '###id###']);
    $list->setColumnFormat('', 'custom', static function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        $type = explode('_', $list->getValue('stream_type'));
        $icon = 'fa-paper-plane-o';
        if (isset($type[0])) {
            $icon = match ($type[0]) {
                'rss' => 'fa-rss',
                'twitter' => 'fa-twitter',
                'facebook' => 'fa-facebook',
                'youtube' => 'fa-youtube',
                'instagram' => 'fa-instagram',
                'google' => 'fa-google',
                'vimeo' => 'fa-video-camera',
                default => $icon,
            };
            return $list->getColumnLink('', '<i class="rex-icon ' . $icon . (($list->getValue('status')) ? '' : ' text-muted') . '"></i>');
        }
    });

    $list->removeColumn('id');
    $list->removeColumn('url');
    $list->removeColumn('stream_type');

    $list->setColumnLabel('date', $this->i18n('item_date'));
    $list->setColumnFormat('date', 'custom', static function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        return rex_formatter::strftime($list->getValue('date'), 'datetime');
    });
    $list->setColumnSortable('date');

    $list->setColumnLabel('namespace', $this->i18n('stream_namespace'));
    $list->setColumnFormat('namespace', 'custom', static function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        $namespace = $list->getValue('namespace');
        $type = $list->getValue('stream_type');
        $out = $namespace . '<br /><small>' . $type . '</small>';
        $out = '<span class="type' . (($list->getValue('status')) ? '' : ' text-muted') . '">' . $out . '</span>';
        return $out;
    });
    $list->setColumnSortable('namespace');

    $list->setColumnLabel('title', $this->i18n('item_title'));
    $list->setColumnFormat('title', 'custom', static function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        $title = $list->getValue('title');
        if (null === $title) {
            $title = '';
        }
        $title = rex_formatter::truncate($title, ['length' => 140]);
        $title .= ('' != $list->getValue('url')) ? '<br /><small><a href="' . $list->getValue('url') . '" target="_blank">' . $list->getValue('url') . '</a></small>' : '';
        $title = '<div class="rex-word-break"><span class="title' . (($list->getValue('status')) ? '' : ' text-muted') . '">' . $title . '</span></div>';
        return $title;
    });

    $list->setColumnLabel('media_filename', $this->i18n('item_media'));
    $list->setColumnFormat('media_filename', 'custom', static function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        $item = Item::get($list->getValue('id'));

        if ($item && $item->getMediaFilename()) {
            $media_url = $item->getMediaManagerUrl('feeds_thumb');
            return '<img class="thumbnail" src="' . $media_url . '" width="60" height="60" alt="" title="" loading="lazy">';
        }
        return '';
    });

    $list->setColumnLabel('author', $this->i18n('item_author'));
    $list->setColumnSortable('author');

    $list->setColumnLabel('status', $this->i18n('status'));
    $list->setColumnParams('status', ['func' => 'setstatus', 'oldstatus' => '###status###', 'id' => '###id###']);
    $list->setColumnLayout('status', ['<th class="rex-table-action">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnFormat('status', 'custom', function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        if (1 == $list->getValue('status')) {
            $str = $list->getColumnLink('status', '<span class="rex-online"><i class="rex-icon rex-icon-active-true"></i> ' . $this->i18n('item_status_online') . '</span>');
        } else {
            $str = $list->getColumnLink('status', '<span class="rex-offline"><i class="rex-icon rex-icon-active-false"></i> ' . $this->i18n('item_status_offline') . '</span>');
        }
        return $str;
    });

    $list->addColumn($this->i18n('function'), $this->i18n('edit'));
    $list->setColumnLayout($this->i18n('function'), ['<th class="rex-table-action">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnParams($this->i18n('function'), ['func' => 'edit', 'id' => '###id###']);

    $content = $list->get();

    $fragment = new rex_fragment();
    $fragment->setVar('title', $this->i18n('items'));
    $fragment->setVar('content', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $searchPanel;
    echo $content;
} else {
    $title = 'edit' == $func ? $this->i18n('item_edit') : $this->i18n('item_add');

    $form = rex_form::factory(Item::table(), '', 'id = ' . $id, 'post', false);
    $form->addParam('id', $id);
    $form->setApplyUrl(rex_url::currentBackendPage());
    $form->setEditMode('edit' == $func);
    $add = 'edit' != $func;

    $field = $form->addHiddenField('changed_by_user', 1);

    $field = $form->addTextField('uid');
    $field->setLabel($this->i18n('item_uid'));

    if ($media = $form->getSql()->getValue('type')) {
        $field = $form->addTextField('type');
        $field->setLabel($this->i18n('item_type'));
    }

    $field = $form->addTextField('title');
    $field->setLabel($this->i18n('item_title'));

    $field = $form->addTextAreaField('content');
    $field->setLabel($this->i18n('item_content'));

    $field = $form->addTextAreaField('content_raw');
    $field->setLabel($this->i18n('item_content_raw'));

    $field = $form->addTextField('url');
    $field->setLabel($this->i18n('item_url'));

    $field = $form->addReadOnlyField('date');
    $field->setLabel($this->i18n('item_date'));

    $field = $form->addTextField('author');
    $field->setLabel($this->i18n('item_author'));

    $field = $form->addTextField('language');
    $field->setLabel($this->i18n('item_language'));

    $field = $form->addSelectField('status');
    $field->setLabel($this->i18n('status'));
    $select = $field->getSelect();
    $select->setSize(1);
    $select->addOption($this->i18n('item_status_online'), 1);
    $select->addOption($this->i18n('item_status_offline'), 0);

    if ($media = $form->getSql()->getValue('mediasource')) {
        $field = $form->addTextField('mediasource');
        $field->setLabel($this->i18n('item_mediasource'));
    }

    if ($form->isEditMode()) {
        $item = Item::get($id);
        if ($item && $item->getMediaFilename()) {
            $form->addRawField('<div class="text-center"><img class="img-responsive" src="' . $item->getMediaManagerUrl('feeds_thumb') . '"></div>');
        }
    }

    $content = $form->get();

    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit');
    $fragment->setVar('title', $title);
    $fragment->setVar('body', $content, false);
    $content = $fragment->parse('core/page/section.php');

    echo $content;
}
