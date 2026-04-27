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
 * German language strings for mod_eledialeitnerflow.
 *
 * @package    mod_eledialeitnerflow
 * @copyright  2026 eLeDia GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['activesessioninfo']  = 'Aktive Lerneinheit: {$a->answered} von {$a->total} beantwortet, {$a->correct} richtig';
$string['activityname']       = 'LeitnerFlow';
$string['alllearned']         = 'Alle Fragen gelernt!';
$string['animationdelay']            = 'Anzeigedauer der Rückmeldung';
$string['animationdelay_help']       = 'Wie lange die Rückmeldung und die Fach-Animation angezeigt werden, bevor die nächste Frage automatisch geladen wird. Gilt nicht für den Feedback-Stil "Detailliert" (dort muss auf eine Schaltfläche geklickt werden). Kürzere Zeiten beschleunigen die Lerneinheit; längere Zeiten geben den Teilnehmenden mehr Zeit, die Rückmeldung zu lesen.';
$string['avgcorrect']         = 'Durchschnitt: {$a} % richtig';
$string['avglearnedpercent']  = 'Ø % gelernt';
$string['backtooverview']     = 'Zurück zur Übersicht';
$string['box_1']       = 'Fach 1 – Neu / Fehler';
$string['box_learned'] = 'Gelernt';
$string['box_n']       = 'Fach {$a}';
$string['boxcount']                  = 'Anzahl der Fächer';
$string['boxcount_help']             = 'Wie viele Leitner-Fächer (Stufen) verwendet werden. Karten wandern von Fach 1 → Fach N → Gelernt. Mehr Fächer bedeuten feinere Abstufung zwischen Wiederholungen. 3 Fächer sind ein guter Standard; 5 Fächer bieten feinere Abstufung für größere Fragenpools.';
$string['boxdistribution']    = 'Verteilung der Leitner-Fächer';
$string['cancelsession']      = 'Lerneinheit abbrechen';
$string['cardbackone']        = 'Karte ein Fach zurückgesetzt.';
$string['cardlearned']        = 'Karte als gelernt markiert!';
$string['cardreset']          = 'Karte zurück auf Fach 1.';
$string['cardselection']             = 'Kartenauswahl';
$string['cardstatus_box']     = 'Fach {$a}';
$string['cardstatus_learned'] = 'Gelernt';
$string['continuesession']    = 'Lerneinheit fortsetzen';
$string['correct']            = 'richtig';
$string['correctanswer']      = 'Richtige Antwort';
$string['correctrate']        = 'Richtig';
$string['correcttolearn']            = 'Benötigte richtige Antworten';
$string['correcttolearn_help']       = 'Anzahl der richtigen Antworten, die insgesamt nötig sind, bis eine Karte als "gelernt" markiert und aus der aktiven Rotation entfernt wird. Höhere Werte bedeuten mehr Wiederholung. Beispiel: Bei 3 müssen Teilnehmende eine Karte dreimal (über alle Lerneinheiten hinweg) richtig beantworten, bevor sie in den Gelernt-Stapel wandert.';
$string['current']            = 'aktuell';
$string['detailed_correct']          = 'Richtig! Die Karte wandert von Fach {$a->from} in Fach {$a->to}.';
$string['detailed_correct_stay']     = 'Richtig! Die Karte bleibt in Fach {$a}.';
$string['detailed_learned']          = 'Richtig! Diese Karte ist jetzt vollständig gelernt!';
$string['detailed_wrong_back']       = 'Falsch. Die Karte wandert von Fach {$a->from} zurück in Fach {$a->to}.';
$string['detailed_wrong_stay']       = 'Falsch. Die Karte bleibt in Fach {$a}.';
$string['displaysettings']           = 'Anzeige';
$string['eledialeitnerflow:addinstance']   = 'Neue LeitnerFlow-Aktivität hinzufügen';
$string['eledialeitnerflow:attempt']       = 'LeitnerFlow bearbeiten';
$string['eledialeitnerflow:manage']        = 'LeitnerFlow-Einstellungen verwalten';
$string['eledialeitnerflow:resetprogress'] = 'Lernfortschritt zurücksetzen';
$string['eledialeitnerflow:view']          = 'LeitnerFlow anzeigen';
$string['eledialeitnerflow:viewreport']    = 'Lehrenden-Übersicht anzeigen';
$string['encourage_correct_1']       = 'Sehr gut! Weiter in Fach {$a}.';
$string['encourage_correct_2']       = 'Stark — weiter so!';
$string['encourage_correct_3']       = 'Richtig! Die Karte rückt in Fach {$a} auf.';
$string['encourage_correct_4']       = 'Sieht gut aus! Fach {$a} füllt sich.';
$string['encourage_correct_5']       = 'Perfekt — nächstes Fach!';
$string['encourage_learned_1']       = 'Gemeistert! Diese Karte ist gelernt!';
$string['encourage_learned_2']       = 'Sitzt! Ab in den Gelernt-Stapel!';
$string['encourage_learned_3']       = 'Brillant — eine Karte weniger zu lernen!';
$string['encourage_wrong_back_1']    = 'Nicht ganz — zurück in Fach {$a}.';
$string['encourage_wrong_back_2']    = 'Ups, die Karte rutscht zurück.';
$string['encourage_wrong_back_3']    = 'Kein Problem — beim nächsten Mal klappt es!';
$string['encourage_wrong_back_4']    = 'Fast! Ein Fach zurück in Fach {$a}.';
$string['encourage_wrong_stay_1']    = 'Knapp daneben! Die Karte bleibt in Fach {$a}.';
$string['encourage_wrong_stay_2']    = 'Weiter üben — die Karte bleibt, wo sie ist.';
$string['endsession']         = 'Lerneinheit beenden';
$string['error_noattempt'] = 'Sie haben keine Berechtigung, diese Aktivität zu bearbeiten.';
$string['event_progress_reset']    = 'Lernfortschritt zurückgesetzt';
$string['event_session_completed'] = 'Lerneinheit abgeschlossen';
$string['event_session_started']   = 'Lerneinheit gestartet';
$string['feedbackstyle']             = 'Feedback-Stil';
$string['feedbackstyle_animated']    = 'Animiert';
$string['feedbackstyle_detailed']    = 'Detailliert';
$string['feedbackstyle_gamified']    = 'Spielerisch';
$string['feedbackstyle_help']        = 'Legt fest, wie Teilnehmende nach einer Antwort Rückmeldung erhalten.<br><b>Aus</b>: Keine Rückmeldung, die nächste Frage erscheint sofort.<br><b>Minimal</b>: Kurze Sachmeldung ("Karte → Fach 3"), blendet nach 2 Sekunden aus.<br><b>Animiert</b>: Motivierende, wechselnde Meldungen plus ein Leucht-Effekt auf dem Zielfach.<br><b>Detailliert</b>: Zeigt die richtige Antwort und den Fach-Wechsel (von → nach). Bleibt sichtbar, bis auf "Nächste Frage" geklickt wird.<br><b>Spielerisch</b>: Punkte, Streak-Zähler und Meilensteine. Besonders geeignet für jüngere Lernende.';
$string['feedbackstyle_minimal']     = 'Minimal';
$string['feedbackstyle_off']         = 'Aus';
$string['finishsession']      = 'Lerneinheit abschließen';
$string['grademethod']               = 'Bewertung';
$string['grademethod_none']          = 'Keine Bewertung';
$string['grademethod_percent']       = '% der gelernten Karten';
$string['gradepassmustbenonnegative'] = 'Die Bestehensgrenze muss null oder größer sein.';
$string['gradingsettings']           = 'Bewertung';
$string['incorrect']          = 'Falsch.';
$string['invalidsession']  = 'Ungültige oder abgelaufene Lerneinheit.';
$string['lastsession']   = 'Letzte Lerneinheit';
$string['learned']            = 'Gelernt';
$string['leitnersettings']           = 'Leitner-System';
$string['milestone_10learned']       = '10 Karten gelernt!';
$string['milestone_5learned']        = '5 Karten gelernt!';
$string['milestone_streak10']        = '10 in Folge! Nicht zu stoppen!';
$string['milestone_streak3']         = '3 in Folge!';
$string['milestone_streak5']         = '5 in Folge! Du bist in Form!';
$string['modulename']           = 'LeitnerFlow';
$string['modulename_help']      = 'LeitnerFlow nutzt die bewährte Methode des verteilten Lernens nach Sebastian Leitner, um Teilnehmende effizient lernen zu lassen. Fragen aus der Fragensammlung werden zu virtuellen Karteikarten, die durch eine Reihe von Fächern wandern. Richtige Antworten befördern eine Karte in das nächste Fach; falsche Antworten schicken sie zurück. Karten in niedrigeren Fächern erscheinen häufiger, so dass sich Teilnehmende auf das konzentrieren, was ihnen am schwersten fällt. Sobald eine Karte ausreichend oft richtig beantwortet wurde, wandert sie in den Gelernt-Stapel. Teilnehmende arbeiten in kurzen, fokussierten Lerneinheiten und können ihren Fortschritt im Zeitverlauf über ein Dashboard mit Fach-Verteilung, Fortschrittsbalken und Verlauf verfolgen.';
$string['modulenameplural']     = 'LeitnerFlow';
$string['movedtobox']         = 'Karte nach Fach {$a} verschoben';
$string['newsession']         = 'Neue Lerneinheit';
$string['nextaftercheck']     = 'Nächste Frage nach "Prüfen"';
$string['nextquestion']       = 'Nächste Frage';
$string['nextquestionbtn']           = 'Nächste Frage';
$string['nocards']             = 'Aktuell keine Karten in Fach {$a}.';
$string['nocardsinpool']      = 'In der ausgewählten Kategorie wurden keine Fragen gefunden. Bitte fügen Sie zunächst Fragen zur Fragensammlung hinzu.';
$string['nocardsinthisbox']   = 'Aktuell keine Karten in Fach {$a}.';
$string['nocategory']      = 'Keine Fragenkategorie konfiguriert. Bitte passen Sie die Aktivitätseinstellungen an.';
$string['nosessionactive']    = 'Keine aktive Lerneinheit';
$string['nosessions']         = 'Bisher keine abgeschlossenen Lerneinheiten.';
$string['nostudents']    = 'Bisher haben keine Teilnehmenden diese Aktivität gestartet.';
$string['nounlearnedcards']   = 'Alle Karten sind bereits gelernt! Sie können Ihren Fortschritt zurücksetzen, um von vorn zu beginnen.';
$string['open']               = 'Offen';
$string['pluginadministration'] = 'LeitnerFlow-Administration';
$string['pluginname']           = 'LeitnerFlow';
$string['points']                    = '{$a} Punkte';
$string['practiceboxn']       = 'Fach {$a} üben';
$string['prioritystrategy']          = 'Kartenauswahl';
$string['prioritystrategy_help']     = '<b>Niedrigere Fächer bevorzugen</b>: Fragen aus Fach 1 (am wenigsten beherrscht) werden zuerst gezeigt — gut für gezieltes Wiederholen.<br><b>Gemischt zufällig</b>: Fragen aus allen Fächern werden zufällig gemischt — gut für Abwechslung.';
$string['prioritystrategy_mixed']    = 'Gemischt zufällig';
$string['prioritystrategy_prio']     = 'Niedrigere Fächer zuerst';
$string['privacy:metadata']                                    = 'Das LeitnerFlow-Plugin speichert pro Person den Kartenstatus und Daten zu den Lerneinheiten.';
$string['privacy:metadata:eledialeitnerflow_card_state']             = 'Verfolgt den Fortschritt jeder Person pro Frage (aktuelles Fach, Anzahl richtiger Antworten, Status).';
$string['privacy:metadata:eledialeitnerflow_card_state:attemptcount'] = 'Gesamtzahl der Versuche.';
$string['privacy:metadata:eledialeitnerflow_card_state:correctcount'] = 'Wie oft die Person richtig geantwortet hat.';
$string['privacy:metadata:eledialeitnerflow_card_state:currentbox']  = 'Das aktuelle Leitner-Fach der Karte.';
$string['privacy:metadata:eledialeitnerflow_card_state:questionid']  = 'Die ID der Frage.';
$string['privacy:metadata:eledialeitnerflow_card_state:status']      = 'Kartenstatus: offen, gelernt oder fehlerhaft.';
$string['privacy:metadata:eledialeitnerflow_card_state:userid']      = 'Die Nutzer-ID.';
$string['privacy:metadata:eledialeitnerflow_sessions']               = 'Erfasst jede abgeschlossene Lerneinheit.';
$string['privacy:metadata:eledialeitnerflow_sessions:questionsasked'] = 'Anzahl der Fragen in der Lerneinheit.';
$string['privacy:metadata:eledialeitnerflow_sessions:questionscorrect'] = 'Anzahl richtiger Antworten in der Lerneinheit.';
$string['privacy:metadata:eledialeitnerflow_sessions:timecompleted'] = 'Zeitpunkt des Abschlusses der Lerneinheit.';
$string['privacy:metadata:eledialeitnerflow_sessions:timecreated']   = 'Zeitpunkt des Starts der Lerneinheit.';
$string['privacy:metadata:eledialeitnerflow_sessions:userid']        = 'Die Nutzer-ID.';
$string['progressreset'] = 'Fortschritt wurde zurückgesetzt.';
$string['questioncategory']          = 'Fragenkategorien';
$string['questioncategory_help']     = 'Wählen Sie eine oder mehrere Kategorien aus der Fragensammlung, aus denen die Karten gezogen werden. Alle Fragen in den ausgewählten Kategorien (ohne Unterkategorien) werden zu Karteikarten. Kategorien legen Sie in der Fragensammlung des Kurses an.';
$string['questionrotation']          = 'Fragenrotation';
$string['questionrotation_dynamic']  = 'Dynamisch';
$string['questionrotation_fixed']    = 'Fester Pool';
$string['questionrotation_help']     = '<b>Dynamisch</b>: Fragen werden immer frisch aus der Fragensammlung geholt — neue Fragen erscheinen automatisch, gelöschte verschwinden.<br><b>Fest</b>: Der Fragenpool wird beim ersten Start festgeschrieben. Spätere Änderungen in der Fragensammlung wirken sich nicht aus.';
$string['questionsasked']            = 'Gestellte Fragen';
$string['questionsinpool']    = 'Fragen im Pool';
$string['report']        = 'Übersicht aller Teilnehmenden';
$string['resetandrestart']    = 'Zurücksetzen und neu starten';
$string['resetconfirm']  = 'Soll der gesamte Fortschritt für {$a} wirklich zurückgesetzt werden? Das kann nicht rückgängig gemacht werden.';
$string['resetprogress'] = 'Fortschritt zurücksetzen';
$string['sessioncancelled']   = 'Lerneinheit abgebrochen.';
$string['sessioncomplete']    = 'Lerneinheit abgeschlossen!';
$string['sessioncorrectof']   = '{$a->correct} / {$a->total}';
$string['sessionduration']    = 'Dauer';
$string['sessionhistory']     = 'Meine Lerneinheiten';
$string['sessioninprogress']  = 'Lerneinheit läuft';
$string['sessionpercent']     = '{$a} %';
$string['sessionresult']      = 'Sie haben {$a->correct} von {$a->total} Fragen richtig beantwortet.';
$string['sessionsettings']           = 'Lerneinheit';
$string['sessionsize']               = 'Fragen pro Lerneinheit';
$string['sessionsize_help']          = 'Maximale Anzahl an Fragen in einer Lerneinheit. Teilnehmende können jederzeit vorher beenden. Empfohlen: 5–20 für kurze, fokussierte Einheiten.';
$string['showanimation']             = 'Karten-Animation';
$string['showanimation_help']        = 'Wenn aktiviert, wird nach jeder Antwort eine kurze Animation angezeigt, die das Ziel-Fach markiert. Wenn deaktiviert, erscheint die nächste Frage sofort.';
$string['showtour']                  = 'Einführungstour anzeigen';
$string['showtour_help']             = 'Wenn aktiviert, sehen Teilnehmende beim ersten Aufruf eine geführte Tour durch die LeitnerFlow-Oberfläche. Die Tour wird über das Nutzertouren-System von Moodle verwaltet und pro Nutzer einmalig auf der gesamten Website angezeigt (nicht pro Kurs). Deaktivieren Sie diese Option, wenn Sie die Aktivität selbst einführen möchten.';
$string['startsession']       = 'Lerneinheit starten';
$string['streakbroken']              = 'Streak beendet';
$string['streakcounter']             = '{$a}× Streak!';
$string['totalsessions']      = '{$a} Lerneinheiten abgeschlossen';
$string['trend_recent']       = 'Letzte 3 Lerneinheiten: {$a->recent} % richtig (Durchschnitt: {$a->avg} %)';
$string['viewreport']         = 'Übersicht aller Teilnehmenden';
$string['witherrors']         = 'Mit Fehlern';
$string['wrongbehavior']             = 'Bei falscher Antwort';
$string['wrongbehavior_back1']       = 'Ein Fach zurück';
$string['wrongbehavior_help']        = 'Was passiert, wenn eine Frage falsch beantwortet wird:<br><b>Zurück auf Fach 1</b> — am strengsten, die Karte beginnt von vorn.<br><b>Ein Fach zurück</b> — mittel, die Karte fällt ein Fach.<br><b>Keine Änderung</b> — nachsichtig, die Karte bleibt im aktuellen Fach, der Zähler richtiger Antworten erhöht sich aber nicht.';
$string['wrongbehavior_nochange']    = 'Keine Änderung';
$string['wrongbehavior_reset']       = 'Zurück auf Fach 1';
$string['yourprogress']       = 'Mein Lernfortschritt';
