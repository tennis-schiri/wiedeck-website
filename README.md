# wiedeck.de – persönliche Landingpage

Statische, mobiloptimierte persönliche Startseite mit vier Projektlogos und Links zu Tennis-Schiri, TENNILO, TENNILO Score und Social Media. Keine Build-Umgebung oder Tracking-Skripte. Ein kleines lokales JavaScript steuert den Hell-/Dunkelmodus ohne externe Dienste.

## Dateien

- `public/index.html` – Landingpage mit eingebettetem CSS
- `public/logos/mw-hauptlogo-{hell,dunkel}.png` – originales Michael-Wiedeck-Hauptlogo aus Drive (MW12-Familie), passend zum Hell-/Dunkelmodus
- `public/logos/mw-icon-{hell,dunkel}.png` – originale MW-Appsymbole als Navigationszeichen und Favicon
- `public/favicon.svg` – bisherige, nur noch als Legacy-Asset verbleibende Grafik
- `public/theme.js` – systemabhängiger und manuell umschaltbarer Darkmode; Wunsch via localStorage gespeichert
- `public/logos/` – Original-Wortmarken von Tennis-Schiri, TENNILO und TENNILO Score sowie WM04b-App-Symbol. Die jeweils passenden hellen und dunklen Logo-Varianten werden über `html[data-theme]` automatisch angezeigt; für TENNILO wird im Darkmode ein CSS-Farbfilter genutzt; TENNILO Score verwendet dagegen die beiden freigegebenen Originalvarianten aus dem finalen Logo-Set (02/12) ohne Filter.
- `public/impressum.html`, `public/datenschutz.html`, `public/legal.css` – Rechtliches
- `.github/workflows/deploy.yml` – automatischer FTPS-Live-Upload bei Änderungen auf `main`, zusätzlich manuell startbar

## Veröffentlichung auf ALL-INKL

Die Domain `wiedeck.de` verwendet das separate Webspace-Verzeichnis **`/wiedeck-de/`**.

- GitHub-Secrets: `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD`
- Der FTP-Benutzer **muss mit dem Startverzeichnis `/wiedeck-de/` eingerichtet sein**, da der Workflow die Dateien relativ zum FTP-Startverzeichnis in dessen Wurzel ablegt.
- Ein zusätzlicher manueller Upload kann unter **Actions → wiedeck.de automatisch veröffentlichen → Run workflow** gestartet werden.
- Änderungen an den Website-Dateien unter `public/` oder am Workflow lösen nach einem Push auf `main` automatisch einen Live-Upload aus. Zusätzlich bleibt der manuelle Upload über GitHub Actions verfügbar.
- Der Workflow überträgt alle Website-Dateien einschließlich der Logos nach `/wiedeck-de/`; er löscht keine Serverdateien.

## Vor oder nach dem Livegang prüfen

- Ob `wiedeck.de` und ggf. `www.wiedeck.de` auf `/wiedeck-de/` verweisen
- Persönliche Instagram-, Facebook- und X-Profile sowie Mailadresse
- Impressum und Datenschutzhinweise entsprechend den rechtlichen Anforderungen
- Vorherige WordPress-URLs und notwendige Weiterleitungen

## Lokal ansehen

`public/index.html` im Browser öffnen.

## Farbwelt Michael Wiedeck

Die Website nutzt die metallisch-graue MW-Markenwelt statt der früheren violetten Farbpalette: Perlweiß / Graphit / Silber. Der vorhandene nutzerseitig umschaltbare Darkmode bleibt erhalten. Original-Logos der vier Projekte werden nicht verändert.

## Freigestelltes MW-Logo

Das von Google Drive übernommene MW-Hauptlogo ist als Original-PNG mit eingebettetem Hintergrund gespeichert. In der Hero-Sektion werden die weißen und dunklen Hintergründe mittels lokal eingebetteter SVG-Farbmatten (`mw-cutout-light`, `mw-cutout-dark`) beim Rendern transparent gesetzt. Originaldateien bleiben unverändert. Der frühere Kartenrahmen, Kartenhintergrund, Innenabstand und Kartenschatten sind in beiden Darstellungsmodi entfernt.

## Einheitliche MW-Logos in beiden Themes

Alle sichtbaren MW-Monogramme auf wiedeck.de verwenden dasselbe, original
freigestellte `MW_Monogramm_transparent.png` aus Google Drive (im Repository:
`branding/mw-monogram-transparent.png`). Daraus entstehen automatisch die
Varianten `mw-symbol-light.png`, `mw-symbol-dark.png` und die beiden Favicons.

Auch die **vollständige Wortmarke mit MICHAEL WIEDECK** in der Startseiten-Hero
basiert jetzt auf einem echten transparenten Original:
`branding/mw-hauptlogo-hell-transparent.png` aus Google Drive. Das Build-Skript
erzeugt daraus die **identisch großen** Varianten
`mw-hauptlogo-hell-clean.png` und `mw-hauptlogo-dunkel-clean.png`.
Die dunkle Variante erhält helle silberne Flächen und eine helle Schrift, die
helle Variante behält die dunklen Original-Silbertöne.

Die alten SVG-Farbmasken `mw-cutout-*` und die alten, hintergrundhaltigen
Hauptlogo-Dateien werden auf der Startseite nicht mehr verwendet. Die korrekte
Sichtbarkeit steuert die gemeinsame CSS-Datei
`public/assets/mw-logo-theme.css`: **Hell zeigt immer `mw-theme-light`,
Dunkel immer `mw-theme-dark`**, unabhängig von anderen Bild-Stylesheets.
Das gilt für Header, großes Hauptlogo, Projekt 02, persönliches Intro und
Webdesign-Unterseite sowie für die Header der Restaurant-, Comedy- und
Rechtstextseiten, jeweils auf Deutsch und Englisch. Cache-Versionen sind
einheitlich, der gespeicherte Darstellungsmodus bleibt erhalten.

`scripts/check_mw_theme.py` prüft automatisch 12 Seiten, die MW-Logo-Paare,
die Dateigrößen, dieselbe Bildgeometrie/Transparenz und den unterschiedlichen
Silberkontrast. Die separate GitHub Action
`.github/workflows/mw-logo-qa.yml` erzeugt **ohne Live-Upload** vier
Bildvorschauen in beiden Themes; die automatischen und manuellen FTPS-Uploads führen denselben
Regressionscheck aus.

## TENNILO Umpire – Teaser

Auf der Projektübersicht wird **TENNILO Umpire** bewusst ohne Funktionsdetails angekündigt. Das Original-Logo ist der in Google Drive freigegebene Hauptentwurf `00_TENNILO_Umpire_Favorit_4K.png` aus `TENNILO Umpire/Logo`, eingebunden als `public/logos/tennilo-umpire.png`. Der Teaser führt wie TENNILO Score zu tennilo.de.

## Restaurant-Tagebuch (DE / EN)

- `public/restaurants.html` und `public/en/restaurants.html`: Die obere Kartenübersicht wird nur angezeigt, wenn veröffentlichte Besuchsberichte vorhanden sind. Sie enthält höchstens vier Restaurants, nach letztem Veröffentlichungsdatum sortiert. Diese Einträge werden nicht nochmals in der darunterliegenden Städteliste angezeigt.
- `public/redaktion/`: Passwortgeschütztes Backend für Restaurant-Stammdaten und beliebig viele getrennte Besuche pro Restaurant. 0–10-Sterne-Bewertungen, Fotos und Entwürfe bleiben bestehen.
- Die 23 auf der Startseite dokumentierten Hard Rock Cafes (mit zwei getrennten Londoner Standorten) gehören als eigenständige Restaurantdatensätze zur Liste: insgesamt **69 Lokale in 24 Städten**. Sie werden bei bestehenden Installationen **einmalig** ergänzt. Es werden keine persönlichen Restaurantbesuche, Bewertungen oder Berichte erfunden; spätere Löschungen werden nicht automatisch rückgängig gemacht.
- **Besuchsformulare:** Nur das Datum ist bei Restaurants und Comedy-Shows Pflicht; auch veröffentlichte Einträge dürfen ohne Bericht, Bewertungen oder Fotos gespeichert werden.
- Bei Formularfehlern zeigt der Server exakt die gerade eingegebenen Texte, Bewertungen und den Veröffentlichungsstatus erneut an, statt die alten Datenbankwerte zu laden. Das lokale Skript `redaktion/visits-draft.js` hält zusätzlich ungespeicherte Änderungen bis zum Schließen des Browser-Tabs in `sessionStorage` fest; nach erfolgreichem Speichern wird die Zwischensicherung gelöscht. Keine externe Übertragung.
- Auf `restaurants.html` werden date-only freigegebene Besuche in der Städteliste als „Besuch online“ verlinkt; die oberen maximal vier Karten bleiben Restaurants mit tatsächlich veröffentlichten Textberichten vorbehalten.

## Berichte ohne externe KI-Dienste

Restaurant- und Comedyberichte werden ausschließlich manuell im geschützten Backend geschrieben. Die frühere kostenpflichtige KI-Textgenerierung und die OpenAI-API-Anbindung sind entfernt. Der Live-Upload deaktiviert einmal vorhandene KI-PHP-Endpunkte und versucht, alte Skripte sowie den serverseitigen API-Schlüssel zu löschen. Bereits gespeicherte Restaurant- und Comedybesuche bleiben erhalten.

## Comedy-Clubs im selben Redaktions-Backend

- `/redaktion/` bietet nach dem Login den neuen Bereich `/redaktion/comedy.php`. Dieselbe Sitzung, derselbe geschützte SQLite-Speicher und dieselbe JSON-Datensicherung (Comedy-Clubs und Auftritte inklusive).
- Clubs und Shows haben eigene Datenbanktabellen. Die 26 bisher auf der Startseite aufgeführten Locations werden einmalig übernommen; manuell gelöschte Clubs werden nicht automatisch neu angelegt.
- Pro Show: Datum, optionale Bezeichnung, Comedians (ein Name pro Zeile), private Notizen und optional ein freier persönlicher Bericht. **Keine Künstlerbewertungen**. Die Redaktion zählt Besuche und unterschiedliche Comedians.
- Mit `Privat` bleibt die Show intern; `Öffentlich` erlaubt es, Datum und Lineup auf der Website sowie die Club-Zähler anzuzeigen. Ein Bericht ist dafür **nicht erforderlich**.
- `/comedy.html` und `/en/comedy.html` zeigen maximal vier Clubs mit veröffentlichten persönlichen Berichten. Die übrigen Clubs bleiben darunter aufgelistet. Die Startseite verlinkt das Comedy-Tagebuch.

## Webdesign & Entwicklung (eigene Unterseite)

- Die sechste Projektkarte der Startseite führt auf `/websites.html` bzw. `/en/websites.html`. Die bisherigen vier direkten Referenzlinks wurden von der Startseite entfernt; die Website-Unterseite zeigt jetzt fünf Referenzen einschließlich `wiedeck.de`.
- Leistungen: Konzeption, Design, Umsetzung, Redaktionssysteme, Formulare, mehrsprachige Seiten, Integrationen, Veröffentlichung und Pflege. **Keine Festpreise** – Umfang und Konditionen werden individuell vereinbart.
- Zu jeder Referenz gehört ein eigener Screenshot sowie eine zunächst geschlossene, mit HTML-`details` aufklappbare Funktionsübersicht und ein separater Link zur Live-Website.
- `scripts/capture_website_references.py` erzeugt nach jedem GitHub-Actions-Upload echte Screenshots der fünf öffentlichen Referenz-Websites mit einem Headless-Browser und Pillow unter `public/media/websites/*.webp` und lädt sie anschließend auf den Webspace. Die Bilddateien werden nicht ins Git-Repository geschrieben. Ist eine Website beim Upload nicht erreichbar, wird stattdessen eine **ausdrücklich als nicht verfügbar gekennzeichnete Vorschau** erstellt (kein nachgebildeter Screenshot).
- Das vorhandene `theme.js` stellt auf der Unterseite Hell-/Dunkelmodus und den animierten Nach-oben-Button bereit; das Layout der neuen Seiten liegt in `public/assets/websites.css`.
