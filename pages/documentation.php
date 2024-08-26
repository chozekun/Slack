<?php

$global = gpc_get_bool('global', false);
$title = plugin_lang_get('documentation');
$current_menu_page = plugin_page('config_page');
$css = plugin_file('documentation.css');
$link = "<link href=\"$css\" type=\"text/css\" rel=\"stylesheet\" >";
if ($global) {
    $current_sidebar_page = 'manage_overview_page.php';
    access_ensure_global_level(config_get('manage_plugin_threshold'));
    layout_page_header($title);
    echo $link;
    layout_page_begin($current_sidebar_page);
    print_manage_menu($current_menu_page);
} else {
    layout_page_header($title);
    echo $link;
    layout_page_begin();
    print_account_menu($current_menu_page);
}

$tpl = file_get_contents(dirname(__FILE__) . '/documentation.tpl');

$vars = array(
    "variable" => "Hello World!",
    "week" => array(
        'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'
    ),
    "user" => array(
        array( 'name' => 'Jupiter', 'color' => 'yellow'),
        array( 'name' => 'Mars', 'color' => 'red' ),
        array( 'name' => 'Earth', 'color' => 'blue' ),
    ),
    "empty_array" => array(),
);
$compiled = template_compile($tpl);
echo template_render($compiled, $vars);

layout_page_end();
