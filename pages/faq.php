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

$content = '
    <h3>Wann brauche ich ein Access Token für Facebook?</h3>
    <p>
        Bei Facebook Pages wird kein Access Token benötigt, falls nur die öffentlich einsehbaren Einträge eingelesen werden sollen. Für Einräge mit eingeschränkter Sichtbarkeit wird ein User Access Token oder Page Access Token benötigt.<br>
        Für Facebook User Feeds wird ein User Access Token mit der Berechtigung <code>user_posts</code> benötigt.
    </p>
    <h3>Wie erzeuge ich ein Access Token für Facebook?</h3>
    <ul>
       <li>Auf <a href="https://developers.facebook.com">developers.facebook.com</a> einloggen</li> 
       <li>Den <a href="https://developers.facebook.com/tools/explorer/">Graph API Explorer</a> aufrufen</li> 
       <li>Dort rechts oben die App auswählen, die für YFeed verwendet wird</li>
       <li>In der Auswahlbox darunter entweder "Get User Access Token" oder "Get Page Access Token" auswählen, und den Anweisungen folgen. Beim User Access Token bei den Permisssions <code>user_posts</code> auswählen.</li>
       <li>Im Feld "Zugriffsschlüssel" befindet sich das Access Token</li>
    </ul>
    <h3>Wie werden die Streams aktualisiert?</h3>
    <ul>
        <li>Entweder manuell über "Stream abrufen" oder </li>
        <li>automatisch über den Cronjob "YFeed: Feeds abrufen" im Cronjob-Addon.</li>
    </ul>
    ';

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('faq'));
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
