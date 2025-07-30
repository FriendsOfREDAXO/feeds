<?php

/**
 * Diese Datei ist Teil des feeds-Pakets.
 *
 * @author FOR
 * @author cukabeka
 *
 */

namespace FriendsOfRedaxo\Feeds\Stream;

class Ics extends AbstractStream
{
    public function getTypeName()
    {
        return \rex_i18n::msg('feeds_ical_calendar');
    }

    public function getTypeParams()
    {
        return [
            [
                'label' => \rex_i18n::msg('feeds_ical_url'),
                'name' => 'url',
                'lang' => \rex_i18n::msg('feeds_ical_lang'),
                'type' => 'string',
            ],
        ];
    }

 public function fetch()
{
    $url = $this->typeParams['url'];

    try {
        $icsData = file_get_contents($url);
        if ($icsData === false) {
            throw new \Exception("Fehler beim Abruf der ICS-Datei.");
        }

        $events = $this->parseIcs($icsData);
        foreach ($events as $event) {
            $item = new \FriendsOfRedaxo\Feeds\Item($this->streamId, $event['UID']);
            $item->setTitle($event['SUMMARY']);
            $item->setContent(isset($event['DESCRIPTION']) ? $event['DESCRIPTION'] : '');
            $item->setUrl(isset($event['URL']) ? $event['URL'] : '');
            $item->setDate(new DateTime($event['DTSTART']));
            $item->setRaw(isset($event['DESCRIPTION']));

            // Spracheinstellung, hier als Beispiel fest auf "de" gesetzt, evtl in die
            // Da ICS-Dateien normalerweise keine Sprachinformationen enthalten, müssen Sie entscheiden, wie Sie die Sprache bestimmen möchten
            $item->setLanguage($this->typeParams['lang']);

            $this->updateCount($item);
            $item->save();
        }
    } catch (\Exception $e) {
        echo \rex_view::error($e->getMessage());
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
            if ($line[0] === " " || $line[0] === "\t") { // Fortsetzung einer Zeile
                $event[$currentKey] .= ltrim($line);
            } else {
                list($key, $value) = explode(':', $line, 2) + [null, null];
                if ($key && $value) {
                    // Behandlung von Eigenschaften mit Parametern (z.B. DTSTART;TZID=Europe/Berlin)
                    if (strpos($key, ';') !== false) {
                        list($key, $parameter) = explode(';', $key, 2);
                        // Optional: Parameterverarbeitung hier hinzufügen, falls benötigt
                    }
                    $currentKey = $key;
                    // Spezielle Behandlung für die DESCRIPTION
                    if ($key == 'DESCRIPTION') {
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
    $description = str_replace("\\n", "\n", $description);
    $description = str_replace("\\n\\n", "\n", $description);
    // Hier können weitere Anpassungen vorgenommen werden, z.B. das Entfernen oder Ersetzen von speziellen Zeichen
    return $description;
}
}   
