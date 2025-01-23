# Feeds

REDAXO Feed Aggregator

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/feeds/assets/screen.png)

## Features

* Abruf von YouTube-, Vimeo- und RSS-Streams.
* Dauerhaftes Speichern der Beiträge
* Speicherung des Hauptmediums Im data-Ordner des AddOns
* Nachträgliche Aktualisierung der Beiträge (z.B. nach einem Update / einer Korrektur)
* Erweiterbar durch eigene Feed-Provider
* Feeds können in Watson gesucht werden `feed suchbegriff`
* Abruf aller oder einzelner Feeds per Cronjob

## Installation

Im REDAXO-Backend unter `Installer` abrufen und installieren

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
        if($item->getMediaFilename()) {
        print '<img src="'.rex_media_manager::getUrl($media_manager_type,$item->getId() .'.feeds').'"  alt="'. rex_escape($item->getTitle()) .'" title="'. rex_escape($item->getTitle()) .'">';
        }
       print '<p>'.rex_escape($item->getContent()).'</p>';
       print '</a>';
    }
?>
```

## Bilder ausgeben mit dem Media Manager

Die Bilder eines Feeds werden im AddOn-Data-Ordner unter `data/addons/feeds/media` gespeichert. Für die Ausgabe der Bilder stehen zwei gleichwertige Möglichkeiten zur Verfügung.

### 1. Methode des Feed-Items

Neu seit 5.0.0

```php
// Mit Media Manager Effekt
$media_url = $item->getMediaManagerUrl('feeds_thumb');
echo '<img src="'.$media_url.'" alt="Mein Bild">';
```

Ermitteln der Medie-Infos: 

```php
$mediaInfo = $item->getMediaInfo('mein_media_type'); 
if ($mediaInfo) {
    echo "Breite: " . $mediaInfo['width'];
    echo "Höhe: " . $mediaInfo['height'];
    echo "Format: " . $mediaInfo['format'];
}
```


### 2. Direkt über de Mediamanager

Dies ist die traditionelle Methode zur Ausgabe des Mediums.
Wichtig hierbei die Endung `.feeds`. 

```php
// $item ist ein rex_feeds_item Objekt
$media_url = rex_media_manager::getUrl('feeds_thumb', $item->getId() .'.feeds');
echo '<img src="'.$media_url.'" alt="Mein Bild">';
```


## Komplettes Beispiel

```php
<?php 
$stream_id = 1;
// Media Manager Typ wo der Feeds-Effekt als erster Effekt eingerichtet ist
$media_manager_type = 'feeds_thumb';

$stream = rex_feeds_stream::get($stream_id);
$items = $stream->getPreloadedItems(); // Standard gibt 5 Einträge zurück

foreach($items as $item) {
    // Titel ermitteln und verlinken
    echo '<a href="'. $item->getUrl() .'" title="'. rex_escape($stream->getTitle()) .'">';
    
    // Variante 1: Klassische Methode
    echo '<img src="'.rex_media_manager::getUrl($media_manager_type, $item->getId() .'.feeds').'" 
              alt="'. rex_escape($item->getTitle()) .'">';
              
    // ODER Variante 2: Neue Methode
    echo '<img src="'.$item->getMediaManagerUrl($media_manager_type).'" 
              alt="'. rex_escape($item->getTitle()) .'">';
    
    echo '<p>'.rex_escape($item->getContent()).'</p>';
    echo '</a>';
}
?>
```

## Media Manager Effekt einrichten

1. Im REDAXO-Backend unter Media Manager einen neuen Typ anlegen
2. Als ersten Effekt "Datei: Aus Feeds einlesen" auswählen
3. Weitere gewünschte Effekte wie Resize, Crop etc. hinzufügen

> **Wichtig:** Der Feed-Effekt muss immer als erster Effekt in der Effektkette eingerichtet sein.

> **Hinweis:** Beide Methoden der Bildausgabe sind in ihrer Funktionalität identisch. Die klassische Methode wird aus Gründen der Abwärtskompatibilität weiterhin unterstützt.


## Einträge entfernen

Über das Cronjob-Addon lässt sich ein PHP-Cronjob ausführen, um nicht mehr benötigte Einträge aus der Datenbank zu entfernen. Dazu diese Codezeile ausführen und ggf. die Werte für `stream_id` und `INTERVAL` anpassen.

```php
<?php rex_sql::factory()->setQuery("DELETE FROM rex_feeds_item WHERE stream_id = 4 AND createdate < (NOW() - INTERVAL 2 MONTH)"); ?>
```

Alternativ: 

`<?php rex_sql::factory()->setQuery("DELETE t1 FROM rex_feeds_item t1 JOIN (SELECT id FROM rex_feeds_item WHERE stream_id = 1 ORDER BY id DESC LIMIT 50,500) t2 ON t1.id = t2.id"); ?>`

Dies löscht nicht nach Datum, sondern nach Anzahl.
Vorteil: Wenn viele Posts immer geladen werden, kann sich die die DB sehr schnell aufblähen und Probleme beim Backup machen.



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

## Autor

[Friends Of REDAXO](https://github.com/FriendsOfREDAXO)

## Credits
[Contributors](https://github.com/FriendsOfREDAXO/feeds/graphs/contributors)
