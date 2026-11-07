# wiedeck.de – persönliche Landingpage

Statische, mobiloptimierte persönliche Startseite mit vier Projektlogos und Links zu Tennis-Schiri, TENNILO, TENNILO Score und Social Media. Keine Build-Umgebung oder Tracking-Skripte. Ein kleines lokales JavaScript steuert den Hell-/Dunkelmodus ohne externe Dienste.

## Dateien

- `public/index.html` – Landingpage mit eingebettetem CSS
- `public/favicon.svg` – neutrales Favicon ohne Initialen
- `public/theme.js` – systemabhängiger und manuell umschaltbarer Darkmode; Wunsch via localStorage gespeichert
- `public/logos/` – Original-Wortmarken von Tennis-Schiri, TENNILO und TENNILO Score sowie WM04b-App-Symbol. Die jeweils passenden hellen und dunklen Logo-Varianten werden über `html[data-theme]` automatisch angezeigt; bei TENNILO und Score sorgt ein CSS-Farbfilter für kontrastreiche Schrift bei unveränderten Formen.
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
