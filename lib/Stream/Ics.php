<?php

/**
 * Diese Datei ist Teil des feeds-Pakets.
 *
 * @author FOR
 * @author cukabeka
 */

namespace FriendsOfRedaxo\Feeds\Stream;

use DateTime;
use Exception;
use FriendsOfRedaxo\Feeds\Item;
use rex_i18n;
use rex_view;

class Ics extends AbstractStream
{
    public function getTypeName()
    {
        return rex_i18n::msg('feeds_ical_calendar');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => rex_i18n::msg('feeds_ical_url'),
                'name' => 'url',
                'lang' => rex_i18n::msg('feeds_ical_lang'),
                'type' => 'string',
            ],
        ];
    }

    public function fetch()
    {
        $url = $this->typeParams['url'];

        try {
            $icsData = file_get_contents($url);
            if (false === $icsData) {
                $errorMessage = rex_i18n::msg('feeds_error_fetch_ics') ?: 'Error fetching ICS data.';
                throw new Exception($errorMessage);
            }

            $events = $this->parseIcs($icsData);
            foreach ($events as $event) {
                $item = new Item($this->streamId, $event['UID']);
                $item->setTitle($event['SUMMARY']);
                $item->setContent($event['DESCRIPTION'] ?? '');
                $item->setUrl($event['URL'] ?? '');
                $item->setDate(new DateTime($event['DTSTART']));
                $item->setRaw((bool) ($event['DESCRIPTION'] ?? null));

                // Spracheinstellung, hier als Beispiel fest auf "de" gesetzt, evtl in die
                // Da ICS-Dateien normalerweise keine Sprachinformationen enthalten, müssen Sie entscheiden, wie Sie die Sprache bestimmen möchten
                $item->setLanguage($this->typeParams['lang']);

                if (!$this->filter($item)) {
                    continue;
                }

                $this->updateCount($item);
                $item->save();
            }
        } catch (Exception $e) {
            echo rex_view::error($e->getMessage());
        }
    }

    private function parseIcs($icsData)
    {
        $lines = explode("\n", $icsData);
        $events = [];
        $event = [];
        $currentKey = '';

        foreach ($lines as $line) {
            if (str_starts_with($line, 'BEGIN:VEVENT')) {
                $event = []; // Neues Event starten
            } elseif (str_starts_with($line, 'END:VEVENT')) {
                $events[] = $event; // Event hinzufügen
            } else {
                if (' ' === $line[0] || "\t" === $line[0]) { // Fortsetzung einer Zeile
                    $event[$currentKey] .= ltrim($line);
                } else {
                    [$key, $value] = explode(':', $line, 2) + [null, null];
                    if ($key && $value) {
                        // Behandlung von Eigenschaften mit Parametern (z.B. DTSTART;TZID=Europe/Berlin)
                        if (str_contains($key, ';')) {
                            [$key, $parameter] = explode(';', $key, 2);
                            // Optional: Parameterverarbeitung hier hinzufügen, falls benötigt
                        }
                        $currentKey = $key;
                        // Spezielle Behandlung für die DESCRIPTION
                        if ('DESCRIPTION' == $key) {
                            $value = $this->formatDescription($value);
                        }
                        $event[$key] = $value;
                    }
                }
            }
        }
        return $events;
    }

    private function formatDescription($description)
    {
        // Ersetzt \n durch echte Zeilenumbrüche und behandelt andere spezielle Fälle
        $description = str_replace('\\n', "\n", $description);
        $description = str_replace('\\n\\n', "\n", $description);
        // Hier können weitere Anpassungen vorgenommen werden, z.B. das Entfernen oder Ersetzen von speziellen Zeichen
        return $description;
    }
}
