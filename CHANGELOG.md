Changelog
=========

Version 1.3.0 – 01.07.2018
--------------------------

### Neu

* Vimeo Pro (@chrison94)
* Übersetzungen (@ynamite, @nandes2062, @interweave-media)

### Bugfixes

* Diverse Bugfixes für Instagram und Facebook (@alexplusde, @gharlan)


Version 1.2.1 – 05.01.2018
--------------------------

### Bugfixes

* Instagram: Abruf über inoffizielle API (ohne Access Token) funktionierte nicht mehr
* Media-Manager-Effekt: Teilweise wurden auch Dateinamen als YFeed-Datei gewertet, die nicht dem Schema `x.yfeed` entsprachen


Version 1.2 – 08.11.2017
------------------------

### Neu

* Sprechender Name für Media-Manager-Effekt

### Bugfixes

* Instagram-Benutzerfeed funktionierte nicht mehr über die offizielle API (Access Token hinterlegt)
* Media-Manager-Effekt: Caching hat nicht gegriffen


Version 1.1.2 – 07.09.2017
--------------------------

### Bugfixes

* Der Abruf von Instagram-Tags (ohne Access-Token) schlug auf 32-Bit-Systemen fehl


Version 1.1.1 – 07.09.2017
--------------------------

### Bugfixes

* Der Abruf über die inoffizielle Instragram-Schnittstelle (ohne Access-Token) funktionierte nicht mehr. 
  ACHTUNG: Beim Abruf eines Users-Feeds muss nun der Benutzername statt der Benutzer-ID hinterlegt werden


Version 1.1.0 – 15.05.2017
--------------------------

### Neu

- Youtube-Unterstützung
- Instagram-Unterstützung
- Bei Twitter wird auch das Bild ausgelesen, falls vorhanden
- Media-Manager-Effekt zur Auslieferung (und Weiterbearbeitung) der Bilder über den Media Manager

### Bugfixes

- Cronjob für Script-Umgebung korrigiert
- URL-Feld enthielt uneinheitliche Werte, nun immer die URL des Originalbeitrages
- Bei Twitter-Hashtags werden Retweets ignoriert
- Bei Twitter wurde die Original-ID teilweise nicht richtig gespeichert
- Bei Twitter wurden die Texte nicht immer vollständig eingelesen
