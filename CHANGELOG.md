# Changelog

## 6.2.2 - 2026-03-11

### Bugfixes

- **Media Manager Effect**: `rex_effect_feeds` ist jetzt die primäre Implementierung (non-namespaced) und direkt mit `rex_media_manager::addEffect()` kompatibel – behebt "Class not found"-Fehler bei selbst angelegten Media-Manager-Typen (Issue #260)
- `FriendsOfRedaxo\Feeds\MediaManagerEffect` bleibt als `@deprecated` Alias erhalten für Abwärtskompatibilität

## 6.2.0 - 2026-02-05

### Neue Features

- **Archiviert-Status**: Einträge können jetzt in 3 Zuständen existieren: Online (1), Offline (0), Archiviert (2)
  - Archivierte Einträge werden standardmäßig ausgeblendet (nur mit Status-Filter sichtbar)
  - Status-Auswahl als Bootstrap Selectpicker mit REDAXO-typischen Farben und Icons
- **Status-Filter**: Dropdown-Filter im Einträge-Bereich zum Filtern nach Status (Alle/Online/Offline/Archiviert)
- **Content-Preview**: Tooltip-Vorschau des Contents beim Hover über Titel (erste 200 Zeichen)
- **Duplicate Detection**: Automatische Erkennung doppelter URLs beim Fetch mit Warning-Log
- **Stream Health-Check**: Button zum Testen der Erreichbarkeit von Stream-URLs (RSS, YouTube, Podcast, iCal)
- **Konfigurierbare Einstellungen**: Neue Settings-Seite für HTTP-Timeouts, Media-Größen und Log-Level
- **Granulare Berechtigungen**: Separate Rechte für Streams-Verwaltung (`feeds[streams]`) und Einträge-Bearbeitung (`feeds[items]`)
- **Medien-Lightbox**: Klickbare Thumbnails öffnen Original-Bilder in Lightbox-Overlay

### Verbesserungen

- **Performance**: SQL-Injection-Schwachstellen behoben, Prepared Statements verwendet
- **Performance**: N+1 Query-Problem in `getPreloadedItems()` eliminiert durch Batch-Loading
- **Performance**: Composite Index `stream_status_date` für schnellere Queries
- **HTTP-Caching**: Etag und Last-Modified Header für effiziente Feeds-Abfrage
- **UI/UX**: Optimierte Einträge-Ansicht mit besserer Spaltenaufteilung und kürzeren Texten
- **UI/UX**: Einträge-Seite ist jetzt die Standard-Startseite des AddOns
- **UI/UX**: Status-Auswahl mit Bootstrap Selectpicker, REDAXO-Farben (grün/rot/grau) und Icons
- **Fehlerbehandlung**: Konfigurierbare Timeouts und besseres Error-Handling bei Netzwerkfehlern
- **Backward Compatibility**: Deprecated Klassen werden automatisch geladen (Issue #253)
- **Kompatibilität**: Symfony HTTP Client auf 6.4/7.x downgraded für REDAXO Core-Kompatibilität
- **CSP**: Inline-Scripts und Styles mit Nonce-Attributen für Content Security Policy

### Bugfixes

- Array-to-string Conversion Warnings in Settings-Page behoben
- Undefined array key Warnings in `Item::createFromDbRow()` behoben
- Medienanzeige in Einträge-Liste repariert
- TransportException bei DNS-Fehlern besser abgefangen

### Datenbank

- `rex_feeds_item.status` auf `tinyint(4)` erweitert für 3 Status-Zustände
- Neuer Composite Index `stream_status_date` für Performance-Optimierung

## 6.1.0 - 2026-01-08

- Neue Content-Helper in `FriendsOfRedaxo\Feeds\Item`:
  - `hasMedia()`, `getPlainTextContent()`, `getTruncatedContent()`
  - `removeEmojis()`, `removeHashtags()`, `sanitizeContent()`
  - `extractTitleFromContent()` (Stop-Zeichen-Extraktion)
- README: Dokumentation und API-Referenz der `Item`-Klasse ergänzt.

## 6.1.1 - 2026-01-08

- Abhängigkeiten aktualisiert: `symfony/http-client` auf 8.x, `redaxo/php-cs-fixer-config` auf 2.19.0.
  - Keine Plattform-Inkompatibilitäten auf PHP 8.4 festgestellt.

## 6.1.2 - 2026-01-08

- Erhöhte minimale PHP-Version des AddOns auf `>=8.4` (erforderlich durch `symfony/http-client` 8.x).
