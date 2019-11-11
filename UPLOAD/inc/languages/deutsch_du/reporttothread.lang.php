<?php
/*
* Report to Thread - 1.0 Language File German (informal)
* Licensed under GNU/GPL v3
*/

/*********** AdminCP *************/
$l['reporttothread'] = "Report to Thread";
$l['reporttothread_desc'] = "Erstellt einen neuen Thread bei einer Meldung";
$l['setting_group_reporttothread'] = "Report to Thread Einstellungen";
$l['setting_group_reporttothread_desc'] = "Einstellungen des Report to Thread-Plugins";

$l['setting_reporttothread_enable'] = "Report to Thread aktivieren?";
$l['setting_reporttothread_enable_desc'] = "Wähle JA um die Funktion von Report to Thread einzuschalten!";

$l['setting_reporttothread_fid'] = "Forum für die Meldungen";
$l['setting_reporttothread_fid_desc'] = "Wähle das Forum aus, in dem die Meldungens-Threads erstellt werden sollen.";

$l['setting_reporttothread_modcp'] = "ModCP-Infobar für Meldungen deaktivieren?";
$l['setting_reporttothread_modcp_desc'] = "Wähle JA um die ModCP Infobar für die Meldungen auszuschalten, für die ein Thread erstellt wurde!";

$l['setting_reporttothread_type'] = "Threads erstellen bei Meldungen von:";
$l['setting_reporttothread_type_desc'] = "Wähle die Arten der Meldungen aus, wofür ein Diskussionthread erstellt werden soll (Mehrfachauswahl möglich)";
$l['setting_reporttothread_type_1'] = "Beiträgen";
$l['setting_reporttothread_type_2'] = "Profilen";
$l['setting_reporttothread_type_3'] = "Bewertungen";
$l['setting_reporttothread_type_4'] = "Privaten Nachrichten";

$l['setting_reporttothread_type_post_cutoff'] = "Inhalt gemeldeter Beiträge verkürzt darstellen?";
$l['setting_reporttothread_type_post_cutoff_desc'] = "Setze die Anzahl der Zeichen nach der der Inhalt des gemeldeten Beitrags abgeschnitten wird. (0 deaktiviert die Option, Standart: 1000)";

/********** Forum Seiten *************/
$l['reporttothread_type_post'] = "Beitrag";
$l['reporttothread_type_profile'] = "Profil";
$l['reporttothread_type_reputation'] = "Bewertung";
$l['reporttothread_type_privatemessage'] = "Private Nachricht";

$l['reporttothread_subject'] = "{1} gemeldet von {2}";
$l['reporttothread_comment'] = "Nutzer {1} hat dazu kommentiert: {2}";

$l['reporttothread_message_post'] = "[b]{1} hat einen {2} gemeldet.[/b]

Originales Thema: {3}
Originales Forum: {4}

[b][u]Meldungsgrund:[/u][/b] {5}
{6}
[b][u]Gemeldeter Beitrag:[/u][/b]

{7}";
$l['reporttothread_message_profile'] = "[b]{1} hat das {2} von Nutzer: {3} gemeldet.[/b]

[b][u]Meldungsgrund:[/u][/b] {4}
{5}";
$l['reporttothread_message_reputation'] = "[b]{1} hat eine {2} für {3} gemeldet.[/b]

[b][u]Meldungsgrund:[/u][/b] {4}
{5}
Bewertung ({6}) wurde abgegeben durch {7} für {8}
{9}";
$l['reporttothread_message_privatemessage'] = "[b]{1} hat eine {2} von {3} gemeldet.[/b]

[b][u]Meldungsgrund:[/u][/b] {4}
{5}
{6}";

/*** Benutzerdefinierte Meldegrund Unterstützung ***/
/* 
* Für die Sprachünterstützung deines eigenen Meldegrunds 
* setze den Titel des Grunds wie: <lang:report_reason_myreason>
* und definiere den Grund hier wie:
*/
// $l['report_reason_myreason'] = "My Report Reason";
?>
