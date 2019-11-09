<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

if(defined('IN_ADMINCP'))
{
    $plugins->add_hook('admin_config_report_reasons_start', 'reporttothread_acp_load_lang');
}
else
{
    $plugins->add_hook('report_do_report_end', 'reporttothread_run');
    $plugins->add_hook('class_moderation_delete_thread', 'reporttothread_delete_thread_from_cache');
    $plugins->add_hook('report_start', 'reporttothread_load_lang');
}

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
        "version"       => "1.3",
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

function reporttothread_activate()
{
    global $db;
    $db->update_query("settings", array('value' => 1), "name = 'reporttothread_enable'");
    rebuild_settings();
}

function reporttothread_deactivate()
{
    global $db;
    $db->update_query("settings", array('value' => 0), "name = 'reporttothread_enable'");
    rebuild_settings();
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

    if($mybb->settings['reporttothread_enable'] != 1)
    {
        return;
    }

    if(empty($mybb->settings['reporttothread_fid']) || $mybb->settings['reporttothread_fid'] == "-1" || $mybb->settings['reporttothread_fid'] < 1)
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

    $rid = $mybb->get_input('reason', MyBB::INPUT_INT);
    $query = $db->simple_select("reportreasons", "title,extra", "rid = '{$rid}'");
    $reasons = $db->fetch_array($query);

    $reason = htmlspecialchars_uni($lang->parse($reasons['title']));

    $comment = "";
    if($reasons['extra'])
    {
        if(!empty($mybb->get_input('comment')))
        {
            $comment = $lang->sprintf(htmlspecialchars_uni($lang->reporttothread_comment), $mybb->user['username'], trim($mybb->get_input('comment')))."\n";
        }
    }

    switch ($report_type) {
        case 'post':
            $post = get_post($mybb->get_input('pid', MyBB::INPUT_INT));
            $reported_id = $post['pid'];
            $rtype = htmlspecialchars_uni($lang->reporttothread_type_post);

            $thread = get_thread($post['tid']);
            $forum = get_forum($thread['fid']);

            $pd_thread_link = "[url=" . $mybb->settings['bburl'] . "/" . get_thread_link($thread['tid']) . "]" . $thread['subject'] . "[/url]";
            $pd_forum_link = "[url=" . $mybb->settings['bburl'] . "/" . get_forum_link($thread['fid']) . "]" . $forum['name'] . "[/url]";
            if(my_strlen($post['message']) > 1000)
            {
                $post['message'] = my_substr($post['message'], 0, 997)."...";
            }
            $pd_reported_post = "[quote=\"" . $post['username'] . "\" pid=\"" . $post['pid'] . "\" dateline=\"" . $post['dateline'] . "\"]" . $post['message'] . "[/quote]";
            $message = $lang->sprintf(htmlspecialchars_uni($lang->reporttothread_message_post), $mybb->user['username'], $rtype, $pd_thread_link, $pd_forum_link, $reason, $comment, $pd_reported_post);
            break;
        case 'profile':
            $user = get_user($mybb->get_input('pid', MyBB::INPUT_INT));
            $reported_id = $user['uid'];
            $rtype = htmlspecialchars_uni($lang->reporttothread_type_profile);
            $userlink = "[url=" . $mybb->settings['bburl'] . "/member.php?action=profile&uid=" . $user['uid'] . "]" . $user['username'] . "[/url]";
            $message = $lang->sprintf(htmlspecialchars_uni($lang->reporttothread_message_profile), $mybb->user['username'], $rtype, $userlink, $reason, $comment);
            break;
        case 'reputation':
            $query1 = $db->simple_select("reputation", "*", "rid = '".$mybb->get_input('pid', MyBB::INPUT_INT)."'");
            $reputation = $db->fetch_array($query1);
            $reported_id = $reputation['rid'];
            $rtype = htmlspecialchars_uni($lang->reporttothread_type_reputation);
            $getuser = get_user($reputation['uid']);
            $getuserlink = "[url=" . $mybb->settings['bburl'] . "/member.php?action=profile&uid=" . $getuser['uid'] . "]" . $getuser['username'] . "[/url]";
            $adduser = get_user($reputation['adduid']);
            $adduserlink = "[url=" . $mybb->settings['bburl'] . "/member.php?action=profile&uid=" . $adduser['uid'] . "]" . $adduser['username'] . "[/url]";
            if($reputation['pid'] > 0)
            {
                $reppost = get_post($reputation['pid']);
                $reppostlink = $getuser['username'] . "'s " .$lang->reporttothread_type_post . ": [url=" . $mybb->settings['bburl'] . "/" . get_post_link($reputation['pid']) . "#pid" . $reputation['pid'] . "]" . $reppost['subject'] . "[/url]";
            }
            else
            {
                $reppostlink = $lang->reporttothread_type_profile;
            }
            $reputation_comment = '';
            if(!empty($reputation['comments']))
            {
                $reputation_comment = "\n[quote=\"" . $adduser['username'] . "\" dateline=\"" . $reputation['dateline'] . "\"]" . htmlspecialchars_uni($reputation['comments']) . "[/quote]";
            }
            $message = $lang->sprintf(htmlspecialchars_uni($lang->reporttothread_message_reputation), $mybb->user['username'], $rtype, $getuserlink, $reason, $comment, $reputation['reputation'],$adduserlink, $reppostlink, $reputation_comment);
            break;
    }

    $subject = $lang->sprintf(htmlspecialchars_uni($lang->reporttothread_subject), $rtype, $mybb->user['username']);

    $find_tid = reporttothread_search_tid($reported_id, $report_type);
    if($find_tid)
    {
        $thread_info = reporttothread_build_post($find_tid, $subject, $message);
    }
    else
    {
        $thread_info = reporttothread_build_thread($subject, $message, $reported_id, $report_type);
    }

    if($thread_info && $mybb->settings['reporttothread_modcp'] != 0)
    {
        $db->update_query("reportedcontent", array('reportstatus' => 1), "id = '{$reported_id}'");
        $cache->update_reportedcontent();
    }
}

function reporttothread_build_thread($subject, $message, $reported_id, $report_type)
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
            reporttothread_cache($tid, $reported_id, $report_type);
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

function reporttothread_cache($tid, $reported_id, $report_type)
{
    global $cache;
    $reportedthread = array();
    $reportedthread = $cache->read('reporttothread');
    $reportedthread[$tid] = array('id' => $reported_id, 'type' => $report_type);
    $cache->update('reporttothread',$reportedthread);
}

function reporttothread_delete_thread_from_cache($tid)
{
    global $cache;
    $reportedthread = array();
    $reportedthread = $cache->read('reporttothread');
    unset($reportedthread[$tid]);
    $cache->update('reporttothread',$reportedthread);
}

function reporttothread_acp_load_lang()
{
    global $lang;
    $lang->load('config_reporttothread');
}

function reporttothread_load_lang()
{
    global $lang;
    $lang->load('reporttothread');
}

function reporttothread_search_tid($reported_id, $report_type)
{
    global $cache;
    $reportedthread = array();
    $reportedthread = $cache->read('reporttothread');
    if($reportedthread)
    {
        foreach ($reportedthread as $key => $val)
        {
           if ($val['id'] == $reported_id && $val['type'] == $report_type)
           {
               return $key;
           }
        }
        return false;
    }
    else
    {
        return false;
    }
}
