<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook('report_do_report_end', 'reporttothread_run');
$plugins->add_hook('class_moderation_delete_thread', 'reporttothread_delete_thread_from_cache');

function reporttothread_info()
{
    global $db, $lang;
    $lang->load('config_reporttothread');
    return array(
        "name"          =>  $db->escape_string($lang->reporttothread),
        "description"   =>  $db->escape_string($lang->reporttothread_desc),
        "website"       => "https://github.com/SvePu/MyBB_Report-To-Thread",
        "author"        => "SvePu",
        "authorsite"    => "https://github.com/SvePu",
        "version"       => "1.1",
        "codename"      => "reporttothread",
        "compatibility" => "18*"
    );
}

function reporttothread_install()
{
    global $db,$lang;
     $lang->load('config_reporttothread');

    $query_add = $db->simple_select("settinggroups", "COUNT(*) as counts");
    $rows = $db->fetch_field($query_add, "counts");

    $setting_group = array(
        'name' => 'reporttothread_setting',
        "title" => $db->escape_string($lang->reporttothread_settings_title),
        "description" => $db->escape_string($lang->reporttothread_settings_title_desc),
        'disporder' => $rows+1,
        'isdefault' => 0
    );

    $gid = $db->insert_query("settinggroups", $setting_group);

    $setting_array = array(
        'reporttothread_enable' => array(
            'title' => $db->escape_string($lang->reporttothread_enable_title),
            'description' => $db->escape_string($lang->reporttothread_enable_title_desc),
            'optionscode' => 'yesno',
            'value' => 1,
            'disporder' => 1
            ),
        'reporttothread_fid' => array(
            'title' => $db->escape_string($lang->reporttothread_fid_title),
            'description' => $db->escape_string($lang->reporttothread_fid_title_desc),
            'optionscode' => 'forumselectsingle',
            'value' => '',
            'disporder' => 2
            ),
        'reporttothread_modcp' => array(
            'title' => $db->escape_string($lang->reporttothread_modcp_title),
            'description' => $db->escape_string($lang->reporttothread_modcp_title_desc),
            'optionscode' => 'yesno',
            'value' => 1,
            'disporder' => 3
            )
        );

    foreach($setting_array as $name => $setting)
    {
        $setting['name'] = $name;
        $setting['gid'] = $gid;
        $db->insert_query('settings', $setting);
    }
    rebuild_settings();
}

function reporttothread_is_installed()
{
    global $mybb;
    if(isset($mybb->settings['reporttothread_enable']))
    {
        return true;
    }
    return false;

}

function reportthread_activate()
{
    global $db;
    $db->update_query("settings", array('value' => 1), "name = 'reporttothread_enable'");
}

function reportthread_deactivate()
{
    global $db;
    $db->update_query("settings", array('value' => 0), "name = 'reporttothread_enable'");
}

function reporttothread_uninstall()
{
    global $db, $cache;
    $query = $db->simple_select("settinggroups", "gid", "name='reporttothread_setting'");
    $gid = $db->fetch_field($query, "gid");
    if(!$gid)
    {
        return;
    }
    $db->delete_query("settinggroups", "name='reporttothread_setting'");
    $db->delete_query("settings", "gid=$gid");
    rebuild_settings();

    $cache->delete('reporttothread');
}

function reporttothread_run()
{
    global $db, $mybb, $report_type, $lang, $cache, $session;

    if($mybb->settings['reporttothread_enable'] == 1 && $report_type == 'post')
    {
        if(empty($mybb->settings['reporttothread_fid']) || $mybb->settings['reporttothread_fid'] == "-1")
        {
            return;
        }

        $forum_cache = $cache->read("forums");
        if($mybb->settings['reporttothread_fid'] >= 1 && $forum_cache[$mybb->settings['reporttothread_fid']]['type'] == "c")
        {
            return;
        }

        $lang->load('report');
        $lang->load('reporttothread');

        $post = get_post($mybb->get_input('pid', MyBB::INPUT_INT));
        $reported_pid = $post['pid'];

        $thread = get_thread($post['tid']);
        $forum = get_forum($thread['fid']);

        $rid = $mybb->get_input('reason', MyBB::INPUT_INT);
        switch ($rid) {
            case 1:
                $reason = $lang->report_reason_other;
                break;
            case 2:
                $reason = $lang->report_reason_rules;
                break;
            case 3:
                $reason = $lang->report_reason_bad;
                break;
            case 4:
                $reason = $lang->report_reason_spam;
                break;
            case 5:
                $reason = $lang->report_reason_wrong;
                break;
        }

        $comment = "";
        if($rid == 1)
        {
            if(!empty($mybb->get_input('comment')))
            {
                $comment = $db->escape_string($mybb->get_input('comment'));
                $comment = "[quote=\"" . $mybb->user['username'] . "\" dateline=\"" . time() . "\"]" . trim($comment) ."[/quote]\n";
            }
        }

        $pd_thread_link = "[url=" . $mybb->settings['bburl'] . "/" . get_thread_link($thread['tid']) . "]" . $thread['subject'] . "[/url]";
        $pd_forum_link = "[url=" . $mybb->settings['bburl'] . "/" . get_forum_link($thread['fid']) . "]" . $forum['name'] . "[/url]";
        $pd_reported_post = "[quote=\"" . $post['username'] . "\" pid=\"" . $post['pid'] . "\" dateline=\"" . $post['dateline'] . "\"]" . $post['message'] . "[/quote]";

        $subject = $lang->sprintf(htmlspecialchars_uni($lang->reporttothread_subject), $mybb->user['username']);
        $message = $lang->sprintf(htmlspecialchars_uni($lang->reporttothread_message), $mybb->user['username'], $pd_thread_link, $pd_forum_link, $reason, $comment, $pd_reported_post);

        $reportedthread = array();
        $reportedthread = $cache->read('reporttothread');
        if(array_key_exists($reported_pid, $reportedthread))
        {
            $thread_info = reporttothread_build_post($reportedthread[$reported_pid], $subject, $message);
        }
        else
        {
            $thread_info = reporttothread_build_thread($subject, $message, $reported_pid);
        }

        if($thread_info && $mybb->settings['reporttothread_modcp'] != 0)
        {
            $db->update_query("reportedcontent", array('reportstatus' => 1), "id = '{$post['pid']}'");
            $cache->update_reportedcontent();
        }
    }
}

function reporttothread_build_thread($subject, $message, $reported_pid)
{
    global $mybb, $session;
    require_once MYBB_ROOT."inc/datahandlers/post.php";
    $posthandler = new PostDataHandler("insert");
    $posthandler->action = "thread";

    $new_thread = array(
        "fid" => (int)$mybb->settings['reporttothread_fid'],
        "prefix" => 0,
        "subject" => $subject,
        "icon" => 0,
        "uid" => (int)$mybb->user['uid'],
        "username" => $mybb->user['username'],
        "message" => $message,
        "ipaddress" => $session->packedip,
        "posthash" => md5((int)$mybb->user['uid'] . random_str()),
    );

    $posthandler->set_data($new_thread);
    $valid_thread = $posthandler->validate_thread();

    if($valid_thread)
    {
        $thread_info = $posthandler->insert_thread();
        if($thread_info)
        {
            $tid = $thread_info['tid'];
            reporttothread_cache($reported_pid, $tid);
        }
        return $thread_info;
    }
}

function reporttothread_build_post($tid, $subject, $message)
{
    global $mybb, $session;
    require_once MYBB_ROOT."inc/datahandlers/post.php";
    $posthandler = new PostDataHandler("insert");

    $new_post = array(
        "tid" => (int)$tid,
        "replyto" => 0,
        "fid" => (int)$mybb->settings['reporttothread_fid'],
        "subject" => $subject,
        "icon" => 0,
        "uid" => (int)$mybb->user['uid'],
        "username" => $mybb->user['username'],
        "message" => $message,
        "dateline" => TIME_NOW,
        "ipaddress" => $session->packedip,
        "posthash" => md5((int)$mybb->user['uid'] . random_str())
    );

    $posthandler->set_data($new_post);
    $valid_post = $posthandler->validate_post();

    if($valid_post)
    {
        $thread_info = $posthandler->insert_post();
        return $thread_info;
    }
}

function reporttothread_cache($pid, $tid)
{
    global $cache;
    $reportedthread = array();
    $reportedthread = $cache->read('reporttothread');
    if($reportedthread)
    {
        $reportedthread += [$pid => $tid];
    }
    else
    {
        $reportedthread = [$pid => $tid];
    }
    $cache->update('reporttothread',$reportedthread);
}

function reporttothread_delete_thread_from_cache($tid)
{
    global $cache;
    $reportedthread = array();
    $reportedthread = $cache->read('reporttothread');
    $reportedthread = array_diff($reportedthread,array($tid));
    $cache->update('reporttothread',$reportedthread);
}
