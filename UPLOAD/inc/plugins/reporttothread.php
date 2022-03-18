<?php

if (!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

if (defined('IN_ADMINCP'))
{
    $plugins->add_hook('admin_config_report_reasons_start', 'reporttothread_load_lang');
    $plugins->add_hook("admin_config_settings_begin", 'reporttothread_load_lang');
    $plugins->add_hook('admin_settings_print_peekers', 'reporttothread_settings_peeker');
}
else
{
    $plugins->add_hook('report_do_report_end', 'reporttothread_run');
    $plugins->add_hook('class_moderation_delete_thread', 'reporttothread_cache');
    $plugins->add_hook('report_start', 'reporttothread_load_lang');
    $plugins->add_hook('modcp_start', 'reporttothread_load_lang');
}

if (isset($mybb->settings['reporttothread_autoclose']) && $mybb->settings['reporttothread_autoclose'] == 1)
{
    $plugins->add_hook('class_moderation_delete_post', 'reporttothread_deleted_post');
    $plugins->add_hook('class_moderation_delete_thread_start', 'reporttothread_deleted_thread');
    $plugins->add_hook('private_delete_end', 'reporttothread_deleted_pm');
    $plugins->add_hook('private_do_stuff', 'reporttothread_mass_deleted_pm');
    $plugins->add_hook('datahandler_user_delete_start', 'reporttothread_deleted_user');
    $plugins->add_hook('reputation_delete_end', 'reporttothread_deleted_reputation');
}

function reporttothread_info()
{
    global $db, $lang, $plugins_cache;
    $lang->load('reporttothread', true);
    $info = array(
        "name"          => $db->escape_string($lang->reporttothread),
        "description"   => $db->escape_string($lang->reporttothread_desc),
        "website"       => "https://github.com/SvePu/MyBB-Report-To-Thread",
        "author"        => "SvePu",
        "authorsite"    => "https://github.com/SvePu",
        "version"       => "1.5",
        "codename"      => "reporttothread",
        "compatibility" => "18*"
    );

    if (is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active']['reporttothread'])
    {
        $query = $db->simple_select('settinggroups', 'gid', "name = 'reporttothread'", array('limit' => 1));
        $settings_group = (int)$db->fetch_field($query, 'gid');
        if ($settings_group)
        {
            $info['description'] =  $info['description'] . "<br /><span style=\"line-height: 2.5em;display: inline-block;font-weight: 600;font-style: italic;\"><a href=\"index.php?module=config-settings&amp;action=change&amp;gid=" . $settings_group . "\"><img style=\"vertical-align: sub;\" src=\"./styles/default/images/icons/custom.png\" title=\"" . $db->escape_string($lang->setting_group_reporttothread) . "\" alt=\"settings_icon\" width=\"16\" height=\"16\" />&nbsp;" . $db->escape_string($lang->setting_group_reporttothread) . "</a></span>";
        }
    }

    return $info;
}

function reporttothread_install()
{
    global $db, $lang;
    $lang->load('reporttothread', true);

    $query = $db->simple_select('settinggroups', 'MAX(disporder) AS disporder');
    $disporder = (int)$db->fetch_field($query, 'disporder');

    $setting_group = array(
        'name' => 'reporttothread',
        "title" => $db->escape_string($lang->setting_group_reporttothread),
        "description" => $db->escape_string($lang->setting_group_reporttothread_desc),
        'isdefault' => 0
    );

    $setting_group['disporder'] = ++$disporder;

    $gid = (int)$db->insert_query('settinggroups', $setting_group);

    $settings = array(
        'enable' => array(
            'optionscode' => 'yesno',
            'value' => 1
        ),
        'type' => array(
            'optionscode' => 'checkbox \n1=' . $db->escape_string($lang->setting_reporttothread_type_1) . '\n2=' . $db->escape_string($lang->setting_reporttothread_type_2) . '\n3=' . $db->escape_string($lang->setting_reporttothread_type_3),
            'value' => '1,2,3'
        ),
        'type_post_cutoff' => array(
            'optionscode' => 'numeric \n min=0',
            'value' => '1000',
        ),
        'fid' => array(
            'optionscode' => 'forumselectsingle',
            'value' => ''
        ),
        'autoclose' => array(
            'optionscode' => 'yesno',
            'value' => 0
        ),
        'autoclose_uid' => array(
            'optionscode' => 'numeric \n min=1',
            'value' => 0
        ),
        'autoclose_subject' => array(
            'optionscode' => 'text',
            'value' => ''
        ),
        'autoclose_message' => array(
            'optionscode' => 'textarea',
            'value' => $db->escape_string($lang->setting_reporttothread_autoclose_message_value)
        ),
        'modcp' => array(
            'optionscode' => 'yesno',
            'value' => 1
        )
    );

    $disporder = 0;

    foreach ($settings as $name => $setting)
    {
        $name = "reporttothread_{$name}";

        $setting['name'] = $db->escape_string($name);

        $lang_var_title = "setting_{$name}";
        $lang_var_description = "setting_{$name}_desc";

        $setting['title'] = $db->escape_string($lang->{$lang_var_title});
        $setting['description'] = $db->escape_string($lang->{$lang_var_description});
        $setting['disporder'] = $disporder;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
        ++$disporder;
    }

    $is_reportpm = reporttothread_checkfor_reportpm();
    if (!$is_reportpm)
    {
        rebuild_settings();
    }
}

function reporttothread_is_installed()
{
    global $mybb;
    if (isset($mybb->settings['reporttothread_enable']))
    {
        return true;
    }
    return false;
}

function reporttothread_activate()
{
    global $db;
    $db->update_query("settings", array('value' => 1), "name = 'reporttothread_enable'");

    $is_reportpm = reporttothread_checkfor_reportpm();
    if (!$is_reportpm)
    {
        rebuild_settings();
    }
}

function reporttothread_deactivate()
{
    global $db;
    $db->update_query("settings", array('value' => 0), "name = 'reporttothread_enable'");
    rebuild_settings();
}

function reporttothread_uninstall()
{
    global $db, $mybb;

    if ($mybb->request_method != 'post')
    {
        global $page, $lang;
        $lang->load('reporttothread', true);
        $page->output_confirm_action('index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=reporttothread', $lang->reporttothread_uninstall_message, $lang->reporttothread_uninstall);
    }

    $db->delete_query("settinggroups", "name='reporttothread'");
    $db->delete_query("settings", "name LIKE 'reporttothread_%'");

    rebuild_settings();

    if (!isset($mybb->input['no']))
    {
        global $cache;
        $cache->delete('reporttothread');
    }
}

function reporttothread_load_lang()
{
    global $lang;
    $lang->load('reporttothread', true);
}

function reporttothread_settings_peeker(&$peekers)
{
    $peekers[] .= 'new Peeker($(".setting_reporttothread_enable"), $("#row_setting_reporttothread_type, #row_setting_reporttothread_type_post_cutoff, #row_setting_reporttothread_fid, #row_setting_reporttothread_autoclose, #row_setting_reporttothread_autoclose_uid, #row_setting_reporttothread_autoclose_subject, #row_setting_reporttothread_autoclose_message, #row_setting_reporttothread_modcp"), 1, true)';
    $peekers[] .= 'new Peeker($("#setting_reporttothread_type_1"), $("#row_setting_reporttothread_type_post_cutoff"), 1, true)';
    $peekers[] .= 'new Peeker($(".setting_reporttothread_autoclose"), $("#row_setting_reporttothread_autoclose_uid, #row_setting_reporttothread_autoclose_subject, #row_setting_reporttothread_autoclose_message"), 1, true)';
}

function reporttothread_run()
{
    global $db, $mybb, $report_type, $lang, $cache, $session;

    if ($mybb->settings['reporttothread_enable'] != 1 || empty($mybb->settings['reporttothread_type']))
    {
        return;
    }

    if (empty($mybb->settings['reporttothread_fid']) || $mybb->settings['reporttothread_fid'] == "-1" || $mybb->settings['reporttothread_fid'] < 1)
    {
        return;
    }

    $forum_cache = $cache->read("forums");
    if ($mybb->settings['reporttothread_fid'] >= 1 && $forum_cache[$mybb->settings['reporttothread_fid']]['type'] == "c")
    {
        return;
    }

    $lang->load('report');
    $lang->load('reporttothread');

    $reporttothread_type = explode(',', $mybb->settings['reporttothread_type'], 4);

    $rid = $mybb->get_input('reason', MyBB::INPUT_INT);
    $query = $db->simple_select("reportreasons", "title,extra", "rid = '{$rid}'");
    $reasons = $db->fetch_array($query);

    $reason = htmlspecialchars_uni($lang->parse($reasons['title']));

    $comment = "";
    if ($reasons['extra'])
    {
        $comment = $mybb->get_input('comment');
        if (!empty($comment))
        {
            $comment = $lang->sprintf(htmlspecialchars_uni($lang->reporttothread_comment), $mybb->user['username'], trim($comment)) . "\n";
        }
    }

    switch ($report_type)
    {
        case 'post':
            if (!in_array(1, $reporttothread_type))
            {
                return;
            }
            $post = get_post($mybb->get_input('pid', MyBB::INPUT_INT));
            $reported_id = $post['pid'];
            $rtype = htmlspecialchars_uni($lang->reporttothread_type_post);

            $thread = get_thread($post['tid']);
            $forum = get_forum($thread['fid']);

            $pd_thread_link = "[url=" . $mybb->settings['bburl'] . "/" . get_thread_link($thread['tid']) . "]" . $thread['subject'] . "[/url]";
            $pd_forum_link = "[url=" . $mybb->settings['bburl'] . "/" . get_forum_link($thread['fid']) . "]" . $forum['name'] . "[/url]";
            if ($mybb->settings['reporttothread_type_post_cutoff'] > 0 && my_strlen($post['message']) > $mybb->settings['reporttothread_type_post_cutoff'])
            {
                $post['message'] = my_substr($post['message'], 0, $mybb->settings['reporttothread_type_post_cutoff'] - 3) . "...";
            }
            $pd_reported_post = "[quote=\"" . $post['username'] . "\" pid=\"" . $post['pid'] . "\" dateline=\"" . $post['dateline'] . "\"]" . $post['message'] . "[/quote]";
            $message = $lang->sprintf(htmlspecialchars_uni($lang->reporttothread_message_post), $mybb->user['username'], $rtype, $pd_thread_link, $pd_forum_link, $reason, $comment, $pd_reported_post);
            break;
        case 'profile':
            if (!in_array(2, $reporttothread_type))
            {
                return;
            }
            $user = get_user($mybb->get_input('pid', MyBB::INPUT_INT));
            $reported_id = $user['uid'];
            $rtype = htmlspecialchars_uni($lang->reporttothread_type_profile);
            $userlink = "[url=" . $mybb->settings['bburl'] . "/member.php?action=profile&uid=" . $user['uid'] . "]" . $user['username'] . "[/url]";
            $message = $lang->sprintf(htmlspecialchars_uni($lang->reporttothread_message_profile), $mybb->user['username'], $rtype, $userlink, $reason, $comment);
            break;
        case 'reputation':
            if (!in_array(3, $reporttothread_type))
            {
                return;
            }
            $query1 = $db->simple_select("reputation", "*", "rid = '" . $mybb->get_input('pid', MyBB::INPUT_INT) . "'");
            $reputation = $db->fetch_array($query1);
            $reported_id = $reputation['rid'];
            $rtype = htmlspecialchars_uni($lang->reporttothread_type_reputation);
            $getuser = get_user($reputation['uid']);
            $getuserlink = "[url=" . $mybb->settings['bburl'] . "/member.php?action=profile&uid=" . $getuser['uid'] . "]" . $getuser['username'] . "[/url]";
            $adduser = get_user($reputation['adduid']);
            $adduserlink = "[url=" . $mybb->settings['bburl'] . "/member.php?action=profile&uid=" . $adduser['uid'] . "]" . $adduser['username'] . "[/url]";
            if ($reputation['pid'] > 0)
            {
                $reppost = get_post($reputation['pid']);
                $reppostlink = $getuser['username'] . "'s " . htmlspecialchars_uni($lang->reporttothread_type_post) . ": [url=" . $mybb->settings['bburl'] . "/" . get_post_link($reputation['pid']) . "#pid" . $reputation['pid'] . "]" . $reppost['subject'] . "[/url]";
            }
            else
            {
                $reppostlink = htmlspecialchars_uni($lang->reporttothread_type_profile);
            }
            $reputation_comment = '';
            if (!empty($reputation['comments']))
            {
                $reputation_comment = "\n[quote=\"" . $adduser['username'] . "\" dateline=\"" . $reputation['dateline'] . "\"]" . htmlspecialchars_uni($reputation['comments']) . "[/quote]";
            }
            $message = $lang->sprintf(htmlspecialchars_uni($lang->reporttothread_message_reputation), $mybb->user['username'], $rtype, $getuserlink, $reason, $comment, $reputation['reputation'], $adduserlink, $reppostlink, $reputation_comment);
            break;
        case 'privatemessage':
            if (!in_array(4, $reporttothread_type))
            {
                return;
            }
            $lang->load('private');
            $query = $db->simple_select("privatemessages", "*", "pmid = '" . $mybb->get_input('pid', MyBB::INPUT_INT) . "'");
            $pm = $db->fetch_array($query);
            $reported_id = $pm['pmid'];
            $rtype = htmlspecialchars_uni($lang->reporttothread_type_privatemessage);
            $getuser = get_user($pm['uid']);
            $getuserlink = "[url=" . $mybb->settings['bburl'] . "/member.php?action=profile&uid=" . $getuser['uid'] . "]" . $getuser['username'] . "[/url]";
            $senduser = get_user($pm['fromid']);
            $senduserlink = "[url=" . $mybb->settings['bburl'] . "/member.php?action=profile&uid=" . $senduser['uid'] . "]" . $senduser['username'] . "[/url]";
            $pm_content = "\n[quote=\"" . $senduser['username'] . "\" dateline=\"" . $pm['dateline'] . "\"][b]" . htmlspecialchars_uni($lang->export_subject) . ":[/b] " . $pm['subject'] . "\n[b]" . htmlspecialchars_uni($lang->export_message) . ":[/b]\n" . $pm['message'] . "[/quote]";
            $message = $lang->sprintf(htmlspecialchars_uni($lang->reporttothread_message_privatemessage), $mybb->user['username'], $rtype, $senduserlink, $reason, $comment, $pm_content);
            break;
    }

    $subject = $lang->sprintf(htmlspecialchars_uni($lang->reporttothread_subject), $rtype, $mybb->user['username']);

    $find_tid = reporttothread_search_tid($reported_id, $report_type);
    if ($find_tid)
    {
        $thread_info = reporttothread_build_post($find_tid, false, $subject, $message);
    }
    else
    {
        $thread_info = reporttothread_build_thread($subject, $message, $reported_id, $report_type);
    }

    if ($thread_info && $mybb->settings['reporttothread_modcp'] != 0)
    {
        $db->update_query("reportedcontent", array('reportstatus' => 1), "id = '{$reported_id}'");
        $cache->update_reportedcontent();
    }
}

function reporttothread_build_thread(string $subject, string $message, int $reported_id, string $report_type)
{
    global $mybb, $session;
    require_once MYBB_ROOT . "inc/datahandlers/post.php";
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

    $new_thread['options'] = array(
        'signature' => 0,
        'disablesmilies' => 0
    );

    $posthandler->set_data($new_thread);
    $valid_thread = $posthandler->validate_thread();

    if ($valid_thread)
    {
        $thread_info = $posthandler->insert_thread();
        if ($thread_info)
        {
            $tid = $thread_info['tid'];
            reporttothread_cache($tid, $reported_id, $report_type);
            return $thread_info;
        }
        return false;
    }
}

function reporttothread_build_post(int $tid, bool $closeit = false, string $subject = '', string $message = '')
{
    global $mybb, $session;
    require_once MYBB_ROOT . "inc/datahandlers/post.php";
    $posthandler = new PostDataHandler("insert");

    $uid = (int)$mybb->user['uid'];
    $username = $mybb->user['username'];

    if ($closeit)
    {
        if ($mybb->settings['reporttothread_autoclose'] != 1)
        {
            return;
        }

        $uid = (int)$mybb->settings['reporttothread_autoclose_uid'];
        if ($uid == 0)
        {
            $uid = (int)$mybb->user['uid'];
        }

        $user = get_user($uid);
        $username = $user['username'];

        $subject = '';
        if (!empty($mybb->settings['reporttothread_autoclose_subject']))
        {
            $subject = $mybb->settings['reporttothread_autoclose_subject'];
        }

        $message = $mybb->settings['reporttothread_autoclose_message'];
        if (empty($mybb->settings['reporttothread_autoclose_message']))
        {
            global $lang;
            $lang->load('reporttothread', true);
            $message = $lang->setting_reporttothread_autoclose_message_value;
        }
    }

    $new_post = array(
        "tid" => (int)$tid,
        "replyto" => 0,
        "fid" => (int)$mybb->settings['reporttothread_fid'],
        "subject" => $subject,
        "icon" => 0,
        "uid" => $uid,
        "username" => $username,
        "message" => $message,
        "dateline" => TIME_NOW,
        "ipaddress" => $session->packedip,
        "posthash" => md5((int)$mybb->user['uid'] . random_str())
    );

    $new_post['options'] = array(
        'signature' => 0,
        'disablesmilies' => 0
    );

    $posthandler->set_data($new_post);
    $valid_post = $posthandler->validate_post();

    if ($valid_post)
    {
        $thread_info = $posthandler->insert_post();
        if ($thread_info)
        {
            if ($closeit)
            {
                global $db;
                $db->update_query('threads', array('closed' => 1), "tid='{$tid}'");
                reporttothread_cache($tid);
            }
            return $thread_info;
        }
        return false;
    }
}

function reporttothread_cache(int $tid, string $reported_id = "", string $report_type = "")
{
    global $cache;
    $reportedthread = array();
    $reportedthread = $cache->read('reporttothread');
    if (!empty($reported_id) && !empty($report_type))
    {
        $reportedthread['reports'][$tid] = array('id' => $reported_id, 'type' => $report_type);
    }
    else
    {
        if (array_key_exists($tid, $reportedthread['reports']))
        {
            unset($reportedthread['reports'][$tid]);
            if (empty($reportedthread['reports']))
            {
                unset($reportedthread['reports']);
            }
        }
    }
    $cache->update('reporttothread', $reportedthread);
}

function reporttothread_search_tid(int $reported_id, string $report_type)
{
    global $cache;
    $reportedthread = array();
    $reportedthread = $cache->read('reporttothread');
    if ($reportedthread['reports'])
    {
        foreach ($reportedthread['reports'] as $tid => $val)
        {
            if ($val['id'] == $reported_id && $val['type'] == $report_type)
            {
                return $tid;
            }
        }
        return false;
    }
    return false;
}

function reporttothread_deleted_post(int $pid)
{
    $tid = false;
    if ($pid)
    {
        $tid = reporttothread_search_tid($pid, 'post');
    }

    if ($tid)
    {
        reporttothread_build_post($tid, true);
    }
}

function reporttothread_deleted_thread(int $threadid)
{
    $tid = false;
    if ($threadid)
    {
        $thread = get_thread($threadid);
        if (!$thread)
        {
            return;
        }
        $pid = (int)$thread['firstpost'];
        $tid = reporttothread_search_tid($pid, 'post');
    }

    if ($tid)
    {
        reporttothread_build_post($tid, true);
    }
}

function reporttothread_deleted_pm()
{
    global $mybb, $db;

    $tid = $fulldeleted = false;
    $pmid = $mybb->get_input('pmid', MyBB::INPUT_INT);

    if ($pmid)
    {
        $query = $db->simple_select("privatemessages", "*", "pmid='{$pmid}'");
        if ($db->num_rows($query) < 1)
        {
            $fulldeleted = true;
        }
    }

    if ($fulldeleted)
    {
        $tid = reporttothread_search_tid($pmid, 'privatemessage');
    }

    if ($tid)
    {
        reporttothread_build_post($tid, true);
    }
}

function reporttothread_mass_deleted_pm()
{
    global $mybb, $db;

    if (!empty($mybb->input['delete']))
    {
        $mybb->input['check'] = $mybb->get_input('check', MyBB::INPUT_ARRAY);
        if (!empty($mybb->input['check']))
        {
            $pmssql = '';
            foreach ($mybb->input['check'] as $key => $val)
            {
                if ($pmssql)
                {
                    $pmssql .= ",";
                }
                $pmssql .= "'" . (int)$key . "'";
            }

            $deletepms = array();
            $query = $db->simple_select("privatemessages", "pmid, folder", "pmid IN ($pmssql) AND folder='4'", array('order_by' => 'pmid'));
            while ($delpm = $db->fetch_array($query))
            {
                $deletepms[$delpm['pmid']] = 1;
            }

            foreach ($mybb->input['check'] as $key => $val)
            {
                $fulldeleted = false;
                $key = (int)$key;
                if (!empty($deletepms[$key]))
                {
                    $fulldeleted = true;
                }

                $tid = false;
                if ($fulldeleted)
                {
                    $tid = reporttothread_search_tid($key, 'privatemessage');
                }

                if ($tid)
                {
                    reporttothread_build_post($tid, true);
                }
            }
        }
    }
}

function reporttothread_deleted_user($datahandler)
{
    foreach ($datahandler->delete_uids as $key => $uid)
    {
        $tid = false;
        $tid = reporttothread_search_tid($uid, 'profile');

        if ($tid)
        {
            reporttothread_build_post($tid, true);
        }
    }
}

function reporttothread_deleted_reputation()
{
    global $mybb;

    $rid = $mybb->get_input('rid', MyBB::INPUT_INT);

    $tid = false;
    if ($rid)
    {
        $tid = reporttothread_search_tid($rid, 'reputation');
    }

    if ($tid)
    {
        reporttothread_build_post($tid, true);
    }
}

function reporttothread_checkfor_reportpm()
{
    global $db, $plugins_cache, $lang;

    if (is_array($plugins_cache) && is_array($plugins_cache['active']) && $plugins_cache['active']['reportpm'])
    {
        $setting_update = array(
            'optionscode' => 'checkbox \n1=' . $db->escape_string($lang->setting_reporttothread_type_1) . '\n2=' . $db->escape_string($lang->setting_reporttothread_type_2) . '\n3=' . $db->escape_string($lang->setting_reporttothread_type_3) . '\n4=' . $db->escape_string($lang->setting_reporttothread_type_4),
            'value' => '1,2,3,4'
        );
        $db->update_query("settings", $setting_update, "name = 'reporttothread_type'");

        rebuild_settings();

        return true;
    }
    return false;
}
