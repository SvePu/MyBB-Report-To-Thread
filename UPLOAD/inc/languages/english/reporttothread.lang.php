<?php
/*
* Report to Thread - 1.0 Language File English
* Licensed under GNU/GPL v3
*/

/*********** AdminCP *************/
$l['reporttothread'] = "Report to Thread";
$l['reporttothread_desc'] = "Creates a new thread for post reports";
$l['setting_group_reporttothread'] = "Report to Thread settings";
$l['setting_group_reporttothread_desc'] = "Settings for the Report to Thread plugin";

$l['setting_reporttothread_enable'] = "Enable Report to Thread Plugin?";
$l['setting_reporttothread_enable_desc'] = "Select YES to enable the Report to Thread feature!";

$l['setting_reporttothread_fid'] = "Forum for Reports";
$l['setting_reporttothread_fid_desc'] = "Select the forum where the report threads are to be created.";

$l['setting_reporttothread_modcp'] = "Disable ModCP infobar for reports?";
$l['setting_reporttothread_modcp_desc'] = "Select YES to disable the ModCP info bar for the reports where a thread was created for!";

$l['setting_reporttothread_type'] = "Create Threads for Reports of:";
$l['setting_reporttothread_type_desc'] = "Select the types of reports for which a discussion thread is to be created (multiple choice possible)";
$l['setting_reporttothread_type_1'] = "Posts";
$l['setting_reporttothread_type_2'] = "Profiles";
$l['setting_reporttothread_type_3'] = "Reputations";
$l['setting_reporttothread_type_4'] = "Private Messages";

$l['setting_reporttothread_type_post_cutoff'] = "Show cutted Content of reported Posts?";
$l['setting_reporttothread_type_post_cutoff_desc'] = "Set the number of characters after which the content of the reported posts is cut off. (0 disables the option, default: 1000)";

/********** Forum Pages *************/
$l['reporttothread_type_post'] = "post";
$l['reporttothread_type_profile'] = "profile";
$l['reporttothread_type_reputation'] = "reputation";
$l['reporttothread_type_privatemessage'] = "private message";

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

$l['reporttothread_message_privatemessage'] = "[b]{1} has reported a {2} from {3}.[/b]

[b][u]Report Reason:[/u][/b] {4}
{5}
{6}";

/*** Custom Report Reason Support ***/
/* 
* For language support of your custom report reason 
* set the title for new reason at ACP like: <lang:report_reason_myreason>
* and define your reason here like:
*/
// $l['report_reason_myreason'] = "My Report Reason";
?>
