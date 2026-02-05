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

if ('changestatus' == $func) {
    $new_status = rex_request('status', 'int', 0);
    if (in_array($new_status, [0, 1, 2])) {
        rex_sql::factory()
            ->setTable(Item::table())
            ->setWhere('id = :id', ['id' => $id])
            ->setValue('status', $new_status)
            ->addGlobalUpdateFields()
            ->update();
        echo rex_view::success($this->i18n('item_status_saved'));
    }
    $func = '';
}

if ('' == $func) {
    // Suchparameter und Filter
    $search = rex_request('search', 'string', '');
    $namespace_filter = rex_request('namespace_filter', 'string', '');
    $status_filter = rex_request('status_filter', 'string', '');

    // Hole verfügbare Namespaces für den Filter
    $sql = rex_sql::factory();
    $namespaces = $sql->getArray('SELECT DISTINCT namespace FROM ' . Stream::table() . ' ORDER BY namespace');

    // Base Query
    $query = "SELECT
                i.id,
                s.namespace,
                i.date,
                i.media_filename,
                i.mediasource,
                i.content,
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
    if ($status_filter !== '') {
        $where[] = "i.status = " . (int) $status_filter;
    } else {
        // Standardmäßig archivierte Einträge ausblenden
        $where[] = "i.status != 2";
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
    if ($status_filter !== '') {
        $list->addParam('status_filter', $status_filter);
    }

    // Suchformular mit Filter erstellen
    $searchForm = '
    <form action="' . rex_url::currentBackendPage() . '" method="get">
        <input type="hidden" name="page" value="feeds/items" />
        <div class="row">
            <div class="col-sm-3">
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
            <div class="col-sm-3">
                <div class="form-group">
                    <select class="form-control selectpicker" name="status_filter" onchange="this.form.submit()">
                        <option value="">' . rex_i18n::msg('feeds_all_status') . '</option>
                        <option value="1"' . ($status_filter === '1' ? ' selected' : '') . '>' . rex_i18n::msg('item_status_online') . '</option>
                        <option value="0"' . ($status_filter === '0' ? ' selected' : '') . '>' . rex_i18n::msg('item_status_offline') . '</option>
                        <option value="2"' . ($status_filter === '2' ? ' selected' : '') . '>' . rex_i18n::msg('item_status_archived') . '</option>
                    </select>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="input-group">
                    <input class="form-control" type="text" name="search" value="' . htmlspecialchars($search) . '" placeholder="' . rex_i18n::msg('feeds_search_term') . '" />
                    <span class="input-group-btn">
                        <button class="btn btn-primary" type="submit"><i class="rex-icon fa-search"></i> ' . rex_i18n::msg('feeds_search') . '</button>
                        ' . (($search || $namespace_filter || $status_filter !== '') ? '<a class="btn btn-default" href="' . rex_url::currentBackendPage() . '"><i class="rex-icon fa-times"></i> ' . rex_i18n::msg('feeds_clear') . '</a>' : '') . '
                    </span>
                </div>
            </div>
            ' . (($search || $namespace_filter || $status_filter !== '') ? '
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
    $list->removeColumn('mediasource');
    $list->removeColumn('type');
    $list->removeColumn('content');

    $list->setColumnLabel('date', $this->i18n('item_date'));
    $list->setColumnLayout('date', ['<th style="width: 140px;">###VALUE###</th>', '<td style="width: 140px;">###VALUE###</td>']);
    $list->setColumnFormat('date', 'custom', static function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        $date = rex_formatter::strftime($list->getValue('date'), 'date');
        $time = rex_formatter::strftime($list->getValue('date'), 'time');
        return '<span' . (($list->getValue('status')) ? '' : ' class="text-muted"') . '>' . $date . '<br><small>' . $time . '</small></span>';
    });
    $list->setColumnSortable('date');

    $list->setColumnLabel('namespace', $this->i18n('stream_namespace'));
    $list->setColumnLayout('namespace', ['<th style="width: 180px;">###VALUE###</th>', '<td style="width: 180px;">###VALUE###</td>']);
    $list->setColumnFormat('namespace', 'custom', static function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        $namespace = $list->getValue('namespace');
        $type = $list->getValue('stream_type');
        $out = '<strong>' . htmlspecialchars($namespace) . '</strong><br /><small class="text-muted">' . htmlspecialchars($type) . '</small>';
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
        
        // Content für Tooltip direkt aus Query
        $content = $list->getValue('content');
        $content_preview = '';
        if ($content) {
            $content_preview = strip_tags($content);
            $content_preview = rex_formatter::truncate($content_preview, ['length' => 200, 'etc' => '…']);
        }
        
        $title_text = rex_formatter::truncate($title, ['length' => 80, 'etc' => '…']);
        $title_display = $content_preview ? '<span class="feeds-preview" data-toggle="tooltip" data-placement="top" data-html="true" title="' . htmlspecialchars($content_preview) . '">' . $title_text . '</span>' : $title_text;
        
        $url = $list->getValue('url');
        if ($url) {
            $title_display .= '<br><small class="text-muted"><a href="' . htmlspecialchars($url) . '" target="_blank" rel="noopener"><i class="rex-icon fa-external-link"></i> ' . rex_formatter::truncate($url, ['length' => 50, 'etc' => '…']) . '</a></small>';
        }
        $title_display = '<span' . (($list->getValue('status')) ? '' : ' class="text-muted"') . '>' . $title_display . '</span>';
        return $title_display;
    });

    $list->setColumnLabel('media_filename', $this->i18n('item_media'));
    $list->setColumnLayout('media_filename', ['<th style="width: 80px;">###VALUE###</th>', '<td style="width: 80px;">###VALUE###</td>']);
    $list->setColumnFormat('media_filename', 'custom', static function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        $media_filename = $list->getValue('media_filename');
        $media_source = $list->getValue('mediasource');
        $item_id = $list->getValue('id');

        if ($media_filename && $media_source) {
            $thumb_url = rex_media_manager::getUrl('feeds_thumb', $item_id . '.feeds');
            $full_url = htmlspecialchars($media_source);
            
            return '<a href="' . $full_url . '" data-lightbox="feeds-' . $item_id . '" data-title="">' 
                . '<img class="thumbnail" src="' . $thumb_url . '" width="60" height="60" alt="" loading="lazy" style="object-fit: cover; cursor: pointer; border-radius: 3px;">' 
                . '</a>';
        }
        return '<span class="text-muted"><i class="rex-icon fa-picture-o"></i></span>';
    });

    $list->setColumnLabel('author', $this->i18n('item_author'));
    $list->setColumnLayout('author', ['<th style="width: 150px;">###VALUE###</th>', '<td style="width: 150px;">###VALUE###</td>']);
    $list->setColumnFormat('author', 'custom', static function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        $author = $list->getValue('author');
        return $author ? '<span' . (($list->getValue('status')) ? '' : ' class="text-muted"') . '>' . htmlspecialchars($author) . '</span>' : '';
    });
    $list->setColumnSortable('author');

    $list->setColumnLabel('status', $this->i18n('status'));
    $list->setColumnLayout('status', ['<th class="rex-table-action" style="width: 150px;">###VALUE###</th>', '<td class="rex-table-action" style="width: 150px;">###VALUE###</td>']);
    $list->setColumnFormat('status', 'custom', function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        $status = (int) $list->getValue('status');
        $item_id = $list->getValue('id');
        
        $select = '<select class="form-control selectpicker" data-width="fit" onchange="window.location.href=\'' . rex_url::currentBackendPage(['func' => 'changestatus', 'id' => $item_id]) . '&status=\' + this.value">';
        
        // Online - grün mit Haken
        $select .= '<option value="1"' . (1 === $status ? ' selected' : '') . ' data-content="<span class=\'rex-online\'><i class=\'rex-icon rex-icon-active-true\'></i> ' . $this->i18n('item_status_online') . '</span>">' . $this->i18n('item_status_online') . '</option>';
        
        // Offline - rot mit Kreuz
        $select .= '<option value="0"' . (0 === $status ? ' selected' : '') . ' data-content="<span class=\'rex-offline\'><i class=\'rex-icon rex-icon-active-false\'></i> ' . $this->i18n('item_status_offline') . '</span>">' . $this->i18n('item_status_offline') . '</option>';
        
        // Archiviert - grau mit Archiv-Icon
        $select .= '<option value="2"' . (2 === $status ? ' selected' : '') . ' data-content="<span class=\'text-muted\'><i class=\'rex-icon fa-archive\'></i> ' . $this->i18n('item_status_archived') . '</span>">' . $this->i18n('item_status_archived') . '</option>';
        
        $select .= '</select>';
        
        return $select;
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
    
    // Nonce für CSP
    $nonce = rex_response::getNonce();
    
    // Bootstrap Tooltips aktivieren und Lightbox CSS/JS
    echo '
    <style nonce="' . $nonce . '">
    /* Lightbox Overlay */
    .feeds-lightbox-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.9);
        z-index: 10000;
        cursor: pointer;
    }
    .feeds-lightbox-content {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        max-width: 90%;
        max-height: 90%;
    }
    .feeds-lightbox-content img {
        max-width: 100%;
        max-height: 90vh;
        box-shadow: 0 0 25px rgba(0,0,0,0.5);
    }
    .feeds-lightbox-close {
        position: absolute;
        top: 20px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        cursor: pointer;
    }
    /* Preview Tooltip */
    .feeds-preview {
        cursor: help;
        border-bottom: 1px dotted #999;
    }
    </style>
    <div id="feeds-lightbox" class="feeds-lightbox-overlay">
        <span class="feeds-lightbox-close">&times;</span>
        <div class="feeds-lightbox-content">
            <img id="feeds-lightbox-img" src="" alt="">
        </div>
    </div>
    <script nonce="' . $nonce . '">
    jQuery(function($) {
        // Bootstrap Tooltips initialisieren
        $("[data-toggle=\"tooltip\"]").tooltip();
        
        // Lightbox für Feed-Bilder
        $(document).on("click", "a[data-lightbox^=feeds-]", function(e) {
            e.preventDefault();
            var imgUrl = $(this).attr("href");
            $("#feeds-lightbox-img").attr("src", imgUrl);
            $("#feeds-lightbox").fadeIn(200);
        });
        
        // Lightbox schließen
        $("#feeds-lightbox, .feeds-lightbox-close").on("click", function() {
            $("#feeds-lightbox").fadeOut(200);
        });
        
        // Escape-Taste zum Schließen
        $(document).on("keyup", function(e) {
            if (e.keyCode === 27) {
                $("#feeds-lightbox").fadeOut(200);
            }
        });
    });
    </script>
    ';
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
