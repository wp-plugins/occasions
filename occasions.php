<?php
/*
Plugin Name: Occasions
Version: 1.0.3
Plugin URI: http://www.schloebe.de/wordpress/occasions-plugin/
Description: <strong>WordPress 2.5+ only.</strong> Do it like Google! Define any number of occasions in your BE with a fancy AJAX-Interface and the plugin will display them in time... just like Google.
Author: Oliver Schl&ouml;be
Author URI: http://www.schloebe.de/


Copyright 2009-2012 Oliver Schloebe (email : scripts@schloebe.de)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * The main plugin file
 *
 * @package WordPress_Plugins
 * @subpackage Occasions
 */
 

/**
 * Pre-2.6 compatibility
 */
if ( !defined( 'WP_CONTENT_URL' ) )
	define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( !defined( 'WP_CONTENT_DIR' ) )
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( !defined( 'WP_PLUGIN_URL' ) )
	define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( !defined( 'WP_PLUGIN_DIR' ) )
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

/**
 * Define the plugin version
 */
define("OCC_VERSION", "1.0.3");

/**
 * Define the plugin path slug
 */
define("OCC_PLUGINPATH", "/" . plugin_basename( dirname(__FILE__) ) . "/");

/**
 * Define the plugin full url
 */
define("OCC_PLUGINFULLURL", WP_PLUGIN_URL . OCC_PLUGINPATH );

/**
 * Define the plugin full directory
 */
define("OCC_PLUGINFULLDIR", WP_PLUGIN_DIR . OCC_PLUGINPATH );


/**
 * @since 1.0
 * @author scripts@schloebe.de
 * @uses function occ_get_resource_url() to display
 */
if( isset($_GET['resource']) && !empty($_GET['resource'])) {
	$resources = array(
		'occ.gif' =>
		'R0lGODlhCgAKAKIAADMzM93d3cXFxZCQkGZmZv/////M/wAAAC'.
		'H5BAEHAAYALAAAAAAKAAoAAAMgOLpMEQ4+Qoq9lk4a9ZubIHIj'.
		'IZ6moKkkFH0fAcx0nQAAOw=='.
	'');
	
	if(array_key_exists($_GET['resource'], $resources)) {
		
		$content = base64_decode($resources[ $_GET['resource'] ]);

		$lastMod = filemtime(__FILE__);
		$client = ( isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : false );
		if (isset($client) && (strtotime($client) == $lastMod)) {
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastMod).' GMT', true, 304);
			exit;
		} else {
			header('Last-Modified: '.gmdate('D, d M Y H:i:s', $lastMod).' GMT', true, 200);
			header('Content-Length: '.strlen($content));
			header('Content-Type: image/' . substr(strrchr($_GET['resource'], '.'), 1) );
			echo $content;
			exit;
		}
	}
}


/** 
* The Occasions class
*
* @package WordPress_Plugins
* @subpackage Occasions
* @since 1.0
* @author scripts@schloebe.de
*/
class Occasions {

	/**
 	* The Occasions class constructor
 	* initializing required stuff for the plugin
 	*
 	* @since 1.0
 	* @author scripts@schloebe.de
 	*/
	function occasions() {
		global $pagenow;
		add_action('init', array(&$this, 'occ_load_textdomain'));
		add_action('wp_ajax_occasions_sack_addnode', array(&$this, 'occasions_sack_addnode') );
		add_action('wp_ajax_occasions_sack_deletedataset', array(&$this, 'occasions_sack_deletedataset') );
		if ( is_admin() ) {
			add_action('admin_menu', array(&$this, 'occ_add_optionpage'));
			add_filter('plugin_action_links', array(&$this, 'occ_filter_plugin_actions'), 10, 2);
			//add_action('activate_' . plugin_basename(__FILE__), array(&$this, 'occ_setup'));
			register_activation_hook( __FILE__, array(&$this, 'occ_setup') );
			if ( basename($_SERVER['REQUEST_URI']) == 'occasions.php' ) {
				add_action('admin_print_scripts', array(&$this, 'occ_js_header'));
			}
			if( version_compare($GLOBALS['wp_version'], '2.4.999', '>') ) {
				/** 
				 * This file holds all the author plugins functions
				 */
				require_once(dirname (__FILE__) . '/' . 'authorplugins.inc.php');
			}
		}
		if( version_compare($GLOBALS['wp_version'], '2.4.999', '>') && function_exists('add_shortcode') ) {
			add_shortcode('Occasions', array(&$this, 'occ_tag_func'));
		}
		if ( !class_exists('WPlize') ) {
			require_once(OCC_PLUGINFULLDIR . 'inc/wplize.class.php');
		}
		$GLOBALS['OCCWPLIZE'] = new WPlize('occ_options');
	}

	/**
 	* Initialize and load the plugin textdomain
 	*
 	* @since 1.0
 	* @author scripts@schloebe.de
 	*/
	function occ_load_textdomain() {
		if ( function_exists('load_plugin_textdomain') ) {
			if ( !defined('WP_PLUGIN_DIR') ) {
       		 	load_plugin_textdomain('occasions', str_replace( ABSPATH, '', dirname(__FILE__) ) . '/lang');
        	} else {
        		load_plugin_textdomain('occasions', false, dirname(plugin_basename(__FILE__)) . '/lang');
        	}
		}
	}


	/**
 	* Create and setup the plugin database tables
 	*
 	* @since 1.0
 	* @author scripts@schloebe.de
 	*/
	function occ_setup() {
		global $wpdb;
		$occ_db_version = '1.0.2';
		
		$collate = "";
		if( $wpdb->supports_collation() ) {
			if(!empty($wpdb->charset)) $collate = "DEFAULT CHARACTER SET `" . $wpdb->charset . "`";
			if(!empty($wpdb->collate)) $collate .= " COLLATE `" . $wpdb->collate . "`";
		}
		
		$table_name = $wpdb->prefix . "occasions";
		$sql = "CREATE TABLE IF NOT EXISTS `" . $table_name . "` (
			`id` int(3) unsigned NOT NULL,
			`title` text NOT NULL,
			`date_start` int(11) NOT NULL,
			`date_end` int(11) NOT NULL,
			`type` int(1) NOT NULL,
			`content` text NOT NULL,
			PRIMARY KEY  (`id`)
		);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta( $sql );
		
		$occ_options = array(
							'occ_db_version' => $occ_db_version,
							'occ_prepend' => '<div class="occasions"><ul id="the-occasions-list">',
							'occ_append' => '</ul></div>',
							'occ_prepend_record' => '<li class="the-occasions-item" id="the-occasions-item-###ID###">',
							'occ_append_record' => '</li>',
							'occ_nocontent' => __('No occasions for today!', 'occasions')
							);
		$GLOBALS['OCCWPLIZE']->init_option( $occ_options );
		
		return;
	}


	/**
 	* Create options page
 	*
 	* @since 1.0
 	* @author scripts@schloebe.de
 	*/
	function occ_add_optionpage() {
		if ( is_admin() && current_user_can('switch_themes') && function_exists('add_submenu_page') ) {
			$menutitle = '';
			if ( version_compare( $GLOBALS['wp_version'], '2.6.999', '>' ) ) {
				$menutitle = '<img src="' . $this->occ_get_resource_url('occ.gif') . '" alt="" />' . ' ';
			}
			$menutitle .= __('Occasions', 'occasions');
 
			add_submenu_page('options-general.php', __('Occasions', 'occasions'), $menutitle, 8, __FILE__, array(&$this, 'occ_options_page'));
		}
	}
	
	
	/**
	 * Display Images/Icons base64-encoded
	 * 
	 * @since 1.0
	 * @author scripts@schloebe.de
	 * @param $resourceID
	 * @return $resourceURL
	 */
	function occ_get_resource_url( $resourceID ) {
		return trailingslashit( get_bloginfo('url') ) . '?resource=' . $resourceID;
	}


	/**
 	* Add action link(s) to plugins page
 	* 
 	* @since 1.0
 	* @author scripts@schloebe.de
 	* @copyright Dion Hulse, http://dd32.id.au/wordpress-plugins/?configure-link
 	*/
	function occ_filter_plugin_actions($links, $file) {
		static $this_plugin;

		if( !$this_plugin ) $this_plugin = plugin_basename(__FILE__);

		if( $file == $this_plugin ){
			$settings_link = '<a href="options-general.php?page=occasions/occasions.php">' . __('Settings') . '</a>';
			$links = array_merge( array($settings_link), $links);
		}
		return $links;
	}
	
	
	/**
	 * Adds the node control js file to the plugin page header
	 *
	 * @since 1.0
	 * @author scripts@schloebe.de
	 */
	function occ_js_header() {
		add_action('admin_head', wp_enqueue_script( 'date', OCC_PLUGINFULLURL . "js/date.js", array('jquery'), OCC_VERSION ), 10 );
		add_action('admin_head', wp_enqueue_script( 'datePicker', OCC_PLUGINFULLURL . "js/jquery.datePicker.js", array('jquery'), OCC_VERSION ), 20 );
		add_action('admin_head', wp_enqueue_script( 'occasions-control', OCC_PLUGINFULLURL . "js/occasions.control.js", array('jquery', 'jquery-color', 'sack'), OCC_VERSION ), 30 );
		?>
<link rel="stylesheet" href="<?php echo OCC_PLUGINFULLURL; ?>css/datePicker.css" type="text/css" />
<script type="text/javascript">
/* <![CDATA[ */
OccasionsAjaxL10n = {
	requestUrl: "<?php bloginfo( 'wpurl' ); ?>/wp-admin/admin-ajax.php", Time: "<?php _e("Insert time"); ?>", confirmMessage: "<?php _e("This will delete the dataset from the database. Continue?", 'occasions'); ?>", currentYear: "<?php echo date('Y'); ?>", nextYear: "<?php echo date('Y')+1; ?>", addingLabel: "<?php _e('Adding ...', 'occasions'); ?>"
}
/* ]]> */
</script>
		<?
	}
	
	
	/**
 	* Output the datasets as nodes
 	* 
 	* @since 1.0
 	* @author scripts@schloebe.de
 	*/
	function occasions_nodes() {
		global $wpdb;
		$output = '';
		
		$occasions = $wpdb->get_results('SELECT * FROM ' . $wpdb->prefix . 'occasions', ARRAY_A);
		
		$output .= '<table class="widefat the-occasions-table" cellspacing="0">';
		$output .= '<thead>';
		$output .= '<tr class="thead">';
		$output .= '<th class="check-column" scope="col" style="width:25px;"></th>';
		$output .= '<th class="manage-column" scope="col" style="width:190px;">' . __('Title', 'occasions') . '</th>';
		$output .= '<th class="manage-column" scope="col" style="width:100px;">' . __('Startdate', 'occasions') . '</th>';
		$output .= '<th class="manage-column" scope="col" style="width:100px;">' . __('Enddate', 'occasions') . '</th>';
		$output .= '<th class="manage-column" scope="col" style="width:70px;">' . __('Output Type', 'occasions') . '</th>';
		$output .= '<th class="manage-column" scope="col">' . __('Content', 'occasions') . '</th>';
		$output .= '</tr>';
		$output .= '</thead>';
		$output .= '<tfoot>';
		$output .= '<tr class="thead">';
		$output .= '<th class="check-column" scope="col"></th>';
		$output .= '<th class="manage-column" scope="col">' . __('Title', 'occasions') . '</th>';
		$output .= '<th class="manage-column" scope="col">' . __('Startdate', 'occasions') . '</th>';
		$output .= '<th class="manage-column" scope="col">' . __('Enddate', 'occasions') . '</th>';
		$output .= '<th class="manage-column" scope="col">' . __('Output Type', 'occasions') . '</th>';
		$output .= '<th class="manage-column" scope="col">' . __('Content', 'occasions') . '</th>';
		$output .= '</tr>';
		$output .= '</tfoot>';
		$output .= '<tbody id="the-occasions-list">';
		
		if ( !empty($occasions) ) {
			foreach ( $occasions as $occasion ) {
				$output .= '<tr id="occnode' . $occasion['id'] . '">';
				$output .= '<td><a href="javascript:void(0);" id="delnode' . $occasion['id'] . '" class="deletenode delete" onclick="return occ_hardDeleteNode(' . $occasion['id'] . ')">X</a> <input type="hidden" name="nodes[]" value="' . $occasion['id'] . '" /></td>';
				$output .= '<td><input type="text" name="occ_title' . $occasion['id'] . '" value="' . stripslashes( wptexturize( $occasion['title'] ) ) . '" style="width:100%;" /></td>';
				$output .= '<td><input type="text" readonly="readonly" name="occ_startdate' . $occasion['id'] . '" value="' . date( 'd.m.', $occasion['date_start'] ) . '" style="width:70px;" /> <a class="date-pick startdate dp-applied" href="javascript:void(0);"><img src="' . OCC_PLUGINFULLURL . 'img/date.gif" border="" alt="" /></a></td>';
				$output .= '<td><input type="text" readonly="readonly" name="occ_enddate' . $occasion['id'] . '" value="' . date( 'd.m.', $occasion['date_end'] ) . '" style="width:70px;" /> <a class="date-pick enddate dp-applied" href="javascript:void(0);"><img src="' . OCC_PLUGINFULLURL . 'img/date.gif" border="" alt="" /></a></td>';
				$output .= '<td>' . $this->occ_settype( $occasion['id'], $occasion['type'] ) . '</td>';
				$output .= '<td><textarea name="occ_content' . $occasion['id'] . '" cols="40" rows="1" style="width:100%;height:50px;">' . stripslashes( wptexturize( $occasion['content'] ) ) . '</textarea></td>';
				$output .= '</tr>';
			}
		} else {
			$output .= '<tr id="occ_node-initial">';
			$output .= '<td colspan="6" style="text-align:center;">' . __('None yet', 'occasions') . '</td>';
			$output .= '</tr>';
		}
		$output .= '</tbody>';
		$output .= '</table>';
		
		echo $output;
	}
	
	
	/**
 	* SACK function for adding a node
 	*
 	* @since 1.0
 	* @author scripts@schloebe.de
 	*/
	function occasions_sack_addnode() {
		$i = $_POST['inumber']; $j = $i-1;
		$occ_new = $_POST['occ_new'];
		$return = '';
		$return .= '<tr id="occnode' . $i . '">';
		$return .= '<td><a href="javascript:void(0);" id="delnode' . $i . '" class="deletenode delete" onclick="occ_deleteNode(' . $i . ')">X</a> <input type="hidden" name="nodes[]" value="' . $i . '" /></td>';
		$return .= '<td><input type="text" name="occ_title' . $i . '" value="" style="width:100%;" /></td>';
		$return .= '<td><input type="text" readonly="readonly" name="occ_startdate' . $i . '" value="" style="width:70px;" /> <a class="date-pick startdate dp-applied" href="javascript:void(0);"><img src="' . OCC_PLUGINFULLURL . 'img/date.gif" border="" alt="" /></a></td>';
		$return .= '<td><input type="text" readonly="readonly" name="occ_enddate' . $i . '" value="" style="width:70px;" /> <a class="date-pick enddate dp-applied" href="javascript:void(0);"><img src="' . OCC_PLUGINFULLURL . 'img/date.gif" border="" alt="" /></a></td>';
		$return .= '<td>' . $this->occ_settype( $i, '' ) . '</td>';
		$return .= '<td><textarea name="occ_content' . $i . '" cols="40" rows="1" style="width:100%;height:50px;"></textarea></td>';
		$return .= '</tr>';
		
		$addto = ($occ_new == '1') ? "jQuery('#the-occasions-list')" : "jQuery('tr[id^=\'occnode\']:last')";
		die( "jQuery('#occ_node-initial').remove();" . $addto . ".after('" . $return . "'); jQuery('input#occ_addnode').removeAttr( 'disabled' ); jQuery('input#occ_addnode').val( '" . __('Add', 'occasions') . "' ); occ_setupDP();" );
	}
	
	
	/**
 	* SACK function for deleting a dataset from the database
 	*
 	* @since 1.0
 	* @author scripts@schloebe.de
 	*/
	function occasions_sack_deletedataset() {
		global $wpdb;
		$i = intval( $_POST['inumber'] );
		$wpdb->query( $wpdb->prepare("DELETE FROM " . $wpdb->prefix . "occasions WHERE id=%d", $i ) );
		
		die( "occ_deleteNode(" . $i . ");" );
	}
	
	
	/**
 	* Output the specified type checkboxes
 	* 
 	* @since 1.0
 	* @author scripts@schloebe.de
 	* 
 	* @return string
 	*/
	function occ_settype( $id, $type ) {
		$return = '';
		$checked = (!isset($type) || $type=='') ? ' checked="checked"' : '';
		$checked0 = ($type == '0') ? ' checked="checked"' : '';
		$checked1 = ($type == '1') ? ' checked="checked"' : '';
		$return .= '<input type="radio" name="occ_type' . $id . '" id="occ_type' . $id . '_0" value="0"' . $checked0 . $checked . ' /> <label for="occ_type' . $id . '_0">' . __('Text', 'occasions') . '</label><br />';
		$return .= '<input type="radio" name="occ_type' . $id . '" id="occ_type' . $id . '_1" value="1"' . $checked1 . ' /> <label for="occ_type' . $id . '_1">' . __('HTML', 'occasions') . '</a>';
		
		return $return;
	}


	/**
 	* Return the occasions
 	*
 	* @since 1.0
 	* @author scripts@schloebe.de
 	* 
 	* @return string
 	*/
	function _return() {
		global $wpdb;
		
		$output = '';
		$output .= stripslashes( $GLOBALS['OCCWPLIZE']->get_option('occ_prepend') ) . chr(10);
		
		$sql = "SELECT * FROM " . $wpdb->prefix . "occasions
			WHERE
				(DATE_FORMAT(NOW(), '%m%d') BETWEEN
      				DATE_FORMAT(FROM_UNIXTIME(date_start), '%m%d') AND
      				DATE_FORMAT(FROM_UNIXTIME(date_end), '%m%d'))
				OR (DATE_FORMAT(FROM_UNIXTIME(date_end), '%m%d') BETWEEN
      				DATE_FORMAT(NOW(), '%m%d') AND
      				DATE_FORMAT(FROM_UNIXTIME(date_start) - INTERVAL 1 DAY, '%m%d'))
				OR (DATE_FORMAT(FROM_UNIXTIME(date_start), '%m%d') BETWEEN
      				DATE_FORMAT(FROM_UNIXTIME(date_end) + INTERVAL 1 DAY, '%m%d') AND
      				DATE_FORMAT(NOW(), '%m%d'))";
		$q_occasions = $wpdb->get_results( $sql, ARRAY_A );
		if( count( $q_occasions ) > 0 ) {
			foreach ($q_occasions as $occasion) {
				$output .= stripslashes( str_replace('###ID###', $occasion['id'], $GLOBALS['OCCWPLIZE']->get_option('occ_prepend_record')) );
				if( $occasion['type'] == '0' )
					$occ_content = strip_tags( stripslashes( $occasion['content'] ) );
				else
					$occ_content = stripslashes( $occasion['content'] );
				$output .= $occ_content;
				$output .= stripslashes( str_replace('###ID###', $occasion['id'], $GLOBALS['OCCWPLIZE']->get_option('occ_append_record')) ) . chr(10);
			}
		} else {
				$output .= stripslashes( $GLOBALS['OCCWPLIZE']->get_option('occ_nocontent') );
		}
				
		$output .= stripslashes( $GLOBALS['OCCWPLIZE']->get_option('occ_append') ) . chr(10);
		
		return $output;
	}


	/**
 	* Print the occasions
 	*
 	* @since 1.0
 	* @author scripts@schloebe.de
 	* 
 	* @return string
 	*/
	function _output() {
		echo $this->_return();
	}
	
	
	/**
 	* Setups the plugin's shortcode
	*
 	* @since 1.0
 	* @author scripts@schloebe.de
 	*
 	* @param mixed
 	* @return string
 	*/
	function occ_tag_func( $atts ) {
		extract(shortcode_atts(array(
		), $atts));
	
		return $this->_return();
	}


	/**
 	* Filling the options page with content
 	*
 	* @since 1.0
 	* @author scripts@schloebe.de
 	* 
 	* @return string
 	*/
	function occ_options_page() {
		global $wpdb;
		?>
	<div class="wrap">
      <h2>
        <?php _e('Occasions', 'occasions'); ?>
      </h2>
	  <?php
	  	if ( isset($_POST) === true && isset($_POST['action']) === true && $_POST['action'] == 'edit' ) {
			$GLOBALS['OCCWPLIZE']->update_option(
				array(
					'occ_prepend'	=> $_POST['occ_prepend'],
					'occ_append'	=> $_POST['occ_append'],
					'occ_prepend_record'	=> $_POST['occ_prepend_record'],
					'occ_append_record'	=> $_POST['occ_append_record'],
					'occ_nocontent' => $_POST['occ_nocontent']
				)
			);
			
			$successmessage = __('Settings saved.', 'occasions');
			echo '<div id="occ_successmessage" class="updated fade">
				<p>
					<strong>
						' . $successmessage . '
					</strong>
				</p>
			</div><br />';
		}
		if ( isset($_POST) === true && isset($_POST['action']) === true && $_POST['action'] == 'saveoccasions' ) {
			foreach( $_POST['nodes'] as $value ) {
				$count = intval($value);
				
				if( $_POST['occ_title' . $count] != '' && $_POST['occ_startdate' . $count] != '' && $_POST['occ_enddate' . $count] != '' && $_POST['occ_content' . $count] != '' ) {
					$startdate_parts = explode('.', $_POST['occ_startdate' . $count]);
					$enddate_parts = explode('.', $_POST['occ_enddate' . $count]);
					$startdate = gmmktime(0, 0, 0, $startdate_parts[1], $startdate_parts[0], gmdate('Y'));
					$enddate = gmmktime(0, 0, 0, $enddate_parts[1], $enddate_parts[0], gmdate('Y'));
					$dataset_exists = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(id) FROM " . $wpdb->prefix . "occasions WHERE id=%d", $count) );
					if( $dataset_exists >= 1 ) {
						$wpdb->query( $wpdb->prepare("UPDATE " . $wpdb->prefix . "occasions SET id=%d, title=%s, date_start=%d, date_end=%d, type=%d, content=%s WHERE id=%d", $count, $_POST['occ_title' . $count], $startdate, $enddate, $_POST['occ_type' . $count], $_POST['occ_content' . $count], $count ) );
					} else {
						$wpdb->query( $wpdb->prepare("INSERT INTO " . $wpdb->prefix . "occasions ( id, title, date_start, date_end, type, content ) VALUES( %s, %s, %d, %d, %d, %s )", $count, $_POST['occ_title' . $count], $startdate, $enddate, $_POST['occ_type' . $count], $_POST['occ_content' . $count]) );
					}
				}
				if( WP_DEBUG ) {
					$wpdb->show_errors();
					$wpdb->print_error();
				}
				
			}
			$wpdb->flush();
			
			$successmessage = __('Occasions saved successfully.', 'occasions');
			echo '<div id="occ_successmessage" class="updated fade">
				<p>
					<strong>
						' . $successmessage . '
					</strong>
				</p>
			</div><br />';
		}
	  ?>
		<div id="poststuff" class="ui-sortable">
	  
		<div id="occ_nodes_box" class="postbox">
      	<h3>
        	<?php _e('Manage Occasions', 'occasions'); ?>
      	</h3>
		
		<form name="occ_form2" id="occ_form2" action="" method="post">
		<input type="hidden" name="action" value="saveoccasions" />
		<div class="inside">
		
		<table class="form-table">
 		<tr>
 			<td>
 				<?php $this->occasions_nodes(); ?>
 			</td>
 		</tr>
		</table>
		
		<div class="inside">
			<p class="submit">
				<?php _e('<strong>Please note</strong> that you have to fill out all fields to successfully save or update a dataset!', 'occasions'); ?>
				<br /><br />
				<input type="button" name="occ_addnode" id="occ_addnode" value="<?php _e('Add', 'occasions'); ?>" class="button button-secondary" /> <input type="submit" name="submit" value="<?php _e('Save Datasets', 'occasions'); ?> &raquo;" class="button-primary" />
			</p>
		</div>
		</div>
		</div>
		</form>
	  
		<div id="occ_general_box" class="postbox">
      	<h3>
        	<?php _e('General Options', 'occasions'); ?>
      	</h3>
		
		<form name="occ_form" id="occ_form" action="" method="post">
		<input type="hidden" name="action" value="edit" />
		<div class="inside">
		<table class="form-table">
 		<tr>
 			<th scope="row" valign="top"><?php _e('HTML to prepend to the output', 'occasions'); ?></th>
 			<td>
 				<textarea name="occ_prepend" id="occ_prepend" cols="60" rows="2"><?php echo stripslashes( $GLOBALS['OCCWPLIZE']->get_option('occ_prepend') ); ?></textarea>
				<p><?php _e('HTML is <strong>enabled</strong>.', 'occasions'); ?></p>
 			</td>
 		</tr>
 		<tr>
 			<th scope="row" valign="top"><?php _e('HTML to append to the output', 'occasions'); ?></th>
 			<td>
 				<textarea name="occ_append" id="occ_append" cols="60" rows="2"><?php echo stripslashes( $GLOBALS['OCCWPLIZE']->get_option('occ_append') ); ?></textarea>
				<p><?php _e('HTML is <strong>enabled</strong>.', 'occasions'); ?></p>
 			</td>
 		</tr>
 		<tr>
 			<th scope="row" valign="top"><?php _e('HTML to prepend to each record', 'occasions'); ?></th>
 			<td>
 				<textarea name="occ_prepend_record" id="occ_prepend_record" cols="60" rows="2"><?php echo stripslashes( $GLOBALS['OCCWPLIZE']->get_option('occ_prepend_record') ); ?></textarea>
				<p><?php _e('HTML is <strong>enabled</strong>.', 'occasions'); ?> | <?php _e('Parameters:', 'occasions'); ?> ###ID###</p>
 			</td>
 		</tr>
 		<tr>
 			<th scope="row" valign="top"><?php _e('HTML to append to each record', 'occasions'); ?></th>
 			<td>
 				<textarea name="occ_append_record" id="occ_append_record" cols="60" rows="2"><?php echo stripslashes( $GLOBALS['OCCWPLIZE']->get_option('occ_append_record') ); ?></textarea>
				<p><?php _e('HTML is <strong>enabled</strong>.', 'occasions'); ?></p>
 			</td>
 		</tr>
 		<tr>
 			<th scope="row" valign="top"><?php _e('HTML to output if there are no current occasions', 'occasions'); ?></th>
 			<td>
 				<textarea name="occ_nocontent" id="occ_nocontent" cols="60" rows="2"><?php echo stripslashes( $GLOBALS['OCCWPLIZE']->get_option('occ_nocontent') ); ?></textarea>
				<p><?php _e('HTML is <strong>enabled</strong>.', 'occasions'); ?></p>
 			</td>
 		</tr>
		</table>
		
		<div class="inside">
			<p class="submit">
				<input type="submit" name="submit" value="<?php _e('Save Changes'); ?> &raquo;" class="button-primary" />
			</p>
		</div>
		</div>
		</div>
		</form>
	  
		<?php if( version_compare($GLOBALS['wp_version'], '2.4.999', '>') ) { ?>
		<div id="occ_plugins_box" class="postbox if-js-open">
      	<h3>
        	<?php _e('More of my WordPress plugins', 'occasions'); ?>
      	</h3>
		<div class="inside">
		<table class="form-table">
 		<tr>
 			<td>
 				<?php _e('You may also be interested in some of my other plugins:', 'occasions'); ?>
				<p id="authorplugins-wrap"><input id="authorplugins-start" value="<?php _e('Show other plugins by this author inline &raquo;', 'occasions'); ?>" class="button-secondary" type="button"></p>
				<div id="authorplugins-wrap">
					<div id='authorplugins'>
						<div class='authorplugins-holder full' id='authorplugins_secondary'>
							<div class='authorplugins-content'>
								<ul id="authorpluginsul">
									
								</ul>
								<div class="clear"></div>
							</div>
						</div>
					</div>
				</div>
 				<?php _e('More plugins at: <a class="button rbutton" href="http://www.schloebe.de/portfolio/" target="_blank">www.schloebe.de</a>', 'occasions'); ?>
 			</td>
 		</tr>
		</table>
		</div>
		</div>
		<?php } ?>
	  
		<div id="occ_help_box" class="postbox">
      	<h3>
        	<?php _e('Help', 'occasions'); ?>
      	</h3>
		<div class="inside">
		<table class="form-table">
 		<tr>
 			<td>
 				<p><?php _e('If you are new using this plugin or dont understand what all these settings do, please read the documentation at <a href="http://www.schloebe.de/wordpress/occasions-plugin/" target="_blank">http://www.schloebe.de/wordpress/occasions-plugin/</a>', 'occasions'); ?></p>
				<p><?php _e('To output the occasions records, use the following code:', 'occasions'); ?><br />
				<code>&lt;?php
	if( class_exists('Occasions') ) {
		$Occasions->_output();
	}
	?&gt;</code></p>
				<p><?php _e('To load the occasions records into a variable, use the following code:', 'occasions'); ?><br />
				<code>&lt;?php
	if( class_exists('Occasions') ) {
		$occasionsoutput = $Occasions->_return();
	}
	?&gt;</code></p>
				<p><?php _e('There is also a shortcode available:', 'occasions'); ?><br />
				<code>[Occasions]</code></p>
 			</td>
 		</tr>
		</table>
		</div>
		</div>
		
 	</div>
		<?php
	}
}

if ( class_exists('Occasions') ) {
	$Occasions = new Occasions();
}
?>