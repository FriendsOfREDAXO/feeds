# Feeds

Ein REDAXO5-AddOn zum Abruf externer Streams, vormals YFeed.

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/feeds/assets/screenshot.png)

## Features

* Abruf von Facebook-, Twitter-, YouTube-, Vimeo- und RSS-Streams.
* Dauerhaftes Speichern der Beiträge in einer Datenbank-Tabelle
* Nachträgliche Aktualisierung der Beiträge (z.B. nach einem Update / einer Korrektur)
* Erweiterung um eigene Feed-Typen möglich, z.B. Google My Business o.a.
* Feeds können in Watson gesucht werden `feed suchbegriff`

## Installation

1. Im REDAXO-Backend unter `Installer` abrufen und 
2. anschließend unter `Hauptmenü` > `AddOns` installieren.

## YFeed-Migration

- Es sollte YFeed 1.3.0 installiert, sein damit eine Migration erfolgen kann. YFeed ggf. daher vorab aktualisieren. 
- Zum Update zunächst Feeds 2.2.1 migrieren, anschließend lässt sich Feeds updaten.
- Feeds importiert die Tabellen und Konfiguration von YFeed während der Installation. 
- Die neu angelegten Tabellen lauten jetzt: TABLEPREFIX_`feeds_item` und TABLEPREFIX_`feeds_stream`, der Abruf in Modulen, AddOns oder Classes muss daher angepasst werden. 
- Der Aufruf der Bilder mit der Endung `.yfeed` wird weiterhin unterstützt, in Zukunft jedoch `.feeds` verwenden.
- Anschließend lässt sich Feeds auf die aktuelle Version updaten.

## Lizenz

AddOn, siehe [LICENSE](https://github.com/FriendsOfREDAXO/feeds/blob/master/LICENCE.md)

Vendoren, siehe Vendors-Ordner des AddOns

## Autoren

* [Friends Of REDAXO](https://github.com/FriendsOfREDAXO) 
* [Contributors](https://github.com/FriendsOfREDAXO/feeds/graphs/contributors)

**Projekt-Lead**

[Alexander Walther](https://github.com/alexplusde)


## Verwendung

### Einen neuen Feed einrichten

1. Im REDAXO-Backend `AddOns` > `Feeds` aufrufen,
2. dort auf das `+`-Symbol klicken,
3. den Anweisungen der Stream-Einstellungen folgen und
4. anschließend speichern.

> **Hinweis:** Ggf. müssen zusätzlich in den Einstellungen von Feeds Zugangsdaten (bspw. API-Schlüssel) hinterlegt werden, bspw. bei Facebook, Twitter oder YouTube.

### Feed aktualisieren

Die Feeds können manuell unter `AddOns` > `Feeds` abgerufen werden, oder in regelmäßigen Intervallen über einen Cronjob abgerufen werden:

1. Im REDAXO-Backend unter `AddOns` > `Cronjob` aufrufen,
2. dort auf das `+`-Symbol klicken,
3. als Umgebung z.B. `Frontend` auswählen,
4. als Typ `Feeds: Feeds abrufen` auswählen,
5. den Zeitpunkt festlegen (bspw. täglich, stündlich, ...) und
6. mit `Speichern` bestätigen.

Jetzt werden Feeds-Streams regelmäßig dann abgerufen, wenn die Website aufgerufen wird. [Weitere Infos zu REDAXO-Cronjobs](https://www.redaxo.org/doku/master/cronjobs).

### Feed ausgeben

Um ein Feed auszugeben, können die Inhalte in einem Modul oder Template per SQL oder mit nachfolgender Methode abgerufen werden, z.B.:

```php
$stream_id = 1;
$media_manager_type = 'my_mediatype';
$stream = rex_feeds_stream::get($stream_id);
$items = $stream->getPreloadedItems(); // Standard gibt 5 Einträge zurück, sonst gewünschte Anzahl übergeben
    foreach($items as $item) {
        print '<a href="'. $item->getUrl() .'" title="'. $stream->getTitle() .'">';
        print '<img src="index.php?rex_media_type='. $media_manager_type .'&rex_media_file='. $item->getId() .'.feeds"  alt="'. rex_escape($item->getTitle()) .'" title="'. rex_escape($item->getTitle()) .'">'; 
        print '</a>';
    }
```

### Bilder ausgeben

Damit Bilder in der Form `/index.php?rex_media_type=<medientyp>&rex_media_file=<id>.feeds` bzw. `/media/<medientyp>/<id>.feeds`
ausgegeben werden können, muss das Bild über den Media-Manager-Effekt von Feeds eingelesen werden. Diesen sollte man direkt am Anfang vor allen anderen Effekten setzen. Als Medientyp das Media-Manager-Profil angeben und als `id` die ID des Eintrags.

## Einträge entfernen

Über das Cronjob-Addon lässt sich ein PHP-Cronjob ausführen, um nicht mehr benötigte Einträge aus der Datenbank zu entfernen. Dazu diese Codezeile ausführen und ggf. die Werte für `stream_id` und `INTERVAL` anpassen.

```php
<?php rex_sql::factory()->setQuery("DELETE FROM rex_feeds_item WHERE stream_id = 4 AND createdate < (NOW() - INTERVAL 2 MONTH)"); ?>
```

## Feeds erweitern

Um Feeds zu erweitern, kann man sich die Logik der von Haus aus mitgelieferten Extension Points und Feeds ansehen:

### Eigenen Stream hinzufügen

Am Beispiel "Twitter" wird ein neuer Stream erstellt:

* In `/redaxo/src/addons/feeds/pages/settings.twitter.php` wird die Einstellungsseite für das Hinterlegen von API-Keys u.a. Zugangsdaten für Twitter hinterlegt.

* In `/redaxo/src/addons/feeds/lib/stream/twitter_user_timeline.php` wird die Logik für den Import der Tweets eines Users hinterlegt.

Diese lassen sich kopieren und bspw. im `project`-Addon anpassen. In der `boot.php` des Projekt-Addons hinzufügen: `rex_feeds_stream::addStream("rex_Feeds_stream_meine_klasse";`. Zum Einhängen der Einstellungsseite in Feeds muss dann in der `package.yml` die Einstellungsseite registriert werden.

> Tipp: Du hast einen neuen Stream für Feeds? Teile ihn mit der REDAXO-Community! [Zum GitHub-Repository von Feeds](github.com/FriendsOfREDAXO/feeds/)

### Extension Points nutzen

Feeds kommt mit 2 Extension Points, namentlich `FEEDS_STREAM_FETCHED` nach Abruf eines Streams sowie `FEEDS_ITEM_SAVED` nach dem Speichern eines neuen Eintrags.

So lassen sich nach Abruf eines oder mehrerer Streams bestimmte Aktionen ausführen.

Weitere Infos zu Extension Points in REDAXO unter https://www.redaxo.org/doku/master/extension-points

> Tipp: Du hast Beispiele aus der Praxis für die Extension Points? Teile sie mit der REDAXO-Community! [Zum GitHub-Repository von Feeds](github.com/FriendsOfREDAXO/feeds/)

## Facebook

### Wann brauche ich ein Access Token für Facebook?

Stand 2017: Bei Facebook Pages wird kein Access-Token benötigt, falls nur die öffentlich einsehbaren Einträge eingelesen werden sollen. Für Einräge mit eingeschränkter Sichtbarkeit wird ein User-Access Token oder Page-Access Token benötigt. Für Facebook User-Feeds wird ein User-Access-Token mit der Berechtigung `user_posts` benötigt.

Stand 2019: Ist der Nutzer, der den Access-Token generiert, Administrator der Facebook-Seite, so ist kein zusätzlicher Freigabe-Prozess der Facebook-App erforderlich.

### Wie erzeuge ich ein langlebigen Access-Token für Facebook?

[Basierend auf diesem Eintrag von Stackoverflow: Long-lasting FB access-token for server to pull FB page info](https://stackoverflow.com/questions/12168452/long-lasting-fb-access-token-for-server-to-pull-fb-page-info/21927690#21927690)

1.  Auf [developers.facebook.com](https://developers.facebook.com) einloggen und Facebook-App erzeugen, den Anweisungen folgen.
2.  Wichtig: Die App soll sich nicht im Live-Modus, sondern noch im Entwickler-Modus befinden, sonst schlagen die nachfolgenden Aktionen fehl.
3.  Den [Graph API Explorer](https://developers.facebook.com/tools/explorer/) aufrufen. Dort oben rechts jene App auswählen, die für Feeds verwendet werden soll.
4.  Das Auswahlfeld "Zugangsschlüssel anfordern" anklicken und darin entweder  
    A) "Seitenzugriffs-Schlüssel anfordern" wählen und den Anweisungen folgen oder  
    B) Bei bereits bestehender Zugriffsberechtigung die gewünschte Facebook-Page auswählen.
5.  Im Feld "Zugriffsschlüssel" befindet sich ein kurzlebiger Zugangsschlüssel (hier: `access_token1`), der nach ca. 1 Stunde ungültig wird. Diesen in folgenden Link einsetzen, zusammen mit den App-Zugangsdaten:  
    `https://graph.facebook.com/oauth/access_token?client_id=[[[App ID]]]&client_secret=[[[App secret]]]&grant_type=fb_exchange_token&fb_exchange_token=[[[access_token1]]]` und aufrufen.  
    Der durch den Link erzeugten neuen langlebigen Zugriffsschlüssel (hier: `access_token2`) ist ca. 2 Monate gültig.
6.  Diesen Code erneut kopieren und in diesen Link einfügen:  
    `https://graph.facebook.com/me/accounts?access_token=[[[access_token2]]]`
7.  Gibt es hier keine Fehlermeldung, kann der nun generierte, neue "unsterbliche" Zugangsschlüssel kann dann in den Einstellungen für den Facebook-Stream eingesetzt werden. Er wird nur noch dann ungültig, wenn bspw. das Passwort des Facebook-Accounts geändert wird, Admin-Rechte für die Facebook-Page entzogen werden oder die App gelöscht wird.

### Tipps: 

Jeder Token kann [auf der Facebook Developer Debug-Seite](https://developers.facebook.com/tools/debug/accesstoken/) überprüft werden, welche Gültigkeitsdauer vorhanden ist.

Hier löst man Tokens aus und Berechtigungen setzen 

https://developers.facebook.com/tools/accesstoken

Hier sieht man, ob man einen langlebigen API-Schlüssel hat und kann ihn sich von dort kopieren

https://developers.facebook.com/tools/explorer/?classic=0

## Instagram 

Eigenen Access-Token anfordern, entweder über:

http://www.stephan-romhart.de/artikel/instagram-feed-anleitung-code-access-token-api-einbindung-php

Oder alternativ:  Einen access-token im 'public-content' scope generieren lassen, in dem man einer entsprechenden App Zugriff aufs eigene Profil gestattet: https://instagram.pixelunion.net/

Dann gibt man einfach UserID oder UserName ein und fügt noch unter Einstellungen den Accesstoken ein.


## Twitter

Infos zur Erstellung des Access-Tokens gibt es hier: https://developer.twitter.com/en/docs/basics/authentication/guides/access-tokens


### Tipp

> Die API verlangt normalerweise zwingend eine UserID (Dezimalzahl) beim Typ Instagram-Benuter für Feed-Anfragen via access_token. Ist keine UserID (Dezimalzahl) angegeben wird diese durch API-Anfrage ermittelt und dann erst zur eigentlichen Feed-Anfrage weitergegangen. (Was fehlschlägt, wegen fehlendem ‚public_content‘-scope bzw. SandBox-Mode)
Glorreiche Ausnahme ist ***'self'***, diese Anfrage wird mit "UserName" druchgewunken.
Instagram lässt einem zumindest die Berechtigung, mit entsprechenden access_token, den eigenen Stream (des access-token-Inhabers) auszulesen. 


## RSS Feed

Gebe einfach die URL zum Feed ein. ;-) 


## Vimeo Pro

Zum Auslesen des Streams werden User-ID, Access Token und ein Client Secret benötigt. 

Alle Infos dazu unter: https://developer.vimeo.com/api/authentication


## Feeds und YForm

Die Stream-Tabelle lässt sich im YForm-Tablemanager importieren. Dadurch ist es möglich eine eigene Oberfläche für die Redakteure bereitzustellen. 


