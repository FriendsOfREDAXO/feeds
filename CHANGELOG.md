# Changelog

## 6.1.0 - 2026-01-08

- Neue Content-Helper in `FriendsOfRedaxo\Feeds\Item`:
  - `hasMedia()`, `getPlainTextContent()`, `getTruncatedContent()`
  - `removeEmojis()`, `removeHashtags()`, `sanitizeContent()`
  - `extractTitleFromContent()` (Stop-Zeichen-Extraktion)
- README: Dokumentation und API-Referenz der `Item`-Klasse ergänzt.

## 6.1.1 - 2026-01-08

- Abhängigkeiten aktualisiert: `symfony/http-client` auf 8.x, `redaxo/php-cs-fixer-config` auf 2.19.0.
  - Keine Plattform-Inkompatibilitäten auf PHP 8.4 festgestellt.
