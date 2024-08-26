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

$global = gpc_get_bool('global', false);

$user_id = auth_get_current_user_id();

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

$current_menu_page = plugin_page(basename(__FILE__, '.php'));
$documentation_page = plugin_page('documentation');
if ($global) {
    $current_sidebar_page = 'manage_overview_page.php';
    access_ensure_global_level(config_get('manage_plugin_threshold'));
    $title = plugin_lang_get('global_config');
    layout_page_header($title);
    layout_page_begin($current_sidebar_page);
    print_manage_menu($current_menu_page);
    $documentation_page .= '&global=true';
} else {
    $title = plugin_lang_get('user_config');
    layout_page_header($title);
    layout_page_begin();
    print_account_menu($current_menu_page);
}

function config_page_get($field)
{
    global $global, $user_id;
    return $global ? plugin_config_get($field) : slack_config_get_field($user_id, $field);
}

function checkbox_attr($field)
{
    return config_page_get($field) ? "checked" : "";
}

function make_checkbox($field)
{
    $is_checked = checkbox_attr($field);
    $label = plugin_lang_get($field);
    return <<<EOT
<div>
  <label>
    <input class="ace" type="checkbox" name="$field" $is_checked />
    <span class="lbl padding-6">$label</span>
  </label>
</div>
EOT;
}

?>

<div class="col-md-12 col-xs-12">
<div class="space-10"></div>
<div class="form-container">
<form action="<?php echo plugin_page('config') ?>" method="post">
<fieldset>
<div class="widget-box widget-color-blue2">
<div class="widget-header widget-header-small">
  <h4 class="widget-title lighter">
    <i class="ace-icon fa fa-exchange"></i>
    <?php echo $title ?>
  </h4>
<?php echo form_security_field('plugin_Slack_config') ?>
<?php if ($global) { ?>
  <input type="hidden" name="global" value="true" />
<?php } ?>
</div>

<div class="widget-body">

<div class="widget-toolbox padding-8 clearfix">
  <div class="form-container">
    <div class="pull-left">
      <a class="btn btn-primary btn-white btn-round btn-sm" href="<?php echo $documentation_page ?>"><?php echo plugin_lang_get('syntax_documentation') ?></a>
    </div>
    <div class="pull-right">
    </div>
  </div>
</div>

<div class="widget-main no-padding">
<div class="table-responsive">
<table class="table table-bordered table-condensed table-striped">

<?php if ($global) { ?>
  <tr>
    <td class="category">
      <?php echo plugin_lang_get('url_webhook') ?><br/>
      <span class="small"><?php echo plugin_lang_get('webhook_description') ?><br/>{"text": "<?php echo plugin_lang_get('type_text') ?>", "user": "<?php echo plugin_lang_get('type_slack_user_id') ?>"}</span>
    </td>
    <td colspan="2">
      <input id="url_webhook" class="ace" size="80" type="text" name="url_webhook" value="<?php echo plugin_config_get('url_webhook')?>" />
      <a id="webhook_test" class="btn btn-primary btn-white btn-round" href="<?php echo plugin_page('webhook_test') ?>"><?php echo plugin_lang_get('url_webhook_test')?></a>
    </td>
  </tr>
<?php } else { ?>
  <tr>
    <td class="category">
      <?php echo plugin_lang_get('user_id') ?>
    </td>
    <td colspan="2">
      <input class="ace" id="slack_user" type="text" name="slack_user" size="32" value="<?php echo config_page_get('slack_user'); ?>" />
    </td>
  </tr>
<?php } ?>

  <tr>
    <td class="category"><?php echo plugin_lang_get('notifications')?></td>
    <td colspan="2">
<?php
foreach ($notifications as $notification) {
    echo make_checkbox($notification);
}
?>
    </td>
  </tr>

  <tr>
    <td class="category">
      <?php echo plugin_lang_get('bug_format')?>
    </td>
    <td>
      <textarea class="form-control" cols="80" name="bug_format" id="bug_format"><?php echo config_page_get('bug_format') ?></textarea>
    </td>
    <td>
      <input id="bug_id" class="ace" size="10" type="number" name="bug_id" value="" placeholder="Bug ID" />
      <a id="showvars_bug_format" class="btn btn-primary btn-white btn-round btn-sm preview" href="<?php echo plugin_page('preview') ?>"><?php echo plugin_lang_get('show_variables') ?></a>
      <a id="showcodes_bug_format" class="btn btn-primary btn-white btn-round btn-sm preview" href="<?php echo plugin_page('preview') ?>"><?php echo plugin_lang_get('show_codes') ?></a>
      <a id="preview_bug_format" class="btn btn-primary btn-white btn-round btn-sm preview" href="<?php echo plugin_page('preview') ?>"><?php echo plugin_lang_get('preview') ?></a>
      <a id="restore_bug_format" class="btn btn-primary btn-white btn-round btn-sm restore" href="<?php echo plugin_page('restore') ?>"><?php echo plugin_lang_get('restore_default') ?></a>
      <div class="space-4"></div>
      <pre id="bug_format_preview"></pre>
    </td>
  </tr>

  <tr>
    <td class="category">
      <?php echo plugin_lang_get('bugnote_format')?>
    </td>
    <td>
      <textarea class="form-control" cols="80" name="bugnote_format" id="bugnote_format"><?php echo config_page_get('bugnote_format') ?></textarea>
    </td>
    <td>
      <input id="bugnote_id" class="ace" size="10" type="number" name="bugnote_id" value="" placeholder="Bug Note ID" />
      <a id="showvars_bugnote_format" class="btn btn-primary btn-white btn-round btn-sm preview" href="<?php echo plugin_page('preview') ?>"><?php echo plugin_lang_get('show_variables') ?></a>
      <a id="showcodes_bugnote_format" class="btn btn-primary btn-white btn-round btn-sm preview" href="<?php echo plugin_page('preview') ?>"><?php echo plugin_lang_get('show_codes') ?></a>
      <a id="preview_bugnote_format" class="btn btn-primary btn-white btn-round btn-sm preview" href="<?php echo plugin_page('preview') ?>"><?php echo plugin_lang_get('preview') ?></a>
      <a id="restore_bugnote_format" class="btn btn-primary btn-white btn-round btn-sm restore" href="<?php echo plugin_page('restore') ?>"><?php echo plugin_lang_get('restore_default') ?></a>
      <div class="space-4"></div>
      <pre id="bugnote_format_preview"></pre>
    </td>
  </tr>

</table>
</div>
</div>
<div class="widget-toolbox padding-8 clearfix">
  <input type="submit" class="btn btn-primary btn-white btn-round" value="<?php echo plugin_lang_get('update') ?>" />
</div>
</div>
</div>
</fieldset>
</form>
</div>
</div>
<script src="<?php echo plugin_file('textarea.js'); ?>"></script>
<script src="<?php echo plugin_file('ajax.js'); ?>"></script>
<?php
layout_page_end();
