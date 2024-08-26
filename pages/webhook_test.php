<?php

$url = gpc_get_string('url_webhook');
$user_id = auth_get_current_user_id();
$slack_user = slack_config_get_user($user_id);
$result = slack_notify($slack_user, plugin_lang_get('url_webhook_test_text'), $url);
if (!$result['ok']) {
    echo $result['error'];
}
