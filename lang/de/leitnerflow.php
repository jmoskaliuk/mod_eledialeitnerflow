<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * German language strings for mod_leitnerflow.
 *
 * @package    mod_leitnerflow
 * @copyright  2024 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname']         = 'LeitnerFlow Activity';
$string['modulename']         = 'LeitnerFlow Activity';
$string['modulenameplural']   = 'LeitnerFlow Activities';
$string['modulename_help']    = 'Das Leitner-Quiz nutzt das Karteikartensystem nach Sebastian Leitner (Spaced Repetition), um Schüler beim effizienten Lernen zu unterstützen. Karten wandern je nach Antwort durch Boxen und werden so lange wiederholt, bis sie sicher beherrscht werden.';
$string['pluginadministration'] = 'LeitnerFlow-Verwaltung';

$string['questioncategory']        = 'Fragenkategorie';
$string['questioncategory_help']   = 'Wähle die Kategorie aus der Fragensammlung, aus der Fragen gezogen werden.';
$string['sessionsize']             = 'Fragen pro Durchgang';
$string['sessionsize_help']        = 'Wie viele Fragen pro Lernsession gestellt werden.';
$string['boxcount']                = 'Anzahl Leitner-Boxen';
$string['boxcount_help']           = 'Die Anzahl der Boxen (Stufen) im Leitner-System. Mehr Boxen = feinere Fortschrittsanzeige.';
$string['correcttolearn']          = 'Richtige Antworten bis „gelernt"';
$string['correcttolearn_help']     = 'Wie oft eine Frage insgesamt richtig beantwortet werden muss, bis sie als „gelernt" gilt.';
$string['wrongbehavior']           = 'Verhalten bei falscher Antwort';
$string['wrongbehavior_help']      = 'Was passiert mit dem Fortschritt einer Karte, wenn die Antwort falsch ist.';
$string['wrongbehavior_reset']     = 'Auf Box 1 zurücksetzen (voller Reset)';
$string['wrongbehavior_back1']     = 'Eine Box zurück';
$string['wrongbehavior_nochange']  = 'Kein Abzug (nur kein Fortschritt)';
$string['questionrotation']        = 'Fragenrotation';
$string['questionrotation_help']   = 'Dynamisch: Fragen werden immer frisch aus der Fragensammlung geholt. Fest: Der Pool wird beim ersten Start gesperrt.';
$string['questionrotation_dynamic'] = 'Dynamisch (immer aus der Bank)';
$string['questionrotation_fixed']   = 'Fester Pool (beim ersten Start gesperrt)';
$string['prioritystrategy']        = 'Kartenauswahlstrategie';
$string['prioritystrategy_help']   = 'Priorisiert: Fragen aus niederen Boxen werden zuerst gestellt. Gemischt: Zufällige Auswahl aus allen Boxen.';
$string['prioritystrategy_prio']   = 'Niedere Boxen bevorzugen';
$string['prioritystrategy_mixed']  = 'Gemischte Zufallsauswahl';
$string['grademethod']             = 'Bewertungsmethode';
$string['grademethod_none']        = 'Keine Bewertung';
$string['grademethod_percent']     = 'Prozentsatz der gelernten Karten';
$string['gradingsettings']         = 'Bewertungseinstellungen';
$string['leitnersettings']         = 'Leitner-System-Einstellungen';
$string['sessionsettings']         = 'Sitzungseinstellungen';

$string['startsession']            = 'Lernsession starten';
$string['continuesession']         = 'Session fortsetzen';
$string['sessioninprogress']       = 'Session läuft';
$string['nosessionactive']         = 'Keine aktive Session';
$string['yourprogress']            = 'Dein Fortschritt';
$string['totalcards']              = 'Karten gesamt';
$string['learned']                 = 'Gelernt';
$string['open']                    = 'Offen';
$string['witherrors']              = 'Mit Fehlern';
$string['viewreport']              = 'Vollständigen Bericht anzeigen';
$string['sessioncomplete']         = 'Session abgeschlossen!';
$string['sessionresult']           = 'Du hast {$a->correct} von {$a->total} Fragen richtig beantwortet.';
$string['alllearned']              = 'Alle Karten gelernt! Hervorragend.';
$string['nocardsinpool']           = 'Keine Fragen in der gewählten Kategorie gefunden. Bitte füge zuerst Fragen zur Fragensammlung hinzu.';
$string['nounlearnedcards']        = 'Alle Karten sind bereits gelernt! Du kannst deinen Fortschritt zurücksetzen, um neu zu beginnen.';

$string['question']                = 'Frage';
$string['of']                      = 'von';
$string['cardstatus_box']          = 'Box {$a}';
$string['cardstatus_learned']      = 'Gelernt';
$string['correct']                 = 'Richtig!';
$string['incorrect']               = 'Falsch.';
$string['nextquestion']            = 'Nächste Frage';
$string['finishsession']           = 'Session beenden';
$string['check']                   = 'Antwort prüfen';
$string['correctanswer']           = 'Richtige Antwort';
$string['movedtobox']              = 'Karte in Box {$a} verschoben';
$string['cardlearned']             = 'Karte als gelernt markiert!';
$string['cardreset']               = 'Karte auf Box 1 zurückgesetzt.';
$string['cardbackone']             = 'Karte eine Box zurück verschoben.';

$string['report']                  = 'Schülerübersicht';
$string['student']                 = 'Schüler/in';
$string['progress']                = 'Fortschritt';
$string['sessions']                = 'Sessions';
$string['lastsession']             = 'Letzte Session';
$string['nostudents']              = 'Noch kein Schüler hat diese Aktivität gestartet.';
$string['resetprogress']           = 'Fortschritt zurücksetzen';
$string['resetconfirm']            = 'Soll der gesamte Fortschritt von {$a} wirklich zurückgesetzt werden? Das kann nicht rückgängig gemacht werden.';
$string['progressreset']           = 'Fortschritt wurde zurückgesetzt.';

$string['privacy:metadata']                              = 'Das Leitner-Quiz speichert individuelle Kartenzustände und Sessiondaten pro Schüler.';
$string['privacy:metadata:leitnerflow_card_state']       = 'Verfolgt den Fortschritt jedes Schülers pro Frage.';
$string['privacy:metadata:leitnerflow_card_state:userid']       = 'ID des Schülers.';
$string['privacy:metadata:leitnerflow_card_state:questionid']   = 'ID der Frage.';
$string['privacy:metadata:leitnerflow_card_state:currentbox']   = 'Aktuelle Leitner-Box der Karte.';
$string['privacy:metadata:leitnerflow_card_state:correctcount'] = 'Anzahl richtig beantworteter Versuche.';
$string['privacy:metadata:leitnerflow_card_state:attemptcount'] = 'Gesamtanzahl der Versuche.';
$string['privacy:metadata:leitnerflow_card_state:status']       = 'Kartenstatus: offen, gelernt oder mit Fehlern.';
$string['privacy:metadata:leitnerflow_sessions']                = 'Speichert jede abgeschlossene Lernsession.';
$string['privacy:metadata:leitnerflow_sessions:userid']         = 'ID des Schülers.';
$string['privacy:metadata:leitnerflow_sessions:timecreated']    = 'Startzeitpunkt der Session.';
$string['privacy:metadata:leitnerflow_sessions:timecompleted']  = 'Endzeitpunkt der Session.';
$string['privacy:metadata:leitnerflow_sessions:questionsasked'] = 'Anzahl gestellter Fragen.';
$string['privacy:metadata:leitnerflow_sessions:questionscorrect'] = 'Anzahl richtig beantworteter Fragen.';

$string['leitnerflow:addinstance']   = 'Neue LeitnerFlow-Aktivität hinzufügen';
$string['leitnerflow:view']          = 'Leitner-Quiz ansehen';
$string['leitnerflow:attempt']       = 'Leitner-Quiz durchführen';
$string['leitnerflow:viewreport']    = 'Schülerbericht anzeigen';
$string['leitnerflow:manage']        = 'Leitner-Quiz-Einstellungen verwalten';
$string['leitnerflow:resetprogress'] = 'Schülerfortschritt zurücksetzen';

$string['box_n']  = 'Box {$a}';
$string['box_1']  = 'Box 1 – Neu / Fehler';
$string['box_learned'] = 'Gelernt';

// Events
$string['event_session_started']   = 'Lernsession gestartet';
$string['event_session_completed'] = 'Lernsession abgeschlossen';
$string['event_progress_reset']    = 'Schülerfortschritt zurückgesetzt';

$string['invalidsession']   = 'Ungültige oder abgelaufene Session.';
$string['nocategory']       = 'Keine Fragenkategorie konfiguriert. Bitte Aktivitätseinstellungen bearbeiten.';
$string['error_noattempt']  = 'Du hast keine Berechtigung, dieses Quiz durchzuführen.';
