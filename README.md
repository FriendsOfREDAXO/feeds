# Feeds

REDAXO 5.x-AddOn zum Abruf externer Streams.

## Features

* Abruf von YouTube-, Vimeo- und RSS-Streams.
* Dauerhaftes Speichern der Beiträge in einer Datenbank-Tabelle
* Nachträgliche Aktualisierung der Beiträge (z.B. nach einem Update / einer Korrektur)
* Erweiterung um eigene Feed-Typen möglich
* Feeds können in Watson gesucht werden `feed suchbegriff`

## Installation

1. Im REDAXO-Backend unter `Installer` abrufen und 
2. anschließend unter `Hauptmenü` > `AddOns` installieren.

## Lizenz

AddOn, siehe [LICENSE](https://github.com/FriendsOfREDAXO/feeds/blob/master/LICENCE.md)

Vendoren, siehe Vendors-Ordner des AddOns

## Autoren

* [Friends Of REDAXO](https://github.com/FriendsOfREDAXO) 
* [Contributors](https://github.com/FriendsOfREDAXO/feeds/graphs/contributors)

## Verwendung

### Einen neuen Feed einrichten

1. Im REDAXO-Backend `AddOns` > `Feeds` aufrufen,
2. dort auf das `+`-Symbol klicken,
3. den Anweisungen der Stream-Einstellungen folgen und
4. anschließend speichern.

> **Hinweis:** Ggf. müssen zusätzlich in den Einstellungen von Feeds Zugangsdaten (bspw. API-Schlüssel) hinterlegt werden, bspw. bei Vimeo und YouTube.

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
        print '<a href="'. $item->getUrl() .'" title="'. rex_escape($stream->getTitle()) .'">';
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

## Eigene Feeds einbinden

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



## RSS Feed

Gebe einfach die URL zum Feed ein. ;-) 


## Vimeo Pro

Zum Auslesen des Streams werden User-ID, Access Token und ein Client Secret benötigt. 

Alle Infos dazu unter: https://developer.vimeo.com/api/authentication


## Feeds und YForm

Die Stream-Tabelle lässt sich im YForm-Tablemanager importieren. Dadurch ist es möglich eine eigene Oberfläche für die Redakteure bereitzustellen. 

