<?php
/*
Plugin Name: Admin Help Content
Plugin URI: http://premium.wpmudev.org/project/admin-help-content
Description: Change the 'help content' that slides down all AJAXy
Author: Andrew Billits, Ulrich Sossou (Incsub), Ve Bailovity (Incsub)
Version: 2.0.1
Author URI: http://premium.wpmudev.org/project/
Textdomain: admin_help_content
WDP ID: 170
*/

/*
Copyright 2007-2011 Incsub (http://incsub.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License (Version 2 - GPLv2) as published by
the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

///////////////////////////////////////////////////////////////////////////
/* -------------------- Update Notifications Notice -------------------- */
if ( !function_exists( 'wdp_un_check' ) ) {
  add_action( 'admin_notices', 'wdp_un_check', 5 );
  add_action( 'network_admin_notices', 'wdp_un_check', 5 );
  function wdp_un_check() {
    if ( class_exists( 'WPMUDEV_Update_Notifications' ) )
      return;

    if ( $delay = get_site_option( 'un_delay' ) ) {
      if ( $delay <= time() && current_user_can( 'install_plugins' ) )
      	echo '<div class="error fade"><p>' . __('Please install the latest version of <a href="http://premium.wpmudev.org/project/update-notifications/" title="Download Now &raquo;">our free Update Notifications plugin</a> which helps you stay up-to-date with the most stable, secure versions of WPMU DEV themes and plugins. <a href="http://premium.wpmudev.org/wpmu-dev/update-notifications-plugin-information/">More information &raquo;</a>', 'wpmudev') . '</a></p></div>';
	  } else {
			update_site_option( 'un_delay', strtotime( "+1 week" ) );
		}
	}
}
/* --------------------------------------------------------------------- */

/**
 * Escaping for textarea values.
 *
 * @since 3.1
 *
 * Added for compatibility with WordPress 3.0.*
 *
 * @param string $text
 * @return string
 */
if( !function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		$safe_text = htmlspecialchars( $text, ENT_QUOTES );
		return apply_filters( 'esc_textarea', $safe_text, $text );
	}
}


/**
 * Main help panel handler.
 */
class Ahc_AdminHelpContent {

	private $_help;
	private $_default_text = 'You can change the content in this Help drop-down by going to Settings > Admin Help Content on a regular WordPress install or Network Admin > Settings > Admin Help Content on a WordPress multiste install.';

	private function __construct () {
		if (!class_exists('WpmuDev_ContextualHelp')) require_once 'admin-help-content-files/class_wd_contextual_help.php';
		$this->_help = new WpmuDev_ContextualHelp;

		if ( defined( 'WPMU_PLUGIN_DIR' ) && file_exists( WPMU_PLUGIN_DIR . '/admin-help-content.php' ) ) {
			load_muplugin_textdomain( 'admin_help_content', 'admin-help-content-files/languages' );
		} else {
			load_plugin_textdomain( 'admin_help_content', false, dirname( plugin_basename( __FILE__ ) ) . '/admin-help-content-files/languages' );
		}
	}

	public static function serve () {
		$me = new Ahc_AdminHelpContent;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		add_action('admin_init', array($this, 'register_settings'));
		$hook = $this->_is_network_activated() ? 'network_admin_menu' : 'admin_menu';
		add_action($hook, array($this, 'create_admin_menu_entry'));

		$this->_initialize_help_content();
	}

	/**
	 * Main handling method.
	 * Pick up stored settings, convert them to proper format
	 * and feed them to abstract help handler.
	 */
	private function _initialize_help_content () {
		$opts = $this->_get_options();
		if (is_multisite()) {
			if ($opts['prevent_network'] && defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) return false;
			if (!$this->_is_network_activated() && defined('WP_NETWORK_ADMIN') && WP_NETWORK_ADMIN) return false;
		}
		$tabs = $opts['tabs'];
		foreach ($tabs as $idx => $tab) {
			$tabs[$idx]['id'] = md5(@$tab['title'] . @$tab['content'] . time());
		}
		$this->_help->add_page('_global_', $tabs, $opts['sidebar'], !@$opts['merge_panels']);
		$this->_help->initialize();
	}

/* ----- Helper  methods ----- */

	/**
	 * Returnds true if we have sidebar available (i.e. WP3.3+).
	 */
	private function _has_sidebar () {
		global $wp_version;
		$version = preg_replace('/-.*$/', '', $wp_version);

		if (version_compare($version, '3.3', '>=')) return true;
		return false;
	}

	/**
	 * Returns true if the plugin is network activated.
	 */
	private function _is_network_activated () {
		if (!is_multisite()) return false;

		$plugin = plugin_basename(__FILE__);
		$plugins = get_site_option( 'active_sitewide_plugins');
		return isset($plugins[$plugin]);
	}

	/**
	 * Gets appropriate options.
	 * If the old options are still around, attempt to convert them to new format.
	 */
	private function _get_options () {
		$opts = $this->_is_network_activated() ? get_site_option('admin_help_content') : get_option('admin_help_content');
		$opts = is_array($opts) ? $opts : array(
			'tabs' => array(
				array(
					'title' => __('Admin Help', 'admin_help_content'),
					'content' => ($opts ? $opts : $this->_default_text),
				),
			),
			'sidebar' => '',
			'prevent_network' => false,
			'merge_panels' => false,
		);
		return $opts;
	}

	/**
	 * Sets plugin options.
	 */
	private function _set_options ($opts) {
		return $this->_is_network_activated()
			? update_site_option('admin_help_content', $opts)
			: update_option('admin_help_content', $opts)
		;
	}

/* ----- Handlers ----- */

	function register_settings () {
		register_setting('admin_help_content', 'admin_help_content');
		add_settings_section('admin_help_content_setting_section', __('Help Content', 'admin_help_content'), '__return_false', 'admin_help_content');
		add_settings_field('admin_help_content_old', __('Existing Help Items', 'admin_help_content'), array($this, 'help_content_existing_elements'), 'admin_help_content', 'admin_help_content_setting_section');
		add_settings_field('admin_help_content_new', __('Add New Help Item', 'admin_help_content'), array($this, 'help_content_new_element'), 'admin_help_content', 'admin_help_content_setting_section');
		if ($this->_has_sidebar()) {
			add_settings_field('admin_help_sidebar', __('Help Sidebar', 'admin_help_content'), array($this, 'help_sidebar_element'), 'admin_help_content', 'admin_help_content_setting_section');
		}
		add_settings_field('admin_help_settings', __('Help Panel Settings', 'admin_help_content'), array($this, 'help_settings_element'), 'admin_help_content', 'admin_help_content_setting_section');
	}

	function help_content_existing_elements () {
		$opts = $this->_get_options();
		$tabs = $opts['tabs'];
		$tabs = $tabs ? $tabs : array();

		if (!$tabs) {
			echo '<div class="updated below-h2"><p>' .
				__('There are no existing help items to edit.', 'admin_help_content') .
			'</p></div>';
			return false;
		}

		foreach ($tabs as $idx => $tab) {
			$title = esc_attr($tab['title']);
			$body = esc_textarea($tab['content']);
			$class = ($idx%2) ? 'even' : 'odd';
			echo '<p class="ahc-help_item ' . $class . '">' .
				"<label for='ahc-tab-{$idx}-title'>" . __('Help Item Title', 'admin_help_content') . '</label>' .
				"<input type='text' class='widefat' name='admin_help_content[tabs][{$idx}][title]' id='ahc-tab-{$idx}-title' value='{$title}' />" .
				"<label for='ahc-tab-{$idx}-content'>" . __('Help Item Content', 'admin_help_content') . '</label>' .
				"<textarea class='widefat' name='admin_help_content[tabs][{$idx}][content]' id='ahc-tab-{$idx}-content'>{$body}</textarea>" .
				'<a href="#" class="ahc-remove_item">' . __('Remove this item', 'admin_help_content') . '</a>' .
			'</p>';
		}
		echo <<<EoAhcTabsJs
<style type="text/css">
.ahc-help_item {
	padding: 10px;
	border:1px solid #ccc;
	-moz-border-radius:3px;-khtml-border-radius:3px;-webkit-border-radius:3px;border-radius:3px;
 	-moz-box-shadow: 0 3px 3px #ccc;
 	-webkit-box-shadow: 0 3px 3px #ccc;
 	box-shadow: 0 3px 3px #ccc;
}
.ahc-help_item.even {
	background: #eee;
}
</style>
<script type="text/javascript">
(function ($) {
$(function () {

$(".ahc-remove_item").click(function () {
	$(this).parents(".ahc-help_item").remove();
	return false;
});

});
})(jQuery);
</script>
EoAhcTabsJs;
	}

	function help_content_new_element () {
		echo '' .
			"<label for='ahc-tab-new-title'>" . __('New Help Item Title') . '</label>' .
			"<input type='text' class='widefat' name='admin_help_content[new_tab][title]' id='ahc-tab-new-title' value='' />" .
			"<label for='ahc-tab-new-content'>" . __('New Help Item Content') . '</label>' .
			"<textarea class='widefat' name='admin_help_content[new_tab][content]' id='ahc-tab-new-content'></textarea>" .
		'<br />';
		_e( 'HTML Allowed.', 'admin_help_content' );
		echo '<p><input type="submit" value="' . esc_attr(__('Add', 'admin_help_content')) . '" /></p>';
	}

	function help_sidebar_element () {
		$opts = $this->_get_options();
		$bar = esc_textarea($opts['sidebar']);
		echo '' .
			"<textarea class='widefat' name='admin_help_content[sidebar]' id='ahc-sidebar-content'>{$bar}</textarea>" .
		'<br />';
		_e( 'HTML Allowed.', 'admin_help_content' );
	}

	function help_settings_element () {
		$opts = $this->_get_options();

		if ($this->_is_network_activated()) {
			$network = @$opts['prevent_network'] ? 'checked="checked"' : '';
			echo '' .
				'<input type="hidden" name="admin_help_content[prevent_network]" value="" />' .
				"<input type='checkbox' id='ahc-prevent_network' name='admin_help_content[prevent_network]' value='1' {$network} />" .
				'&nbsp;' .
				'<label for="ahc-prevent_network">' . __('Do not show new help panels in Network Admin area', 'admin_help_content') . '</label>' .
			'<br />';
		}

		$merge = @$opts['merge_panels'] ? 'checked="checked"' : '';
		echo '' .
			'<input type="hidden" name="admin_help_content[merge_panels]" value="" />' .
			"<input type='checkbox' id='ahc-merge_panels' name='admin_help_content[merge_panels]' value='1' {$merge} />" .
			'&nbsp;' .
			'<label for="ahc-merge_panels">' . __('Keep default help items (if any) and merge the new ones with them.', 'admin_help_content') . '</label>' .
		'<br />';

	}

	function create_admin_menu_entry () {
		if (@$_POST && isset($_POST['option_page']) && 'admin_help_content' == @$_POST['option_page']) {
			if (isset($_POST['admin_help_content'])) {
				$tabs = $_POST['admin_help_content']['tabs'];
				$tabs = is_array($tabs) ? $tabs : array();
				if (trim(@$_POST['admin_help_content']['new_tab']['title']) && trim(@$_POST['admin_help_content']['new_tab']['content'])) {
					$tabs[] = $_POST['admin_help_content']['new_tab'];
					unset($_POST['admin_help_content']['new_tab']);
				}
				foreach ($tabs as $key=>$tab) {
					$tabs[$key]['title'] = strip_tags(stripslashes($tab['title']));
					$tabs[$key]['content'] = stripslashes($tab['content']);
				}
				$_POST['admin_help_content']['tabs'] = $tabs;
				$_POST['admin_help_content']['sidebar'] = stripslashes($_POST['admin_help_content']['sidebar']);
				$this->_set_options($_POST['admin_help_content']);
			}
			$goback = add_query_arg('settings-updated', 'true',  wp_get_referer());
			wp_redirect($goback);
			die;
		}
		$page = $this->_is_network_activated() ? 'settings.php' : 'options-general.php';
		$perms = $this->_is_network_activated() ? 'manage_network_options' : 'manage_options';
		add_submenu_page($page, __('Admin Help Content', 'admin_help_content'), __('Admin Help Content', 'admin_help_content'), $perms, 'admin_help_content', array($this, 'create_admin_page'));
	}

	function create_admin_page () {
		$settings = __('Admin Panel Help Settings', 'admin_help_content');
		$action = $this->_is_network_activated() ? 'settings.php' : 'options.php';
		echo '' .
			'<div class="wrap">' .
				"<h2>{$settings}</h2>" .
				"<form action='{$action}' method='post'>" .
		'';
		settings_fields('admin_help_content');
		do_settings_sections('admin_help_content');
		echo
			'' .
					'<p class="submit">' .
						'<input name="Submit" type="submit" class="button-primary" value="' . esc_attr('Save Changes') . '" />' .
					'</p>' .
				'</form>' .
			'</div>' .
		'';
	}
}

if (is_admin()) Ahc_AdminHelpContent::serve();