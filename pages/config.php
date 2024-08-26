<?php
/**
 * Slack Integration
 * Copyright (C) Karim Ratib (karim.ratib@gmail.com)
 *
 * Slack Integration is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License 2
 * as published by the Free Software Foundation.
 *
 * Slack Integration is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Slack Integration; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA
 * or see http://www.gnu.org/licenses/.
 */

/**
 * Sets plugin config option if value is different from current/default
 * @param string $p_name  option name
 * @param string $p_value value to set
 * @return void
 */
function config_set_if_needed($name, $value)
{
    if ($value != plugin_config_get($name)) {
        plugin_config_set($name, $value);
    }
}

form_security_validate('plugin_Slack_config');

$global = gpc_get_bool('global', false);

$notifications = array(
    'on_bug_report',
    'on_bug_update',
    'on_bug_deleted',
    'on_bugnote_add',
    'on_bugnote_edit',
    'on_bugnote_deleted',
    'skip_private',
    'skip_bulk',
    'notify_bugnote_contributed',
);

$strings = array(
    'bug_format',
    'bugnote_format'
);

$config = array();
foreach ($notifications as $notification) {
    $config[$notification] = gpc_get_bool($notification);
}
foreach ($strings as $string) {
    $config[$string] = gpc_get_string($string);
}

$redirect_url = plugin_page('config_page', true);
if ($global) {
    access_ensure_global_level(config_get('manage_plugin_threshold'));
    $config['url_webhook'] = gpc_get_string('url_webhook');
    foreach ($config as $key => $value) {
        config_set_if_needed($key, $value);
    }
    $redirect_url .= '&global=true';
} else {
    $user_id = auth_get_current_user_id();
    $config['slack_user'] = gpc_get_string('slack_user');
    slack_config_set($user_id, $config);
}

form_security_purge('plugin_Slack_config');

layout_page_header(null, $redirect_url);
layout_page_begin();
html_operation_successful($redirect_url);
layout_page_end();
