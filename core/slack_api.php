<?php

# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

require_api('mention_api.php');
require_api('relationship_api.php');

require_once('template_api.php');


$g_cache_slack_user = array();
$g_cache_compiled_templates = array();


function slack_get_compiled_template($template)
{
    global $g_cache_compiled_templates;
    if (isset($g_cache_compiled_templates[$template])) {
        return $g_cache_compiled_templates[$template];
    }
    $compiled_template = template_compile($template);
    $g_cache_compiled_templates[$template] = $compiled_template;
    return $compiled_template;
}

function slack_get_webhook()
{
    return plugin_config_get('url_webhook');
}

function slack_collect_receivers_all()
{
    global $g_cache_slack_user;
    $table = plugin_table('user_config');
    $query = "SELECT * FROM $table WHERE slack_user <> ''";
    $result = db_query($query);
    $receivers = array();
    while ($row = db_fetch_array($result)) {
        $user_id = $row['user_id'];
        $g_cache_slack_user[$user_id] = $row;
        $receivers[$user_id] = $row['slack_user'];
    }
    return $receivers;
}

function slack_config_get($user_id)
{
    global $g_cache_slack_user;
    if (isset($g_cache_slack_user[$user_id])) {
        return $g_cache_slack_user[$user_id];
    }
    $table = plugin_table('user_config');
    $query = "SELECT * FROM $table WHERE user_id = " . db_param();
    $result = db_query($query, array($user_id));
    while ($row = db_fetch_array($result)) {
        $g_cache_slack_user[$user_id] = $row;
        return $row;
    }
    return null;
}

function slack_config_set($user_id, $config)
{
    $table = plugin_table('user_config');
    $query = "SELECT COUNT(id) FROM $table WHERE user_id = " . db_param();
    $result = db_query($query, array($user_id));
    $count = db_result($result);

    $params = array(
        $config['slack_user'],
        $config['on_bug_report'],
        $config['on_bug_update'],
        $config['on_bug_deleted'],
        $config['on_bugnote_add'],
        $config['on_bugnote_edit'],
        $config['on_bugnote_deleted'],
        $config['skip_private'],
        $config['skip_bulk'],
        $config['notify_bugnote_contributed'],
        $config['bug_format'],
        $config['bugnote_format'],
        $user_id,
    );
    if ($count > 0) {
        $query = "UPDATE $table SET
		    slack_user = " . db_param() . "
		    , on_bug_report = " . db_param() . "
		    , on_bug_update = " . db_param() . "
		    , on_bug_deleted = " . db_param() . "
		    , on_bugnote_add = " . db_param() . "
		    , on_bugnote_edit = " . db_param() . "
		    , on_bugnote_deleted = " . db_param() . "
		    , skip_private = " . db_param() . "
		    , skip_bulk = " . db_param() . "
		    , notify_bugnote_contributed = " . db_param() . "
		    , bug_format = " . db_param() . "
		    , bugnote_format = " . db_param() . "
		    WHERE user_id = " . db_param();
    } else {
        $query = "INSERT INTO $table
		    ( slack_user
		    , on_bug_report
		    , on_bug_update
		    , on_bug_deleted
		    , on_bugnote_add
		    , on_bugnote_edit
		    , on_bugnote_deleted
		    , skip_private
		    , skip_bulk
		    , notify_bugnote_contributed
		    , bug_format
		    , bugnote_format
		    , user_id
	    )
        VALUES
		    ( " . db_param() . "
		    , " . db_param() . "
		    , " . db_param() . "
		    , " . db_param() . "
		    , " . db_param() . "
		    , " . db_param() . "
		    , " . db_param() . "
		    , " . db_param() . "
		    , " . db_param() . "
		    , " . db_param() . "
		    , " . db_param() . "
		    , " . db_param() . "
		    , " . db_param() . "
	    )";
    }
    db_query($query, $params);
}

function slack_config_get_field($user_id, $field)
{
    $user_config = slack_config_get($user_id);
    if ($user_config) {
        return $user_config[$field];
    }
    if ($field == 'slack_user') {
        return null;
    }
    return plugin_config_get($field);
}

function slack_config_get_user($user_id)
{
    return slack_config_get_field($user_id, 'slack_user');
}

function slack_config_get_bug_format($user_id)
{
    return slack_config_get_field($user_id, 'bug_format');
}

function slack_config_get_bugnote_format($user_id)
{
    return slack_config_get_field($user_id, 'bugnote_format');
}

function slack_event_string($event)
{
    switch ($event) {
        case 'on_bug_report': return lang_get('email_notification_title_for_action_bug_submitted');
        case 'on_bug_update': return lang_get('email_notification_title_for_action_bug_updated');
        case 'on_bug_deleted': return lang_get('email_notification_title_for_action_bug_deleted');
        case 'on_bugnote_add': return lang_get('email_notification_title_for_action_bugnote_submitted');
        case 'on_bugnote_edit': return plugin_lang_get('bugnote_updated');
        case 'on_bugnote_deleted': return plugin_lang_get('bugnote_deleted');
    }
    return "Unknown bugnote event: $event";
}

function slack_bbcode_to_text($bbtext)
{
    $bbtags = array(
        '[b]' => '*','[/b]' => '* ',
        '[i]' => '_','[/i]' => '_ ',
        '[u]' => '_','[/u]' => '_ ',
        '[s]' => '~','[/s]' => '~ ',
        '[sup]' => '','[/sup]' => '',
        '[sub]' => '','[/sub]' => '',

        '[list]' => '','[/list]' => "\n",
        '[*]' => '• ',

        '[hr]' => "\n———\n",

        '[left]' => '','[/left]' => '',
        '[right]' => '','[/right]' => '',
        '[center]' => '','[/center]' => '',
        '[justify]' => '','[/justify]' => '',
    );

    $bbtext = str_ireplace(array_keys($bbtags), array_values($bbtags), $bbtext);

    $bbextended = array(
        "/\[code(.*?)\](.*?)\[\/code\]/is" => "```$2```",
        "/\[color(.*?)\](.*?)\[\/color\]/is" => "$2",
        "/\[size=(.*?)\](.*?)\[\/size\]/is" => "$2",
        "/\[highlight(.*?)\](.*?)\[\/highlight\]/is" => "$2",
        "/\[url](.*?)\[\/url]/i" => "<$1>",
        "/\[url=(.*?)\](.*?)\[\/url\]/i" => "<$1|$2>",
        "/\[email=(.*?)\](.*?)\[\/email\]/i" => "<mailto:$1|$2>",
        "/\[img\]([^[]*)\[\/img\]/i" => "<$1>",
    );

    foreach ($bbextended as $match => $replacement) {
        $bbtext = preg_replace($match, $replacement, $bbtext);
    }

    $bbtext = preg_replace_callback(
        "/\[quote(=)?(.*?)\](.*?)\[\/quote\]/is",
        function ($matches) {
            if (!empty($matches[2])) {
                $result = "\n> _*" . $matches[2] . "* wrote:_\n> \n";
            }
            $lines = explode("\n", $matches[3]);
            foreach ($lines as $line) {
                $result .= "> " . $line . "\n";
            }
            return $result;
        },
        $bbtext
    );
    return $bbtext;
}

function slack_format_text($text)
{
    return strip_tags(
        str_replace(
            array('&', '<', '>'),
            array('&amp;', '&lt;', '&gt;'),
            slack_bbcode_to_text($text)
        )
    );
}

function slack_is_field_short($column)
{
    $id = custom_field_get_id_from_name(str_replace('custom_', '', $column));
    if ($id) {
        $field = custom_field_get_definition($id);
        return $field['type'] != CUSTOM_FIELD_TYPE_TEXTAREA;
    }
    return !column_is_extended($column);
}

function slack_bug_data($user_id, $bug)
{
    # load (push) user language here as bug_data assumes current language
    lang_push(user_pref_get_language($user_id, $bug->project_id));

    # Override current user with user to construct bug data for.
    # This is to make sure that APIs that check against current user (e.g. relationship) work correctly.
    $current_user_id = current_user_set($user_id);

    $user_access_level = user_get_access_level($user_id, $bug->project_id);
    $bug_view_fields = config_get('bug_view_page_fields', null, $user_id, $bug->project_id);

    $date_format = config_get('normal_date_format');

    $tags = array();
    if (in_array('tags', $bug_view_fields) && access_compare_level($user_access_level, config_get('tag_view_threshold'))) {
        $tag_rows = tag_bug_get_attached($bug->id);
        foreach ($tag_rows as $tag) {
            $tags[] = $tag['name'];
        }
    }

    $is_different_projects = false;
    $relationships = array();
    $relationship_all = relationship_get_all($bug->id, $is_different_projects);
    foreach ($relationship_all as $relationship) {
        if ($bug->id == $relationship->src_bug_id) {
            # root bug is in the source side, related bug in the destination side
            $related_project_id = $relationship->dest_project_id;
            $related_bug_id = $relationship->dest_bug_id;
            $relationship_descr = relationship_get_description_src_side($relationship->type);
        } else {
            # root bug is in the dest side, related bug in the source side
            $related_project_id = $relationship->src_project_id;
            $related_bug_id = $relationship->src_bug_id;
            $relationship_descr = relationship_get_description_dest_side($relationship->type);
        }
        # related bug not existing...
        if (!bug_exists($related_bug_id)) {
            continue;
        }
        # user can access to the related bug at least as a viewer
        if (!access_has_bug_level(config_get('view_bug_threshold', null, null, $related_project_id), $related_bug_id)) {
            continue;
        }
        $relationships[] = array(
            'relationship' => $relationship_descr,
            'id' => bug_format_id($related_bug_id),
            'summary' => bug_get_field($related_bug_id, 'summary')
        );
    }

    $history = array();
    if (ON == config_get('history_default_visible') && access_compare_level($user_access_level, config_get('view_history_threshold'))) {
        $history_raw_events = history_get_raw_events_array($bug->id, $user_id);
        foreach ($history_raw_events as $t_raw_history_item) {
            $t_localized_item = history_localize_item(
                $t_raw_history_item['bug_id'],
                $t_raw_history_item['field'],
                $t_raw_history_item['type'],
                $t_raw_history_item['old_value'],
                $t_raw_history_item['new_value'],
                false
            );
            $history[] = array(
                'date' => date($date_format, $t_raw_history_item['date']),
                'username' => $t_raw_history_item['username'],
                'note' => $t_localized_item['note'],
                'change' => $t_localized_item['change'],
            );
        }
    }

    $custom_fields = array();
    $t_custom_fields = custom_field_get_linked_fields($bug->id, $user_access_level);
    foreach ($t_custom_fields as $custom_field_name => $custom_field_data) {
        $custom_fields[] = array(
            'field' => lang_get_defaulted($custom_field_name),
            'value' => string_custom_field_value_for_email($custom_field_data['value'], $custom_field_data['type']),
        );
    }
    // Discover custom fields.
    /*
    $t_related_custom_field_ids = custom_field_get_linked_ids( $bug->project_id );
    foreach ( $t_related_custom_field_ids as $t_id ) {
        $t_def = custom_field_get_definition( $t_id );
        $params['custom_' . $t_def['name']] = custom_field_get_value( $t_id, $bug->id );
    }
    */

    # access_compare_level( $user_access_level, config_get( 'view_handler_threshold' ) )
    # access_compare_level( $user_access_level, config_get( 'due_date_view_threshold' ) )
    # in_array( 'status', $bug_view_fields )
    # in_array( 'severity', $bug_view_fields )
    # in_array( 'priority', $bug_view_fields )
    # in_array( 'reproducibility', $bug_view_fields )
    # in_array( 'resolution', $bug_view_fields )
    # in_array( 'target_version', $bug_view_fields ) && access_compare_level( $user_access_level, config_get( 'roadmap_view_threshold' ) )
    # in_array( 'additional_info', $bug_view_fields )
    # in_array( 'steps_to_reproduce', $bug_view_fields )
    $bug_data = array(
        'url' => array(
            'field' => 'URL',
            'value' => string_get_bug_view_url_with_fqdn($bug->id),
        ),
        'id' => array(
            'field' => lang_get('issue_id'),
            'value' => bug_format_id($bug->id),
        ),
        'reporter' => array(
            'field' => lang_get('reporter'),
            'value' => user_get_name($bug->reporter_id),
        ),
        'handler' => array(
            'field' => lang_get('email_handler'),
            'value' => empty($bug->handler_id) ? plugin_lang_get('no_user') : user_get_name($bug->handler_id),
        ),
        'project' => array(
            'field' => lang_get('email_project'),
            'value' => project_get_name($bug->project_id),
        ),
        'category' => array(
            'field' => lang_get('category'),
            'value' => category_full_name($bug->category_id, false),
        ),
        'reproducibility' => array(
            'field' => lang_get('reproducibility'),
            'value' => get_enum_element('reproducibility', $bug->reproducibility),
        ),
        'severity' => array(
            'field' => lang_get('severity'),
            'value' => get_enum_element('severity', $bug->severity),
        ),
        'priority' => array(
            'field' => lang_get('priority'),
            'value' => get_enum_element('priority', $bug->priority),
        ),
        'status' => array(
            'field' => lang_get('status'),
            'value' => get_enum_element('status', $bug->status),
        ),
        'resolution' => array(
            'field' => lang_get('resolution'),
            'value' => get_enum_element('resolution', $bug->resolution),
        ),
        'fixed_in_version' => array(
            'field' => lang_get('fixed_in_version'),
            'value' => $bug->fixed_in_version,
        ),
        'target_version' => array(
            'field' => lang_get('target_version'),
            'value' => $bug->target_version,
        ),
        'date_submitted' => array(
            'field' => lang_get('date_submitted'),
            'value' => date($date_format, $bug->date_submitted),
        ),
        'last_update' => array(
            'field' => lang_get('last_update'),
            'value' => date($date_format, $bug->last_updated),
        ),
        'due_date' => array(
            'field' => lang_get('due_date'),
            'value' => date_is_null($bug->due_date) ? '' : date($date_format, $bug->due_date),
        ),
        'summary' => array(
            'field' => lang_get('summary'),
            'value' => slack_format_text($bug->summary),
        ),
        'description' => array(
            'field' => lang_get('description'),
            'value' => slack_format_text($bug->description),
        ),
        'additional_information' => array(
            'field' => lang_get('additional_information'),
            'value' => slack_format_text($bug->additional_information),
        ),
        'steps_to_reproduce' => array(
            'field' => lang_get('steps_to_reproduce'),
            'value' => slack_format_text($bug->steps_to_reproduce),
        ),
        'tag' => array(
            'field' => lang_get('tags'),
            'values' => $tags,
        ),
        'relationships' => array(
            'fields' => array(
                'relationship' => lang_get('bug_relationships'),
                'id' => lang_get('issue_id'),
                'summary' => lang_get('summary'),
            ),
            'values' => $relationships,
        ),
        'history' => array(
            'title' => lang_get('bug_history'),
            'fields' => array(
                'date' => lang_get('date_modified'),
                'username' => lang_get('username'),
                'note' => lang_get('field'),
                'change' => lang_get('change'),
            ),
            'values' => $history,
        ),
        'projection' => array(
            'field' => lang_get('projection'),
            'value' => get_enum_element('projection', $bug->projection),
        ),
        'eta' => array(
            'field' => lang_get('eta'),
            'value' => get_enum_element('eta', $bug->eta),
        ),
        'version' => array(
            'field' => lang_get('version'),
            'value' => $bug->version,
        ),
        'build' => array(
            'field' => lang_get('build'),
            'value' => $bug->build,
        ),
        'duplicate_id' => array(
            'field' => lang_get('duplicate_id'),
            'value' => bug_format_id($bug->duplicate_id),
        ),
        'view_state' => array(
            'field' => lang_get('view_status'),
            'value' => $bug->view_state == VS_PRIVATE ? lang_get('private') : lang_get('public'),
        ),
        'os' => array(
            'field' => lang_get('os'),
            'value' => $bug->os,
        ),
        'platform' => array(
            'field' => lang_get('platform'),
            'value' => $bug->platform,
        ),
        'os_build' => array(
            'field' => lang_get('os_build'),
            'value' => $bug->os_build,
        ),
        'custom_fields' => $custom_fields,
    );

    current_user_set($current_user_id);

    lang_pop();

    return $bug_data;
}

function slack_bugnote_data($user_id, $bugnote, $files = array())
{
    $project_id = bug_get_field($bugnote->bug_id, 'project_id');

    # load (push) user language here as bug_data assumes current language
    lang_push(user_pref_get_language($user_id, $project_id));

    # Override current user with user to construct bug data for.
    # This is to make sure that APIs that check against current user (e.g. relationship) work correctly.
    $current_user_id = current_user_set($user_id);

    $view_attachments_threshold = config_get('view_attachments_threshold');
    $size_unit = lang_get('bytes');
    $attached = array();
    if (count($files) > 0 && access_has_bug_level($view_attachments_threshold, $bugnote->bug_id, $user_id)) {
        foreach ($files as $file) {
            $name = $file['name'];
            $size = $file['size'];
            $attached[] = array(
                'name' => $name,
                'size' => $size,
                'size_unit' => $size_unit,
            );
        }
    }

    # $time_tracking_access_threshold = config_get( 'time_tracking_view_threshold' );
    # access_has_bug_level( $time_tracking_access_threshold, $bugnote->bug_id, $user_id );
    $bugnote_data = array(
        'id' => array(
            'field' => lang_get('id'),
            'value' => bugnote_format_id($bugnote->id),
        ),
        'reporter' => array(
            'field' => lang_get('note_user_id'),
            'value' => user_get_name($bugnote->reporter_id),
        ),
        'access_level' => array(
            'field' => lang_get('access_level_project'),
            'value' => user_exists($bugnote->reporter_id)
                ? access_level_get_string(access_get_project_level($project_id, $bugnote->reporter_id))
                : '',
        ),
        'last_modified' => array(
            'field' => lang_get('email_last_modified'),
            'value' => date(config_get('normal_date_format'), $bugnote->last_modified),
        ),
        'url' => array(
            'field' => 'URL',
            'value' => string_process_bugnote_link(config_get('bugnote_link_tag') . $bugnote->id, false, false, true),
        ),
        'view_state' => array(
            'field' => lang_get('bugnote_view_state'),
            'value' => $bugnote->view_state == VS_PRIVATE
                ? lang_get('private')
                : lang_get('public'),
        ),
        'time_tracking' => array(
            'field' => lang_get('time_tracking'),
            'value' => $bugnote->time_tracking > 0
                ? db_minutes_to_hhmm($bugnote->time_tracking)
                : '',
        ),
        'note' => array(
            'field' => lang_get('bugnote'),
            'value' => slack_format_text($bugnote->note),
        ),
        'files' => array(
            'field' => lang_get('bugnote_attached_files'),
            'values' => $attached,
        ),
    );

    current_user_set($current_user_id);

    lang_pop();

    return $bugnote_data;
}

function slack_post($url, $payload)
{
    $url = trim($url);
    if (empty($url)) {
        return array('ok' => false, 'error' => 'empty url');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    $response = curl_exec($ch);
    if ($response) {
        $result = json_decode($response, true);
    } else {
        $result = array('ok' => false, 'error' => curl_errno($ch) . ': ' . curl_error($ch));
    }
    curl_close($ch);
    return $result;
}

function slack_notify($slack_user, $text, $url = null)
{
    if (!$slack_user) {
        return array('ok' => false, 'error' => 'no slack user id');
    }
    $payload = array(
        'user' => $slack_user,
        'text' => $text,
    );
    return slack_post($url ? $url : slack_get_webhook(), $payload);
}

function slack_collect_receivers($bug, $bugnote = null)
{
    $receivers = array();

    # add Reporter
    $receivers[$bug->reporter_id] = true;

    # add Handler
    if ($bug->handler_id > 0) {
        $receivers[$bug->handler_id] = true;
    }

    # add users monitoring the bug
    db_param_push();
    $query = 'SELECT DISTINCT user_id FROM {bug_monitor} WHERE bug_id=' . db_param();
    $result = db_query($query, array( $bug->id ));
    while ($row = db_fetch_array($result)) {
        $user_id = $row['user_id'];
        $receivers[$user_id] = true;
    }

    # add Category Owner
    if ($bug->category_id > 0) {
        $category_assigned_to = category_get_field($bug->category_id, 'user_id');
        if ($category_assigned_to > 0) {
            $receivers[$category_assigned_to] = true;
        }
    }

    # add users who contributed bugnotes
    db_param_push();
    $query = 'SELECT DISTINCT reporter_id FROM {bugnote} WHERE bug_id=' . db_param();
    $result = db_query($query, array( $bug->id ));
    while ($row = db_fetch_array($result)) {
        $user_id = $row['reporter_id'];
        if (slack_config_get_field($user_id, 'notify_bugnote_contributed')) {
            $receivers[$user_id] = true;
        }
    }

    # add users who mentioned in bug
    $mentioned = array();
    $mentioned = array_merge($mentioned, mention_get_users($bug->summary));
    $mentioned = array_merge($mentioned, mention_get_users($bug->description));
    $mentioned = array_merge($mentioned, mention_get_users($bug->steps_to_reproduce));
    $mentioned = array_merge($mentioned, mention_get_users($bug->additional_information));
    $bug_mentioned = access_has_bug_level_filter(config_get('view_bug_threshold'), $bug->id, $mentioned);
    foreach ($bug_mentioned as $user_id) {
        $receivers[$user_id] = true;
    }

    # add users who mentioned in bugnote
    if ($bugnote) {
        $mentioned = mention_get_users($bugnote->note);
        $bugnote_mentioned = access_has_bugnote_level_filter(config_get('view_bug_threshold'), $bugnote->id, $mentioned);
        foreach ($bugnote_mentioned as $user_id) {
            $receivers[$user_id] = true;
        }
    }

    $final_receivers = array();
    foreach ($receivers as $user_id => $_) {
        $slack_user = slack_config_get_user($user_id);
        if (!$slack_user || is_blank($slack_user)) {
            continue;
        }
        $final_receivers[$user_id] = $slack_user;
    }
    return $final_receivers;
}

function slack_check_skip($user_id, $event, $is_bulk, $bug, $bugnote = null)
{
    # Possibly eliminate the current user
    if ((auth_get_current_user_id() == $user_id)) {
        return true;
    }

    # Eliminate users who don't exist anymore or who are disabled
    if (!user_exists($user_id) || !user_is_enabled($user_id)) {
        return true;
    }

    # exclude users who don't have at least viewer access to the bug,
    # or who can't see bugnotes if the last update included a bugnote
    $view_bug_threshold = config_get('view_bug_threshold', null, $user_id, $bug->project_id);
    if (!access_has_bug_level($view_bug_threshold, $bug->id, $user_id)) {
        return true;
    }
    if ($bugnote && !access_has_bugnote_level($view_bug_threshold, $bugnote->id, $user_id)) {
        return true;
    }

    if ($is_bulk && slack_config_get_field($user_id, 'skip_bulk')) {
        return true;
    }

    $skip_private = slack_config_get_field($user_id, 'skip_private');
    if ($bug->view_state == VS_PRIVATE && $skip_private) {
        return true;
    }
    if ($bugnote && $bugnote->view_state == VS_PRIVATE && $skip_private) {
        return true;
    }

    if (!slack_config_get_field($user_id, $event)) {
        return true;
    }

    return false;
}

function slack_bug_action($action, $bug)
{
    switch ($action) {
        case "COPY":
            slack_bug_event('on_bug_report', $bug, true);
            break;
        case "DELETE":
            slack_bug_event('on_bug_deleted', $bug, true);
            break;
        case "EXT_ADD_NOTE":
            $bugnote_id = bugnote_get_latest_id($bug->id);
            $bugnote = bugnote_get($bugnote_id);
            slack_bugnote_event('on_bugnote_add', $bug, $bugnote, true);
            break;
        case "ASSIGN":
        case "CLOSE":
        case "RESOLVE":
        case "SET_STICKY":
        case "UP_PRIOR":
        case "EXT_UPDATE_SEVERITY":
        case "UP_STATUS":
        case "UP_CATEGORY":
        case "VIEW_STATUS":
        case "EXT_ATTACH_TAGS":
        case "UP_PRODUCT_VERSION":
        case "UP_FIXED_IN_VERSION":
        case "UP_TARGET_VERSION":
        default:
            slack_bug_event('on_bug_update', $bug, true);
            break;
    }
}

function slack_bug_event($event, $bug, $is_bulk = false)
{
    if ($event == 'on_bug_report') {
        $receivers = slack_collect_receivers_all();
    } else {
        $receivers = slack_collect_receivers($bug);
    }
    foreach ($receivers as $user_id => $slack_user) {
        if (slack_check_skip($user_id, $event, $is_bulk, $bug)) {
            continue;
        }
        $bug_data = slack_bug_data($user_id, $bug);
        $bug_data['event'] = slack_event_string($event);
        $bug_format = slack_config_get_bug_format($user_id);
        $compiled_template = slack_get_compiled_template($bug_format);
        $text = template_render($compiled_template, $bug_data);
        slack_notify($slack_user, $text);
    }
}

function slack_bugnote_event($event, $bug, $bugnote, $is_bulk = false, $files = array())
{
    $receivers = slack_collect_receivers($bug, $bugnote);
    foreach ($receivers as $user_id => $slack_user) {
        if (slack_check_skip($user_id, $event, $is_bulk, $bug, $bugnote)) {
            continue;
        }
        $bug_data = slack_bug_data($user_id, $bug);
        $bug_data['event'] = slack_event_string($event);
        $bug_data['bugnote'] = slack_bugnote_data($user_id, $bugnote, $files);
        $bugnote_format = slack_config_get_bugnote_format($user_id);
        $compiled_template = slack_get_compiled_template($bugnote_format);
        $text = template_render($compiled_template, $bug_data);
        slack_notify($slack_user, $text);
    }
}
