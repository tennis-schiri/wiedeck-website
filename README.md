# wiedeck.de – persönliche Landingpage

Statische, mobiloptimierte persönliche Startseite mit Links zu Tennis-Schiri, TENNILO und Social Media. Keine externe Build-Umgebung, kein JavaScript und keine Tracking-Skripte.

## Dateien

- `public/index.html` – Landingpage mit eingebettetem CSS
- `public/favicon.svg` – MW-Favicon
- `.github/workflows/deploy.yml` – manueller FTPS-Upload **nur auf ausdrücklichen Start**

## Lokal ansehen

`public/index.html` im Browser öffnen.

## Veröffentlichung (noch nicht eingerichtet)

Die vorhandene Website `wiedeck.de` wird **nicht** automatisch ersetzt. Vor dem ersten Upload:

1. Vorhandene WordPress-Seite sichern und in ALL-INKL ein neues, separates Verzeichnis für die neue Seite anlegen.
2. Domain `wiedeck.de` erst nach Freigabe auf dieses Verzeichnis umstellen; bestehende Installation bis dahin nicht überschreiben.
3. Einen separaten FTPS-Benutzer erstellen, dessen Startverzeichnis exakt das neue Website-Verzeichnis ist.
4. In GitHub unter **Settings → Secrets and variables → Actions** die Secrets `FTP_HOST`, `FTP_USER`, `FTP_PASSWORD` hinterlegen.
5. Erst dann **Actions → wiedeck.de manuell veröffentlichen → Run workflow** starten.

Der Workflow lädt nur `index.html` und `favicon.svg` hoch. Er löscht keine Dateien und löst sich nicht bei jedem Commit aus.

## Vor Livegang prüfen

- Persönliche Instagram-, Facebook- und X-Profile sowie Mailadresse auf Richtigkeit prüfen.
- Impressum / Datenschutzhinweise rechtlich prüfen und bei Bedarf ergänzen, bevor die neue Seite öffentlich geschaltet wird.
- Die Projektlinks auf https://www.tennis-schiri.de/ und https://tennilo.de/ überprüfen.
- Prüfen, ob die alte WordPress-Seite noch Inhalte/URLs hat, für die Weiterleitungen nötig sind.