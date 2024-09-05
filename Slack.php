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


class SlackPlugin extends MantisPlugin
{
    public function register()
    {
        $this->name = plugin_lang_get('title');
        $this->description = plugin_lang_get('description');
        $this->version = '2.0.0';
        $this->requires = array(
            'MantisCore' => '2.0.0',
        );
        $this->author = 'Youngje Kim';
        $this->contact = 'chozekun@gmail.com';
        $this->url = 'https://chozekun.github.io';
    }

    public function install()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            plugin_error('ERROR_PHP_VERSION');
            return false;
        }
        if (!extension_loaded('curl')) {
            plugin_error('ERROR_NO_CURL');
            return false;
        }
        return true;
    }

    public function config()
    {
        $bug_format = file_get_contents(dirname(__FILE__) . '/bug.tpl');
        $bugnote_format = file_get_contents(dirname(__FILE__) . '/bugnote.tpl');
        return array(
            'url_webhook' => '',
            'on_bug_report' => true,
            'on_bug_update' => true,
            'on_bug_deleted' => true,
            'on_bugnote_add' => true,
            'on_bugnote_edit' => true,
            'on_bugnote_deleted' => true,
            'skip_private' => true,
            'skip_bulk' => true,
            'notify_bugnote_contributed' => true,
            'bug_format' => $bug_format,
            'bugnote_format' => $bugnote_format,
        );
    }

    public function hooks()
    {
        return array(
            'EVENT_MENU_ACCOUNT' => 'menu_account',
            'EVENT_MENU_MANAGE' => 'menu_manage',
            'EVENT_REPORT_BUG' => 'bug_report',
            'EVENT_UPDATE_BUG' => 'bug_update',
            'EVENT_BUG_DELETED' => 'bug_deleted',
            'EVENT_BUG_ACTION' => 'bug_action',
            'EVENT_BUGNOTE_ADD' => 'bugnote_add',
            'EVENT_BUGNOTE_EDIT' => 'bugnote_edit',
            'EVENT_BUGNOTE_DELETED' => 'bugnote_deleted',
            'EVENT_BUGNOTE_ADD_FORM' => 'bugnote_add_form',
        );
    }

    public function schema()
    {
        return array(
            array( "CreateTableSQL", array( plugin_table("user_config"), "
                id                          I      NOTNULL UNSIGNED AUTOINCREMENT PRIMARY,
                user_id                     I      NOTNULL UNSIGNED,
                slack_user                  C(16)  NOTNULL,
                on_bug_report               L      NOTNULL DEFAULT 1,
                on_bug_update               L      NOTNULL DEFAULT 1,
                on_bug_deleted              L      NOTNULL DEFAULT 1,
                on_bugnote_add              L      NOTNULL DEFAULT 1,
                on_bugnote_edit             L      NOTNULL DEFAULT 1,
                on_bugnote_deleted          L      NOTNULL DEFAULT 1,
                skip_private                L      NOTNULL DEFAULT 1,
                skip_bulk                   L      NOTNULL DEFAULT 1,
                notify_bugnote_contributed  L      NOTNULL DEFAULT 1,
                bug_format                  XL     NOTNULL,
                bugnote_format              XL     NOTNULL
            ")),
        );
    }

    public function init()
    {
        $core_path = dirname(__FILE__) . '/core/';
        require_once($core_path . 'slack_api.php');
    }

    public function menu_account()
    {
        $page = plugin_page("config_page");
        $label = plugin_lang_get("user_config");
        $anchor = "<a href=\"$page\">$label</a>";
        return array($anchor);
    }

    public function menu_manage()
    {
        $page = plugin_page("config_page") . '&global=true';
        $label = plugin_lang_get("global_config");
        $anchor = "<a href=\"$page\">$label</a>";
        return array($anchor);
    }

    public function event_to_config($event)
    {
        $configs = array(
            'EVENT_REPORT_BUG' => 'on_bug_report',
            'EVENT_UPDATE_BUG' => 'on_bug_update',
            'EVENT_BUG_DELETED' => 'on_bug_deleted',
            'EVENT_BUGNOTE_ADD' => 'on_bugnote_add',
            'EVENT_BUGNOTE_EDIT' => 'on_bugnote_edit',
            'EVENT_BUGNOTE_DELETED' => 'on_bugnote_deleted',
        );
        if (array_key_exists($event, $configs)) {
            return $configs[$event];
        } else {
            return null;
        }
    }

    public function bugnote_add_form($event, $bug_id)
    {
        echo '<tr>';
        echo '<th class="category">' . plugin_lang_get('skip') . '</th>';
        echo '<td colspan="5">';
        echo '<label>';
        echo '<input ', helper_get_tab_index(), ' name="slack_skip" class="ace" type="checkbox" />';
        echo '<span class="lbl"></span>';
        echo '</label>';
        echo '</td></tr>';
    }

    public function skip()
    {
        return gpc_get_bool('slack_skip');
    }

    public function bug_action($event, $action, $bug_id)
    {
        if ($this->skip()) {
            return;
        }
        // INFO: Because the event handler is called after the bug is deleted
        if ($action == "DELETE") {
            return;
        }
        $bug = bug_get($bug_id);
        slack_bug_action($action, $bug);
    }

    public function bug_report($event, $bug, $bug_id)
    {
        if ($this->skip()) {
            return;
        }
        slack_bug_event($this->event_to_config($event), $bug);
    }

    public function bug_update($event, $bug_existing, $bug_updated)
    {
        if ($this->skip()) {
            return;
        }
        slack_bug_event($this->event_to_config($event), $bug_updated);
    }

    public function bug_deleted($event, $bug_id)
    {
        if ($this->skip()) {
            return;
        }
        $bug = bug_get($bug_id);
        slack_bug_event($this->event_to_config($event), $bug);
    }

    public function bugnote_add($event, $bug_id, $bugnote_id, $files)
    {
        if ($this->skip()) {
            return;
        }
        $bug = bug_get($bug_id);
        $bugnote = bugnote_get($bugnote_id);
        slack_bugnote_event($this->event_to_config($event), $bug, $bugnote, false, $files);
    }

    public function bugnote_edit($event, $bug_id, $bugnote_id)
    {
        if ($this->skip()) {
            return;
        }
        $bug = bug_get($bug_id);
        $bugnote = bugnote_get($bugnote_id);
        # INFO: Because it contains the previous note
        $bugnote->note = bugnote_get_text($bugnote_id);
        slack_bugnote_event($this->event_to_config($event), $bug, $bugnote);
    }

    public function bugnote_deleted($event, $bug_id, $bugnote_id)
    {
        if ($this->skip()) {
            return;
        }
        $bug = bug_get($bug_id);
        $bugnote = bugnote_get($bugnote_id);
        slack_bugnote_event($this->event_to_config($event), $bug, $bugnote);
    }

}
