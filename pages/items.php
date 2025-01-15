<?php

/**
 * This file is part of the Feeds package.
 *
 * @author FriendsOfREDAXO
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$func = rex_request('func', 'string');
$id = rex_request('id', 'integer');

if ($func == 'setstatus') {
    $status = (rex_request('oldstatus', 'int') + 1) % 2;
    rex_sql::factory()
        ->setTable(rex_feeds_item::table())
        ->setWhere('id = :id', ['id' => $id])
        ->setValue('status', $status)
        ->addGlobalUpdateFields()
        ->update();
    echo rex_view::success($this->i18n('item_status_saved'));
    $func = '';
}

if ('' == $func) {
    // Suchparameter
    $search = rex_request('search', 'string', '');
    
    $query = 'SELECT
                i.id,
                s.namespace,
                i.date,
                i.media_filename,
                s.type,
                (CASE WHEN (i.title IS NULL or i.title = "")
                    THEN i.content
                    ELSE i.title
                END) as title,
                i.url,
                i.status
            FROM
                ' . rex_feeds_item::table() . ' AS i
                LEFT JOIN
                    ' . rex_feeds_stream::table() . ' AS s
                    ON  i.stream_id = s.id';
                    
    // Suchfilter hinzufügen wenn Suche aktiv
    if ($search) {
        $query .= ' WHERE (i.title LIKE :search 
                    OR i.content LIKE :search 
                    OR s.namespace LIKE :search 
                    OR i.author LIKE :search)';
    }
            
    $query .= ' ORDER BY i.date DESC, id DESC';

    $list = rex_list::factory($query);
    
    // Suchparameter an Liste übergeben wenn Suche aktiv
    if ($search) {
        $list->setParameter('search', $search);
        $list->addParam('search', $search);
        $search_term = '%' . $search . '%';
        $list->setQuery($query, ['search' => $search_term]);
    }

    $panelElements = [];

    // Suchformular erstellen
    $searchForm = new rex_form_container();
    $searchForm->setAttributes(['class' => 'form-inline', 'method' => 'get']);

    $searchField = new rex_form_element('text', 'search');
    $searchField->setAttribute('class', 'form-control');
    $searchField->setAttribute('value', htmlspecialchars($search));
    $searchField->setAttribute('placeholder', rex_i18n::msg('search'));
    $searchField->setAttribute('autofocus', 'autofocus');

    $searchButton = new rex_form_element('button', 'search_button');
    $searchButton->setLabel(rex_i18n::msg('search'));
    $searchButton->setAttribute('class', 'btn btn-search');
    $searchButton->setAttribute('type', 'submit');

    $searchContainer = '<div class="input-group">';
    $searchContainer .= $searchField->get();
    $searchContainer .= '<span class="input-group-btn">';
    $searchContainer .= $searchButton->get();
    if ($search) {
        $searchContainer .= '<a class="btn btn-default" href="' . rex_url::currentBackendPage() . '">' . rex_i18n::msg('clear') . '</a>';
    }
    $searchContainer .= '</span>';
    $searchContainer .= '</div>';

    $panelElements[] = $searchContainer;

    $fragment = new rex_fragment();
    $fragment->setVar('content', [$panelElements], false);
    $fragment->setVar('options', $searchForm, false);
    $panel = $fragment->parse('core/page/section.php');

    $list->addTableAttribute('class', 'table-striped');

    $list->addColumn('', '', 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams('', ['func' => 'edit', 'id' => '###id###']);
    $list->setColumnFormat('', 'custom', function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        $type = explode('_', $list->getValue('s.type'));
        $icon = 'fa-paper-plane-o';
        if (isset($type[0])) {
            switch ($type[0]) {
                case 'rss':
                    $icon = 'fa-rss';
                    break;
                case 'twitter':
                    $icon = 'fa-twitter';
                    break;
                case 'facebook':
                    $icon = 'fa-facebook';
                    break;
                case 'youtube':
                    $icon = 'fa-youtube';
                    break;
                case 'instagram':
                    $icon = 'fa-instagram';
                    break;
                case 'google':
                    $icon = 'fa-google';
                    break;
                case 'vimeo':
                    $icon = 'fa-video-camera';
                    break;
            }
            return $list->getColumnLink('', '<i class="rex-icon ' . $icon . (($list->getValue('status')) ? '' : ' text-muted') . '"></i>');
        }
    });

    $list->removeColumn('id');
    $list->removeColumn('url');
    $list->removeColumn('type');

    $list->setColumnLabel('date', $this->i18n('item_date'));
    $list->setColumnSortable('date');

    $list->setColumnLabel('namespace', $this->i18n('stream_namespace') . '/' . $this->i18n('stream_type'));
    $list->setColumnFormat('namespace', 'custom', function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        $namespace = $list->getValue('namespace');
        $type = $list->getValue('type');
        $out = $namespace . '<br /><small>' . $type . '</small>';
        $out = '<span class="type' . (($list->getValue('status')) ? '' : ' text-muted') . '">' . $out . '</span>';
        return $out;
    });
    $list->setColumnSortable('namespace');

    $list->setColumnLabel('title', $this->i18n('item_title'));
    $list->setColumnFormat('title', 'custom', function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        $title = $list->getValue('title');
        if ($title === null) {
            $title = ''; // Set to empty string if null
        }
        $title = rex_formatter::truncate($title, ['length' => 140]);
        $title .= ($list->getValue('url') != '') ? '<br /><small><a href="' . $list->getValue('url') . '" target="_blank">' . $list->getValue('url') . '</a></small>' : '';
        $title = '<div class="rex-word-break"><span class="title' . (($list->getValue('status')) ? '' : ' text-muted') . '">' . $title . '</span></div>';
        return $title;
    });

    $list->setColumnLabel('media_filename', $this->i18n('item_media'));
    $list->setColumnFormat('media_filename', 'custom', function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        $item = rex_feeds_item::get($list->getValue('id'));
        
        if ($item && $item->getMediaFilename()) {
            $media_url = $item->getMediaManagerUrl('feeds_thumb');
            return '<img class="thumbnail" src="'. $media_url.'" width="60" height="60" alt="" title="" loading="lazy">';
        }
        return '';
    });

    $list->setColumnLabel('status', $this->i18n('status'));
    $list->setColumnParams('status', ['func' => 'setstatus', 'oldstatus' => '###status###', 'id' => '###id###']);
    $list->setColumnLayout('status', ['<th class="rex-table-action">###VALUE###</th>', '<td class="rex-table-action">###VALUE###</td>']);
    $list->setColumnFormat('status', 'custom', function ($params) {
        /** @var rex_list $list */
        $list = $params['list'];
        if ($list->getValue('status') == 1) {
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

    echo $panel;
    echo $content;
} else {
    $title = $func == 'edit' ? $this->i18n('item_edit') : $this->i18n('item_add');

    $form = rex_form::factory(rex_feeds_item::table(), '', 'id = ' . $id, 'post', false);
    $form->addParam('id', $id);
    $form->setApplyUrl(rex_url::currentBackendPage());
    $form->setEditMode($func == 'edit');
    $add = $func != 'edit';

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
        $item = rex_feeds_item::get($id);
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
