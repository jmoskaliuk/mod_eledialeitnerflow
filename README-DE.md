# LeitnerFlow — Spaced-Repetition-Aktivität für Moodle

🇬🇧 [English version](README.md)

LeitnerFlow bringt das bewährte Leitner-Karteikartensystem nach Moodle. Fragen aus der Fragensammlung werden zu virtuellen Karteikarten, die durch mehrere Boxen wandern. Richtige Antworten befördern eine Karte weiter, falsche schicken sie zurück. Lernende arbeiten in kurzen, fokussierten Sessions und verfolgen ihren Fortschritt über ein visuelles Dashboard.

**Voraussetzung:** Moodle 4.4+
**Aktuelle Version:** 1.9.0
**Lizenz:** [GPL v3+](https://www.gnu.org/copyleft/gpl.html)
**Entwicklung:** [eLeDia GmbH](https://eledia.de)

## So funktioniert es

Eine Lehrkraft erstellt eine LeitnerFlow-Aktivität und wählt eine oder mehrere Fragenkategorien aus der Fragensammlung. Jede Frage wird zu einer Karteikarte, die in Box 1 startet. Beantwortet ein Lernender richtig, wandert die Karte in die nächste Box. Bei einer falschen Antwort geht sie zurück (konfigurierbar). Wurde eine Karte oft genug richtig beantwortet, gilt sie als „Gelernt".

Karten in niedrigeren Boxen erscheinen häufiger — so konzentrieren sich Lernende automatisch auf die schwierigsten Inhalte. Das System unterstützt drei Verhaltensweisen bei falschen Antworten: Komplett-Reset auf Box 1, eine Box zurück, oder keine Veränderung.

## Funktionen

**Für Lernende:**

- Visuelle Leitner-Box-Anzeige mit Kartenverteilung auf einen Blick
- Mehrstufiger Fortschrittsbalken (Farbverlauf warm→kalt: Orange → Aqua → Blau → Grün)
- Session-Historie mit Richtig-Quote, Fortschrittsbalken und Dauer
- Trend-Indikator: Vergleich der letzten Sessions mit dem Gesamtdurchschnitt
- Klickbare Boxen zum gezielten Üben bestimmter Schwierigkeitsstufen
- Kartenanimation mit konfigurierbarem Feedback-Stil (Minimal, Ermutigend oder Aus)
- Geführte Einführungstour beim ersten Besuch (mehrsprachig: DE/EN)

**Für Lehrkräfte:**

- Flexible Fragenauswahl aus mehreren Fragenkategorien
- Konfigurierbare Box-Anzahl (1–5), Session-Größe und Richtig-Schwelle
- Drei Strategien bei falscher Antwort: Reset, eine zurück, keine Änderung
- Dynamischer oder fester Fragenpool
- Kartenauswahl-Priorität: Niedere Boxen zuerst oder gemischte Zufallsauswahl
- Optionale Bewertungsintegration (Prozentsatz gelernter Karten)
- Teilnehmerbericht mit Fortschritt, Session-Anzahl und Reset-Möglichkeit
- Übersichts-Dashboard: Teilnehmerzahl, Pool-Größe, durchschnittlicher Lernfortschritt

**Technisch:**

- Volle Integration mit der Moodle Question Engine (immediatefeedback-Verhalten)
- Backup und Restore
- Privacy-API / DSGVO-konform (Export + Löschung)
- Event-Logging (Session gestartet, abgeschlossen, Fortschritt zurückgesetzt)
- Kurs-Reset-Unterstützung
- AMD-JavaScript-Module (Tastenkürzel, Animationen, Bestätigungs-Dialoge)
- 22 PHPUnit-Tests für die Leitner-Engine

## Installation

1. Repository in das Verzeichnis `mod/leitnerflow` der Moodle-Installation klonen:
   ```bash
   cd /pfad/zu/moodle/mod
   git clone https://github.com/jmoskaliuk/moodle-mod_leitnerflow.git leitnerflow
   ```
2. **Website-Administration → Mitteilungen** aufrufen, um das Datenbank-Upgrade auszulösen.
3. Eine LeitnerFlow-Aktivität in einem Kurs anlegen und Fragenkategorien auswählen.

## Einstellungen

| Einstellung | Optionen | Standard |
|-------------|----------|----------|
| Fragenkategorien | Mehrfachauswahl aus der Fragensammlung | — |
| Fragenrotation | Dynamisch / Fester Pool | Dynamisch |
| Fragen pro Session | Beliebige Zahl | 20 |
| Anzahl Boxen | 1–5 | 3 |
| Richtige Antworten bis „gelernt" | Beliebige Zahl | 3 |
| Bei falscher Antwort | Reset auf Box 1 / Eine Box zurück / Keine Änderung | Reset |
| Kartenauswahl | Niedere Boxen zuerst / Gemischt zufällig | Niedere Boxen zuerst |
| Bewertung | Keine / % gelernter Karten | Keine |
| Kartenanimation | Ja / Nein | Ja |
| Feedback-Stil | Aus / Minimal / Ermutigend | Minimal |

## Dateistruktur

```
leitnerflow/
├── amd/src/              AMD-JavaScript-Module
│   ├── card_transition.js    Kartenanimation + Auto-Weiterleitung
│   ├── confirm_reset.js      Reset-Bestätigungs-Dialog
│   └── quiz_session.js       Tastenkürzel, Auto-Fokus
├── backup/moodle2/       Backup & Restore
├── classes/
│   ├── engine/               Leitner-Spaced-Repetition-Algorithmus
│   ├── event/                Session- und Fortschritts-Events
│   └── privacy/              DSGVO-Provider
├── cli/                  CLI-Werkzeuge (Testdaten-Generierung)
├── db/
│   ├── access.php            Berechtigungen
│   ├── install.php           Post-Install (User Tour, Multilang-Filter)
│   ├── install.xml           Datenbank-Schema (3 Tabellen)
│   ├── upgrade.php           Migrations-Schritte
│   └── usertours/            User-Tour-Definition (JSON)
├── lang/                 Sprachstrings (DE + EN)
├── pix/                  Icons (SVG + PNG, Lucide-Stil)
├── tests/                PHPUnit-Tests (22 Testfälle)
├── attempt.php           Fragen-Seite
├── lib.php               Moodle-API-Callbacks
├── mod_form.php          Aktivitätseinstellungen
├── report.php            Lehrkraft-Bericht
├── styles.css            Eigene Styles (eLeDia-Markenfarben)
└── view.php              Lernenden-Dashboard
```

## Berechtigungen

| Berechtigung | Beschreibung | Standard-Rollen |
|-------------|-------------|-----------------|
| `mod/leitnerflow:addinstance` | Neue Aktivität anlegen | Trainer/in mit Bearbeitungsrecht, Manager/in |
| `mod/leitnerflow:view` | Aktivität ansehen | Teilnehmer/in, Trainer/in, Manager/in |
| `mod/leitnerflow:attempt` | Fragen beantworten | Teilnehmer/in, Manager/in |
| `mod/leitnerflow:viewreport` | Teilnehmerbericht einsehen | Trainer/in, Manager/in |
| `mod/leitnerflow:manage` | Einstellungen verwalten | Trainer/in mit Bearbeitungsrecht, Manager/in |
| `mod/leitnerflow:resetprogress` | Fortschritt zurücksetzen | Trainer/in mit Bearbeitungsrecht, Manager/in |

## Tests ausführen

```bash
cd /pfad/zu/moodle
php admin/tool/phpunit/cli/init.php
vendor/bin/phpunit --testsuite mod_leitnerflow_testsuite
```

## Mitwirken

Beiträge sind willkommen. Bitte die [Moodle Coding Standards](https://moodledev.io/general/development/policies/codingstyle) einhalten und PHPUnit-Tests für neue Engine-Logik mitliefern.

## Lizenz

Dieses Programm ist freie Software: Sie können es unter den Bedingungen der GNU General Public License, wie von der Free Software Foundation veröffentlicht, weitergeben und/oder modifizieren — entweder gemäß Version 3 der Lizenz oder (nach Ihrer Wahl) jeder späteren Version.
