<?php

access_ensure_global_level(config_get('manage_plugin_threshold'));

$field = gpc_get_string('field');

echo plugin_get()->config()[$field];
