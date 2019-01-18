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
    <h3>Wie erzeuge ich ein langlebigen Access Token für Facebook?</h3>
    <p><a href="https://stackoverflow.com/questions/12168452/long-lasting-fb-access-token-for-server-to-pull-fb-page-info/21927690#21927690">Basierend auf diesem Eintrag von Stackoverflow: Long-lasting FB access-token for server to pull FB page info</a></p>
    <ol>
    <li>Auf <a href="https://developers.facebook.com">developers.facebook.com</a> einloggen und Facebook-App erzeugen, den Anweisungen folgen.</li> 
    <li>Wichtig: Die App soll sich nicht im Live-Modus, sondern noch im Entwickler-Modus befinden, sonst schlagen die nachfolgenden Aktionen fehl.</li> 
       <li>Den <a href="https://developers.facebook.com/tools/explorer/">Graph API Explorer</a> aufrufen. Dort oben rechts jene App auswählen, die für YFeed verwendet werden soll.</li>
       <li>Das Auswahlfeld "Zugangsschlüssel anfordern" anklicken und darin entweder <br />
       A) "Seitenzugriffs-Schlüssel anfordern" wählen und den Anweisungen folgen oder <br />
       B) Bei bereits bestehender Zugriffsberechtigung die gewünschte Facebook-Page auswählen.</li>
       <li>Im Feld "Zugriffsschlüssel" befindet sich ein kurzlebiger Zugangsschlüssel (hier: <code>access_token1</code>), der nach ca. 1 Stunde ungültig wird. Diesen in folgenden Link einsetzen, zusammen mit den App-Zugangsdaten:<br />
       <code>https://graph.facebook.com/oauth/access_token?client_id=[[[App ID]]]&client_secret=[[[App secret]]]&grant_type=fb_exchange_token&fb_exchange_token=[[[access_token1]]]</code> und aufrufen.<br />
       Der durch den Link erzeugten neuen langlebigen Zugriffsschlüssel (hier: <code>access_token2</code>) ist ca. 2 Monate gültig.</li>
       <li>Diesen Code erneut kopieren und in diesen Link einfügen: <br />
        <code>https://graph.facebook.com/me/accounts?access_token=[[[access_token2]]]</code></li>
        <li>Gibt es hier keine Fehlermeldung, kann der nun generierte, neue "unsterbliche" Zugangsschlüssel kann dann in den Einstellungen für den Facebook-Stream eingesetzt werden. Er wird nur noch dann ungültig, wenn bspw. das Passwort des Facebook-Accounts geändert wird, Admin-Rechte für die Facebook-Page entzogen werden oder die App gelöscht wird.</li>
    </ol>
    <p>Tipp: Jeder Token kann <a href="https://developers.facebook.com/tools/debug/accesstoken/">auf der Facebook Developer Debug-Seite</a> überprüft werden, welche Gültigkeitsdauer vorhanden ist.</p>
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
