<?php

$user_id = auth_get_current_user_id();
$action = gpc_get_string('action');
$format = gpc_get_string('format');
$type = gpc_get_string('type');
$id = gpc_get_int('id');
$compiled = template_compile($format);
$data = array();
switch ($type) {
    case 'bug':
        if (!bug_exists($id)) {
            echo "There is no bug id: $id";
            return;
        }
        $bug = bug_get($id);
        $data = array('event' => slack_event_string('on_bug_report'));
        $data = array_merge($data, slack_bug_data($user_id, $bug));
        break;
    case 'bugnote':
        if (!bugnote_exists($id)) {
            echo "There is no bug note id: $id";
            return;
        }
        $bugnote = bugnote_get($id);
        $bug = bug_get($bugnote->bug_id);
        $files = array(array('name' => 'sample.file', 'size' => 65536, 'size_unit' => lang_get('bytes')));
        $data = array(
            'event' => slack_event_string('on_bugnote_add'),
            'bugnote' => slack_bugnote_data($user_id, $bugnote, $files),
        );
        $data = array_merge($data, slack_bug_data($user_id, $bug));
        break;
    default:
        echo "Unknown type: $type";
        return;
}

if ($action == "showvars") {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} elseif ($action == "showcodes") {
    echo $compiled;
} elseif ($action == "preview") {
    echo template_render($compiled, $data);
}
