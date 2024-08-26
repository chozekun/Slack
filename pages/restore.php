<?php

$field = gpc_get_string('field');

echo plugin_get()->config()[$field];
