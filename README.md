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
- Nach Änderungen am Workflow selbst startet zusätzlich ein Upload bei einem Push nach `main`. Änderungen an der Website lösen keinen automatischen Upload aus.
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
