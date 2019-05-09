# Feeds

Ein AddOn für REDAXO 5 zum Abruf externer Feeds.

## Features

* Abruf von Facebook-, Twitter-, YouTube-, Vimeo- und RSS-Feeds.
* Dauerhaftes Speichern der Beiträge in einer Datenbank-Tabelle
* Nachträgliche Aktualisierung der Beiträge (z.B. nach einem Update / einer Korrektur)
* Erweiterung um eigene Feed-Typen möglich, z.B. Google My Business o.a.
* Nutzung der importierten Bilddateien über den REDAXO Media Manager

## Installation

1. Im REDAXO-Backend unter `Installer` abrufen und 
2. anschließend unter `Hauptmenü` > `AddOns` installieren.

### Einen neuen Feed einrichten

1. Im REDAXO-Backend `AddOns` > `Feeds` aufrufen,
2. dort auf das `+`-Symbol klicken,
3. den Anweisungen der Stream-Einstellungen folgen und
4. anschließend speichern.

> **Hinweis:** Ggf. müssen zusätzlich in den Einstellungen von YForm Zugangsdaten (bspw. API-Schlüssel) hinterlegt werden, bspw. bei Facebook, Twitter oder YouTube.

### Feed aktualisieren

Die Feeds können manuell unter `AddOns` > `Feeds` abgerufen werden, oder in regelmäßigen Intervallen über einen Cronjob abgerufen werden:

1. Im REDAXO-Backend unter `AddOns` > `Cronjob` aufrufen,
2. dort auf das `+`-Symbol klicken,
3. als Umgebung z.B. `Frontend` auswählen,
4. als Typ `Feeds: Feeds abrufen` auswählen,
5. den Zeitpunkt festlegen (bspw. täglich, stündlich, ...) und
6. mit `Speichern` bestätigen.

Jetzt werden Feeds-Streams regelmäßig dann abgerufen, wenn die Website aufgerufen wird. [Weitere Infos zu REDAXO-Cronjobs](https://www.redaxo.org/doku/master/cronjobs).

## Feed ausgeben

Um ein Feed auszugeben, können die Inhalte in einem Modul oder Template per SQL abgerufen werden, z.B.:

```
$stream_id = 1; // ID des Streams in Feeds

$Feeds_items = rex_sql::factory()->getArray('SELECT * FROM rex_Feeds_item WHERE stream_id = :stream_id ORDER BY date DESC LIMIT 10', [":stream_id" => $stream_id]);

# dump($Feeds_items); // Zum Debuggen ansehen

foreach ($Feeds_items as $item) {
    echo strftime("%d. %B %Y", strtotime($item['date']));
    echo $item['content'];
    echo $item['url'];
    # echo $item['raw']; // weitere Details zum Beitrag im JSON-Format  
}
```

## Feeds erweitern

Um Feeds zu erweitern, kann man sich die Logik der von Haus aus mitgelieferten Feeds ansehen - hier am Beispiel "Twitter":

* In `/redaxo/src/addons/Feeds/pages/settings.twitter.php` wird die Einstellungsseite für das Hinterlegen von API-Keys u.a. Zugangsdaten für Twitter hinterlegt.

* In `/redaxo/src/addons/Feeds/lib/stream/twitter_user_timeline.php` wird die Logik für den Import der Tweets eines Users hinterlegt.

Diese lassen sich kopieren und bspw. im `project`-Addon anpassen. Zum Einhängen der Einstellungsseite in Feeds muss dann in der `package.yml` die Einstellungsseite registriert werden.

## Facebook-Feeds

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

> **Tipp:** Jeder Token kann [auf der Facebook Developer Debug-Seite](https://developers.facebook.com/tools/debug/accesstoken/) überprüft werden, welche Gültigkeitsdauer vorhanden ist.
