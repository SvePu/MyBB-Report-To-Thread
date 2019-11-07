<?php
/*
* Report to Thread - 1.0 Language File English
* Licensed under GNU/GPL v3
*/

$l['reporttothread_type_post'] = "post";
$l['reporttothread_type_profile'] = "profile";
$l['reporttothread_type_reputation'] = "reputation";

$l['reporttothread_subject'] = "Reported {1} by {2}";
$l['reporttothread_comment'] = "User {1} has commented: {2}";

$l['reporttothread_message_post'] = "[b]{1} has reported a {2}.[/b]

Original Thread: {3}
Original Forum: {4}

[b][u]Report Reason:[/u][/b] {5}
{6}
[b][u]Reported Post:[/u][/b]

{7}";
$l['reporttothread_message_profile'] = "[b]{1} has reported a {2} of user: {3}[/b]

[b][u]Report Reason:[/u][/b] {4}
{5}";
$l['reporttothread_message_reputation'] = "[b]{1} has reported a {2} for {3}.[/b]

[b][u]Report Reason:[/u][/b] {4}
{5}
Reputation ({6}) was given by {7} for {8}
{9}";

/*** Custom Report Reason Support ***/
/* 
* For language support of your custom report reason 
* set the title for new reason at ACP like: <lang:report_reason_myreason>
* and define your reason here like:
*/
// $l['report_reason_myreason'] = "My Report Reason";
?>
