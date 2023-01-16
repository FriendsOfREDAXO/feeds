# Feeds

REDAXO Feed Aggregator

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/feeds/assets/screen.png)

## Features

* Abruf von YouTube-, Vimeo- und RSS-Streams.
* Dauerhaftes Speichern der Beiträge und des Hauptmediums in einer Datenbank-Tabelle
* Nachträgliche Aktualisierung der Beiträge (z.B. nach einem Update / einer Korrektur)
* Erweiterbar durch eigene Feed-Provider
* Feeds können in Watson gesucht werden `feed suchbegriff`
* Abruf aller oder einzelner Feeds per Cronjob

## Installation

1. Im REDAXO-Backend unter `Installer` abrufen und 
2. anschließend unter `Hauptmenü` > `AddOns` installieren.

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
<?php 
$stream_id = 1;
// Mediamanager Typ mit feeds als erster Effekt
$media_manager_type = 'feeds_thumb';
$stream = rex_feeds_stream::get($stream_id);
$items = $stream->getPreloadedItems(); // Standard gibt 5 Einträge zurück, sonst gewünschte Anzahl übergeben
    foreach($items as $item) {
        // Titel ermitteln und alles verlinken
        print '<a href="'. $item->getUrl() .'" title="'. rex_escape($stream->getTitle()) .'">';
        // Bild ausgeben
        print '<img src="'.rex_media_manager::getUrl($media_manager_type,$item->getId() .'.feeds').'"  alt="'. rex_escape($item->getTitle()) .'" title="'. rex_escape($item->getTitle()) .'">';
       print '<p>'.rex_escape($item->getContent()).'</p>';
       print '</a>';
    }
?>
```

## Bilder ausgeben mit dem Mediamanager

Die Bilder eines Feeds werden in der Datenbank gespeichert und müssen mit dem Mediamanager-Effekt `feeds` ausgelesen werden. Der Effekt muss an den Anfang der Verarbeitung. 
- Der Dateiname ist die Datensatz-ID. 
- Damit der Effekt mitbekommt dass ein Feed-Medium verarbeitet werden soll wird dem Mediamanager die Datei-Endung `.feeds` übergeben. 
- Im Anschluss können diese wie gewöhnliche Medien im Mediamanager verarbeitet werden und somit auch alle anderen Effekte angewendet werden. 

Beispielcode: 

```php
$bildurl = rex_media_manager::getUrl($media_manager_type,$item->getId() .'.feeds';
```

## Einträge entfernen

Über das Cronjob-Addon lässt sich ein PHP-Cronjob ausführen, um nicht mehr benötigte Einträge aus der Datenbank zu entfernen. Dazu diese Codezeile ausführen und ggf. die Werte für `stream_id` und `INTERVAL` anpassen.

```php
<?php rex_sql::factory()->setQuery("DELETE FROM rex_feeds_item WHERE stream_id = 4 AND createdate < (NOW() - INTERVAL 2 MONTH)"); ?>
```

## Eigenen Provider anmelden

Feeds kann Inhalte auch anderer Quellen als die der mitglieferten Provider annehmen.

Hierzu erstellt man eine extended Class der `rex_feeds_stream_abstract` im lib Ordner des eigenen AddOns oder des project-AddOns an,  z.B.: `rex_feeds_stream_my_class`. Man kann sich dabei an die mitgelieferten Classes im Ordner `/lib/streams` halten. Alle möglichen Methoden findet man in der `rex_feeds_stream_abstract` -Class unter `/lib/stream_abstract.php`. Dort ruft man die Streamdaten ab und ordnet diese den Tabellenspalten zu. 

Anschließend meldet man den neuen Provider wie folgt in der boot.php an: 

```php 
rex_feeds_stream::addStream("rex_feeds_stream_meine_klasse");
```


## Extension Points nutzen

Feeds kommt mit 2 Extension Points, namentlich `FEEDS_STREAM_FETCHED` nach Abruf eines Streams sowie `FEEDS_ITEM_SAVED` nach dem Speichern eines neuen Eintrags.

So lassen sich nach Abruf eines oder mehrerer Streams bestimmte Aktionen ausführen.

Weitere Infos zu Extension Points in REDAXO unter https://www.redaxo.org/doku/master/extension-points

[Zum GitHub-Repository von Feeds](github.com/FriendsOfREDAXO/feeds/)


## RSS Feed

Gebe einfach die URL zum Feed ein. ;-) 

> Tipp: Mastodon-Feed auslesen: https://phpc.social/@REDAXO.rss 


## Vimeo Pro

Zum Auslesen des Streams werden User-ID, Access Token und ein Client Secret benötigt. 

Alle Infos dazu unter: https://developer.vimeo.com/api/guides/start


## Feeds und YForm

Die Stream-Tabelle lässt sich im YForm-Tablemanager importieren. Dadurch ist es möglich eine eigene Oberfläche für die Redakteure bereitzustellen. 

## Lizenz

AddOn, siehe [LICENSE](https://github.com/FriendsOfREDAXO/feeds/blob/master/LICENCE.md)

Vendoren, siehe Vendors-Ordner des AddOns

## Autoren

* [Friends Of REDAXO](https://github.com/FriendsOfREDAXO) 
* [Contributors](https://github.com/FriendsOfREDAXO/feeds/graphs/contributors)
