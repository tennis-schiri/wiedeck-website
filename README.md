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
- `.github/workflows/deploy.yml` – manueller FTPS-Upload aller Website-Dateien einschließlich Logos

## Veröffentlichung auf ALL-INKL

Die Domain `wiedeck.de` verwendet das separate Webspace-Verzeichnis **`/wiedeck-de/`**.

- GitHub-Secrets: `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD`
- Der FTP-Benutzer **muss mit dem Startverzeichnis `/wiedeck-de/` eingerichtet sein**, da der Workflow die Dateien relativ zum FTP-Startverzeichnis in dessen Wurzel ablegt.
- Die Veröffentlichung kann unter **Actions → wiedeck.de manuell veröffentlichen → Run workflow** gestartet werden.
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

## Header-Icon und Favicon ohne Hintergrund

Das Header-Monogramm und die Favicons werden **wirklich transparent** erzeugt (RGBA mit Alphakanal); die bisherigen Icon-PNGs mit fest eingebranntem hellem oder schwarzem Hintergrund bleiben nur als historische Quellen erhalten. Ausgangspunkt ist die freigegebene MW-Monogramm-Datei `branding/mw-monogram-original.png`. Die Bilder werden durch `scripts/build_mw_transparent.py` vor dem FTPS-Upload erzeugt. Für die Generierung wird Pillow ausschließlich im CI-Workflow installiert. Die Website lädt die Bilddateien lokal und wechselt ohne Tracker automatisch zwischen den beiden transparenten Varianten. Nach einer Änderung des Favicons ggf. den Browser-Cache beachten.

## Einheitliche MW-Logogröße

Die freigestellten MW-Hauptlogos besitzen unterschiedliche Rastermaße (hell 745×366, dunkel 666×371). Die Hero-Fläche hat deshalb ein festes Seitenverhältnis, und nur die dunkle Variante wird um 8 % vergrößert. Dadurch bleibt die sichtbare Wortmarke beim Hell-/Dunkel-Wechsel gleich groß, ohne Rahmen oder Kartendesign.
