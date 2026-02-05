# Feeds

REDAXO Feed Aggregator

![Screenshot](https://raw.githubusercontent.com/FriendsOfREDAXO/feeds/assets/screen.png)

## Features

* Abruf von YouTube-, Vimeo-, RSS-, Mastodon- und Podcast-Streams
* Dauerhaftes Speichern der Beiträge
* Speicherung des Hauptmediums im data-Ordner des AddOns
* Nachträgliche Aktualisierung der Beiträge (z.B. nach einem Update / einer Korrektur)
* Erweiterbar durch eigene Feed-Provider
* Feeds können in Watson gesucht werden `feed suchbegriff`
* Abruf aller oder einzelner Feeds per Cronjob
* Bereinigen von Streams (Löschen aller Einträge) direkt im Backend
* **Filterung:** Positivliste und Negativliste für jeden Stream konfigurierbar (z.B. nur Beiträge mit bestimmten Hashtags importieren)
* **Archivierung:** 3 Status-Zustände (Online/Offline/Archiviert) für Einträge
* **Content-Preview:** Tooltip-Vorschau beim Hover über Titel
* **Duplicate Detection:** Automatische Erkennung doppelter URLs beim Fetch
* **Stream Health-Check:** Testen der Erreichbarkeit von Feed-Quellen
* **Granulare Berechtigungen:** Separate Rechte für Streams und Einträge
* **Konfigurierbare Einstellungen:** HTTP-Timeouts, Media-Größen, Log-Level
* **Medien-Lightbox:** Klickbare Thumbnails mit Vollbild-Ansicht

## Neu in Version 6.2.0

### Status-Verwaltung
- **3 Status-Zustände:** Online (1), Offline (0), Archiviert (2)
- **Status-Filter:** Dropdown-Filter in der Einträge-Liste
- **Quick-Toggle:** Durch Klick auf Status durch alle Zustände wechseln

### Content-Vorschau
- **Tooltip-Preview:** Erste 200 Zeichen des Contents beim Hover über Titel
- **Gepunktete Unterstreichung:** Zeigt verfügbare Vorschau an

### Duplicate Detection
- **Automatische Prüfung:** Erkennt doppelte URLs beim Fetch
- **Warning-Log:** Duplikate werden protokolliert aber nicht importiert
- **Stream-spezifisch:** Prüft nur innerhalb des gleichen Streams

### Stream Health-Check
- **Erreichbarkeit testen:** Button zum Prüfen ob Stream-URL erreichbar ist
- **HTTP-Status anzeigen:** Zeigt Response-Code und Fehlermeldungen
- **Unterstützte Typen:** RSS, Podcast, YouTube (Channel/Playlist), iCal

### Einstellungen
- **HTTP-Timeouts:** Konfigurierbar 5-120 Sekunden
- **Maximale Dauer:** Gesamtdauer inkl. Redirects
- **Media-Größe:** Max. Dateigröße für Downloads (1-500 MB)
- **Log-Level:** Error / Warning / Info

### Berechtigungen
- **feeds[streams]:** Streams erstellen, bearbeiten, löschen, Einstellungen
- **feeds[items]:** Einträge bearbeiten, Status ändern

### Performance & Sicherheit
- **SQL-Injection behoben:** Prepared Statements in allen Queries
- **N+1 Problem gelöst:** Batch-Loading in Listen-Ansicht
- **HTTP-Caching:** Etag/Last-Modified Header für effiziente Updates
- **Composite Index:** Optimierte Queries durch `stream_status_date` Index

## Migration zu Namespaces (REDAXO 6 Vorbereitung)

Das Feeds-AddOn wurde für REDAXO 6 vorbereitet und nutzt jetzt moderne PHP-Namespaces. Die alten `rex_` Klassen sind weiterhin verfügbar, werden aber als deprecated markiert.

### Neue Namespace-Struktur

Alle Klassen wurden in den `FriendsOfRedaxo\Feeds` Namespace migriert:

| Alte Klasse | Neue Klasse |
|-------------|-------------|
| `rex_feeds_stream` | `FriendsOfRedaxo\Feeds\Stream` |
| `rex_feeds_item` | `FriendsOfRedaxo\Feeds\Item` |
| `rex_feeds_stream_abstract` | `FriendsOfRedaxo\Feeds\Stream\AbstractStream` |
| `rex_cronjob_feeds` | `FriendsOfRedaxo\Feeds\Cronjob` |
| `rex_feeds_helper` | `FriendsOfRedaxo\Feeds\Helper` |

Stream-Implementierungen befinden sich im `FriendsOfRedaxo\Feeds\Stream` Namespace:
- `rex_feeds_stream_rss` → `FriendsOfRedaxo\Feeds\Stream\Rss`
- `rex_feeds_stream_youtube_playlist` → `FriendsOfRedaxo\Feeds\Stream\YoutubePlaylist`
- `rex_feeds_stream_youtube_channel` → `FriendsOfRedaxo\Feeds\Stream\YoutubeChannel`
- `rex_feeds_stream_ics` → `FriendsOfRedaxo\Feeds\Stream\Ics`
- `rex_feeds_stream_vimeo_pro` → `FriendsOfRedaxo\Feeds\Stream\VimeoPro`
- `rex_feeds_stream_mastodon` → `FriendsOfRedaxo\Feeds\Stream\Mastodon`
- `rex_feeds_stream_podcast` → `FriendsOfRedaxo\Feeds\Stream\Podcast`

### Sanfte Migration

Die alten Klassennamen funktionieren weiterhin, sind aber als deprecated markiert:

```php
// ✅ Funktioniert weiterhin (deprecated)
$stream = rex_feeds_stream::get($stream_id);

// ✅ Moderne Schreibweise (empfohlen)
$stream = \FriendsOfRedaxo\Feeds\Stream::get($stream_id);
```

**Empfehlung:** Migrieren Sie Ihren Code schrittweise zu den neuen Namespace-Klassen. Die alten Klassen werden in zukünftigen Versionen entfernt.

## Installation

Im REDAXO-Backend unter `Installer` abrufen und installieren

## Verwendung

### Einen neuen Feed einrichten

1. Im REDAXO-Backend `AddOns` > `Feeds` aufrufen,
2. dort auf das `+`-Symbol klicken,
3. den Anweisungen der Stream-Einstellungen folgen und
4. anschließend speichern.

> **Hinweis:** Ggf. müssen zusätzlich in den Einstellungen von Feeds Zugangsdaten (bspw. API-Schlüssel) hinterlegt werden, bspw. bei Vimeo und YouTube.

### Filtern von Beiträgen (Positivliste / Negativliste)

Jeder Stream kann gefiltert werden, um nur bestimmte Beiträge zu importieren oder unerwünschte auszuschließen.

*   **Positivliste:** Kommagetrennte Liste von Begriffen. Wenn gesetzt, wird ein Beitrag nur importiert, wenn er **mindestens einen** dieser Begriffe im Titel oder Inhalt enthält.
*   **Negativliste:** Kommagetrennte Liste von Begriffen. Wenn gesetzt, wird ein Beitrag **ignoriert**, sobald er **einen** dieser Begriffe enthält.

Beispiel:
*   Positivliste: `#news, Wichtig` -> Importiert nur Beiträge, die "#news" ODER "Wichtig" enthalten.
*   Negativliste: `Gewinnspiel, Intern` -> Ignoriert alle Beiträge, die "Gewinnspiel" ODER "Intern" enthalten.

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

**Wichtig:** In den Queries die Status-Werte beachten:
- **Status 1** = Online (für Frontend-Ausgabe)
- **Status 0** = Offline (nicht ausgeben)
- **Status 2** = Archiviert (nicht standardmäßig ausgeben, außer für Archiv-Seiten)

Um ein Feed auszugeben, können die Inhalte in einem Modul oder Template per SQL oder mit nachfolgender Methode abgerufen werden, z.B.:

```php
<?php 
use FriendsOfRedaxo\Feeds\Stream;
use FriendsOfRedaxo\Feeds\Item;

$stream_id = 1;
// Mediamanager Typ mit feeds als erster Effekt
$media_manager_type = 'feeds_thumb';

// Moderne Schreibweise (empfohlen)
$stream = Stream::get($stream_id);
// Alternativ: $stream = rex_feeds_stream::get($stream_id); // Weiterhin möglich, aber deprecated

// Nur Online-Einträge ausgeben (status = 1)
$items = $stream->getPreloadedItems(5, 1); // 5 Einträge mit Status 1 (Online)
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

#### Erweiterte Queries mit Status-Filter

```php
<?php
use FriendsOfRedaxo\Feeds\Item;

// Nur Online-Einträge eines Streams
$sql = rex_sql::factory();
$sql->setQuery('
    SELECT * FROM ' . Item::table() . ' 
    WHERE stream_id = :stream_id 
    AND status = 1 
    ORDER BY date DESC 
    LIMIT 10
', ['stream_id' => $stream_id]);

// Archiv-Seite: Nur archivierte Einträge
$sql->setQuery('
    SELECT * FROM ' . Item::table() . ' 
    WHERE stream_id = :stream_id 
    AND status = 2 
    ORDER BY date DESC
', ['stream_id' => $stream_id]);

// Alle nicht-archivierten Einträge (Online + Offline)
$sql->setQuery('
    SELECT * FROM ' . Item::table() . ' 
    WHERE status != 2 
    ORDER BY date DESC
');

// Items durchlaufen
while ($sql->hasNext()) {
    $item = Item::createFromDbRow($sql->getRow());
    // ... Ausgabe
    $sql->next();
}
?>
```

## Content-Helper (Hilfsmethoden)

Dieses AddOn stellt mehrere praktische Helfer für die Verarbeitung von Content zur Verfügung. Die Methoden befinden sich in der Klasse `FriendsOfRedaxo\Feeds\Item` (Datei: `lib/Item.php`).

- `hasMedia(): bool` — Prüft, ob ein `media_filename` gesetzt ist.
- `getPlainTextContent(): string` — Liefert den Inhalt als reinen Text (HTML entfernt, Whitespace normalisiert).
- `getTruncatedContent(int $length = 200, bool $endOnSentence = true, string $ellipsis = '…'): string` — Kürzt Text intelligent; bevorzugt Satzende, fällt zurück auf Wortgrenze.
- `removeEmojis(string $text): string` — Entfernt gängige Emoji-Zeichen aus Text.
- `removeHashtags(string $text): string` — Entfernt Hashtags (`#tag`) aus Text.
- `sanitizeContent(array $options): string` — Convenience-Methode (Standard: Emojis entfernen, Hashtags optional).
- `extractTitleFromContent(string $stopSign = '::', int $maxLength = 120, bool $fallbackToFirstLine = true): string` — Extrahiert einen Titel aus dem Content; nutzt ein Stop-Zeichen (z.B. `::`) oder als Fallback die erste Zeile.

Anwendungsbeispiele:

```php
// $item ist ein FriendsOfRedaxo\Feeds\Item Objekt
// Prüfen, ob ein Medium vorhanden ist
if ($item->hasMedia()) {
    echo 'Bild vorhanden: ' . $item->getMediaFilename();
}

// Reinen Text bekommen und kürzen
$plain = $item->getPlainTextContent();
$teaser = $item->getTruncatedContent(180);

// Emojis und Hashtags entfernen
$clean = $item->sanitizeContent(['remove_emojis' => true, 'remove_hashtags' => true]);

// Titel anhand eines Stop-Zeichens extrahieren (z.B. "Titel :: Rest des Textes")
$extractedTitle = $item->extractTitleFromContent('::', 120, true);
if ($extractedTitle) {
    // z.B. im Import-Workflow dem Redakteur als Vorschlag anzeigen
}
```

Hinweis: Die Methoden arbeiten auf dem gespeicherten `content` oder `content_raw` des Items und sind bewusst einfach gehalten, damit Redakteure im REDAXO-Backend leicht damit arbeiten können.

## Item-Klasse Referenz

Kurzreferenz aller relevanten Methoden der Klasse `FriendsOfRedaxo\Feeds\Item` (Datei: [lib/Item.php](lib/Item.php)).

| Methode | Rückgabe | Beschreibung |
|---|---:|---|
| `public static function table()` | `string` | Tabellenname für Items. |
| `public static function get($id)` | `?Item` | Liefert ein `Item`-Objekt aus der DB oder `null`. |
| `getTitle()` | `string` | Titel des Items. |
| `getContentRaw()` | `string` | Rohinhalt (unverändert). |
| `getContent()` | `string` | Aufbereiteter Inhalt. |
| `getId()` | `int` | Datenbank-ID. |
| `getUrl()` | `string` | Ursprung-URL des Eintrags. |
| `getDateTime()` | `?\DateTimeInterface` | Datum des Eintrags. |
| `getAuthor()` | `string` | Autor. |
| `getUsername()` | `string` | Username (falls vorhanden). |
| `getLanguage()` | `string` | Sprache. |
| `getMediaFilename()` | `?string` | Dateiname des gespeicherten Mediums oder `null`. |
| `getMediaSource()` | `?string` | Original-URL der Medienquelle. |
| `getMediaManagerUrl(string $type, bool $useOriginalFilename = false, bool $escape = true)` | `?string` | URL aus dem Media Manager. |
| `getMediaInfo(string $type)` | `?array` | Breite/Höhe/Format des Mediums oder `null`. |
| `getRaw()` | `string` | JSON-String der Rohdaten. |
| `hasMedia()` | `bool` | True, wenn ein `media_filename` gesetzt ist. |
| `getPlainTextContent()` | `string` | Inhalt als reiner Text (HTML entfernt). |
| `getTruncatedContent(int $length = 200, bool $endOnSentence = true, string $ellipsis = '…')` | `string` | Intelligentes Kürzen (Satz- oder Wortgrenze). |
| `public static function removeEmojis(string $text)` | `string` | Entfernt Emojis aus Text. |
| `public static function removeHashtags(string $text)` | `string` | Entfernt Hashtags (`#tag`). |
| `sanitizeContent(array $options = [])` | `string` | Convenience-Sanitizer (Emojis/Hashtags/Whitespace). |
| `extractTitleFromContent(string $stopSign = '::', int $maxLength = 120, bool $fallbackToFirstLine = true)` | `string` | Extrahiert einen Titel aus dem Content (Stop-Zeichen oder erste Zeile). |
| Setter: `setTitle`, `setType`, `setContentRaw`, `setContent`, `setUrl`, `setDate`, `setAuthor`, `setUsername`, `setLanguage` | — | Setter für Item-Felder. |
| Media/Raw: `setMedia`, `setMediaSource`, `setRaw`, `setOnline` | — | Methoden zur Medienbehandlung und Rohdaten. |
| Status/Save: `isOnline`, `exists`, `changedByUser`, `save()` | — | Statusabfragen und Persistenz (save() speichert das Item). |

Die obigen Signaturen und Beschreibungen sollen schnelle Orientierung geben; für Implementierungsdetails siehe [lib/Item.php](lib/Item.php).


## Bilder ausgeben mit dem Media Manager

Die Bilder eines Feeds werden im AddOn-Data-Ordner unter `data/addons/feeds/media` gespeichert. Für die Ausgabe der Bilder stehen zwei gleichwertige Möglichkeiten zur Verfügung.

### 1. Methode des Feed-Items


```php
// Mit Media Manager Effekt
$media_url = $item->getMediaManagerUrl('feeds_thumb');
echo '<img src="'.$media_url.'" alt="Mein Bild">';
```

Ermitteln der Medien-Infos: 

```php
$mediaInfo = $item->getMediaInfo('mein_media_type'); 
if ($mediaInfo) {
    echo "Breite: " . $mediaInfo['width'];
    echo "Höhe: " . $mediaInfo['height'];
    echo "Format: " . $mediaInfo['format'];
}
```


### 2. Direkt über den Mediamanager

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
use FriendsOfRedaxo\Feeds\Stream;

$stream_id = 1;
// Media Manager Typ wo der Feeds-Effekt als erster Effekt eingerichtet ist
$media_manager_type = 'feeds_thumb';

// Moderne Schreibweise (empfohlen)
$stream = Stream::get($stream_id);
// Alternativ: $stream = rex_feeds_stream::get($stream_id); // Weiterhin möglich, aber deprecated

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

### Moderne Schreibweise (empfohlen)

Hierzu erstellt man eine extended Class der `FriendsOfRedaxo\Feeds\Stream\AbstractStream` im lib Ordner des eigenen AddOns oder des project-AddOns an, z.B.: `MyCustomStream`. Man kann sich dabei an die mitgelieferten Classes im Ordner `/lib/Stream` halten.

```php
<?php
use FriendsOfRedaxo\Feeds\Stream\AbstractStream;

class MyCustomStream extends AbstractStream
{
    // Ihre Implementierung hier
}
```

Anschließend meldet man den neuen Provider wie folgt in der boot.php an:

```php 
use FriendsOfRedaxo\Feeds\Stream;

Stream::addStream("MyCustomStream");
```

### Legacy-Schreibweise (deprecated)

Die alte Schreibweise funktioniert weiterhin:

```php
// Erstellen einer extended Class der `rex_feeds_stream_abstract`
class rex_feeds_stream_my_class extends rex_feeds_stream_abstract
{
    // Ihre Implementierung hier
}

// Anmelden in der boot.php
rex_feeds_stream::addStream("rex_feeds_stream_my_class");
```


## Extension Points nutzen

Feeds kommt mit 2 Extension Points, namentlich `FEEDS_STREAM_FETCHED` nach Abruf eines Streams sowie `FEEDS_ITEM_SAVED` nach dem Speichern eines neuen Eintrags.

So lassen sich nach Abruf eines oder mehrerer Streams bestimmte Aktionen ausführen.

Weitere Infos zu Extension Points in REDAXO unter https://www.redaxo.org/doku/master/extension-points

[Zum GitHub-Repository von Feeds](github.com/FriendsOfREDAXO/feeds/)


## RSS Feed

Gebe einfach die URL zum Feed ein. ;-) 

## Mastodon

Einfach die Instanz (z.B. `mastodon.social`) und den Benutzernamen (ohne `@`) angeben. Das AddOn nutzt den öffentlichen RSS-Feed des Profils.

## Podcast

Der Podcast-Stream ist speziell für Audio-Feeds optimiert.
*   **Cover-Bild:** Wird bevorzugt aus `itunes:image` geladen.
*   **Audio-Datei:** Die URL zur MP3-Datei wird **nicht** heruntergeladen (um Speicherplatz zu sparen und Statistiken nicht zu verfälschen), sondern in den Rohdaten gespeichert.
*   **Anzahl:** Die Anzahl der abzurufenden Folgen kann begrenzt werden.

### Ausgabe eines Podcasts

Die Audio-URL und die Dauer befinden sich im `raw`-Datenfeld des Items.

```php
<?php
use FriendsOfRedaxo\Feeds\Stream;

$stream = Stream::get(1); // ID des Podcast-Streams
$items = $stream->getPreloadedItems();

foreach($items as $item) {
    // Rohdaten abrufen (als Array)
    $raw = json_decode($item->getRaw(), true);
    $audioUrl = $raw['audio_url'] ?? '';
    $duration = $raw['duration'] ?? '';

    echo '<div class="podcast-episode">';
    echo '<h3>' . rex_escape($item->getTitle()) . '</h3>';
    
    // Cover-Bild
    if($item->getMediaFilename()) {
        echo '<img src="'. $item->getMediaManagerUrl('feeds_thumb') .'" alt="">';
    }

    // Audio-Player
    if ($audioUrl) {
        echo '<audio controls src="' . rex_escape($audioUrl) . '"></audio>';
    }
    
    if ($duration) {
        echo '<small>Dauer: ' . rex_escape($duration) . '</small>';
    }
    
    echo '</div>';
}
?>
```

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
