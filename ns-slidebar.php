<?php
/*
 * Plugin Name: NS Slidebar - Sliding Panel Sidebar
 * Plugin URI: http://neversettle.it
 * Text Domain: ns-slidebar
 * Description: Add a dynamic slide out sidebar panel to any theme with built-in site search and a widget area.
 * Author: Never Settle
 * Author URI: http://neversettle.it
 * Version: 1.0.0
 * License: GPLv2 or later
 */

/*
	Copyright 2014 Never Settle (email : dev@neversettle.it)
	
	This program is free software; you can redistribute it and/or
	modify it under the terms of the GNU General Public License
	as published by the Free Software Foundation; either version 2
	of the License, or (at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // exit if accessed directly!
}

require_once(plugin_dir_path(__FILE__).'ns-sidebar/ns-sidebar.php');

class NS_Slidebar {
	
	var $path; 				// path to plugin dir
	var $wp_plugin_page; 	// url to plugin page on wp.org
	var $ns_plugin_page; 	// url to pro plugin page on ns.it
	var $ns_plugin_name; 	// friendly name of this plugin for re-use throughout
	var $ns_plugin_menu; 	// friendly menu title for re-use throughout
	var $ns_plugin_slug; 	// slug name of this plugin for re-use throughout
	var $ns_plugin_ref; 	// reference name of the plugin for re-use throughout
	var $ns_plugin_version; // version number of plugin for re-use throughout
	var $settings;          // var to keep track of
	
	function __construct(){		
		$this->path = plugin_dir_path( __FILE__ );
		// TODO: update to actual
		$this->wp_plugin_page = "http://wordpress.org/plugins/ns-slidebar";
		$this->ns_plugin_page = "http://neversettle.it/";
		$this->ns_plugin_name = "NS Slidebar";
		$this->ns_plugin_menu = "NS Slidebar";
		$this->ns_plugin_slug = "ns-slidebar";
		$this->ns_plugin_ref = "ns_slidebar";
		$this->ns_plugin_version = '1.0.0';
		
		add_action( 'plugins_loaded', array($this, 'setup_plugin') );	
		add_action( 'admin_init', array($this,'register_settings_fields') );		
		add_action( 'admin_menu', array($this,'register_settings_page'), 20 );
		add_action( 'admin_enqueue_scripts', array($this,'admin_assets') );
		
		add_action( 'widgets_init', array($this,'register_widget_area') );
		add_action( 'wp_enqueue_scripts', array($this,'frontend_assets') );
		add_filter( 'wp_ajax_ns_slidebar_search', array($this,'get_json_search_results') );
		add_filter( 'wp_ajax_nopriv_ns_slidebar_search', array($this,'get_json_search_results') );
		add_action( 'wp_footer', array($this,'output_slidebar') );
	}
	
	/*********************************
	 * NOTICES & LOCALIZATION
	 */
	 
	 function setup_plugin(){
	 	// load options into class var so they're filled with defaults and available without calling get_option over and over
	 	$this->settings = wp_parse_args( (array)get_option($this->ns_plugin_ref), array(
			'trigger_width' => 120,
			'trigger_text' => 'Search',
			'trigger_img' => plugin_dir_url(__FILE__).'images/search-icon.png',
			'more_text' => 'View More Results&hellip;',
			'results_per_page' => 5,
			'excerpt_length' => 100,
			'percent_width' => 15,
			'min_width' => 300
		));
	 	// TODO: add translation dir and base pot file
	 	//load_plugin_textdomain( $this->ns_plugin_slug, false, $this->path."lang/" );
	 }

	function admin_assets($page){
	 	wp_register_style( $this->ns_plugin_slug.'-admin', plugins_url("css/admin.css",__FILE__), false, '1.0.0' );
	 	wp_register_script( $this->ns_plugin_slug.'-admin', plugins_url("js/admin.js",__FILE__), false, '1.0.0' );
		if( strpos($page, $this->ns_plugin_ref) !== false  ){
			wp_enqueue_style( $this->ns_plugin_slug.'-admin' );
			wp_enqueue_script( $this->ns_plugin_slug,'-admin' );
		}		
	}
	
	/**********************************
	 * SETTINGS PAGE
	 */
	
	function register_settings_fields() {
		add_settings_section( 
			$this->ns_plugin_ref.'_set_section', 	// ID used to identify this section and with which to register options
			$this->ns_plugin_name, 					// Title to be displayed on the administration page
			false, 									// Callback used to render the description of the section
			$this->ns_plugin_ref 					// Page on which to add this section of options
		);
		$fields = array(
			'trigger_width' => array( 'label'=>'Trigger Width', 'description'=> 'Width in px (without \'px\') for the trigger button in the upper right hand corner which will trigger showing the slidebar when clicked' ),
			'trigger_text' => array( 'label'=>'Trigger Text', 'description'=> 'Text to show in the upper right hand trigger button' ),
			'trigger_img' => array( 'label'=>'Trigger Image', 'description'=> 'URL to image to show to the right of the trigger text specified above' ),
			'more_text' => array( 'label'=>'More Text', 'description'=> 'Text shown in link to view more search results after the initial set is shown' ),
			'results_per_page' => array( 'label'=>'Results Per Page', 'description'=> 'Number of instant search results to be shown in slidebar before pressing "view more" button' ),
			'excerpt_length' => array( 'label'=>'Excerpt Length', 'description'=> 'Number of characters of content to be shown in the instant search results' ),
			'percent_width' => array( 'label'=>'Percentage Width', 'description'=> 'Width as a percentage (without % sign) that the slidebar should be of the window width when opened on the site' ),
			'min_width' => array( 'label'=>'Minimum Pixel Width', 'description'=> 'Width as a number of pixels (without \'px\') that the slidebar should always be regardless of window width' )
			
		);
		foreach( $fields as $field_name=>$field_data ){
			add_settings_field( 
				$this->ns_plugin_ref.'_'.$field_name,
				$field_data['label'],
				array( $this, 'show_settings_field' ),
				$this->ns_plugin_ref,
				$this->ns_plugin_ref.'_set_section',
				array(
					'field_name' => $field_name,
					'description' => isset($field_data['description'])? $field_data['description'] : false
				)
			);
		}
		register_setting( $this->ns_plugin_ref, $this->ns_plugin_ref );
	}	

	function show_settings_field($args){
		$field_name = $args['field_name'];
		$saved_value = isset( $this->settings[$field_name] )? $this->settings[$field_name] : "";
		echo '<input type="text" name="'.$this->ns_plugin_ref.'['.$field_name.']" value="'.$saved_value.'" /><br/>';
		if( $args['description'] ) echo '<p class="description">'.$args["description"].'</p>';
	}

	function register_settings_page(){
		add_submenu_page(
			'options-general.php',								// Parent menu item slug	
			__($this->ns_plugin_name, $this->ns_plugin_name),	// Page Title
			__($this->ns_plugin_menu, $this->ns_plugin_name),	// Menu Title
			'manage_options',									// Capability
			$this->ns_plugin_ref,								// Menu Slug
			array( $this, 'show_settings_page' )				// Callback function
		);
	}
	
	function show_settings_page(){
		?>
		<div class="wrap">
			
			<h2><?php $this->plugin_image( 'banner.png', __('NS Slidebar') ); ?></h2>
			
			<!-- BEGIN Left Column -->
			<div class="ns-col-left">
				<form method="POST" action="options.php" style="width: 100%;">
					<?php settings_fields($this->ns_plugin_ref); ?>
					<?php do_settings_sections($this->ns_plugin_ref); ?>
					<?php submit_button(); ?>
				</form>
			</div>
			<!-- END Left Column -->
						
			<!-- BEGIN Right Column -->			
			<div class="ns-col-right">
				<h3>Thanks for using <?php echo $this->ns_plugin_name; ?></h3>
				<?php ns_sidebar::widget( 'subscribe' ); ?>
				<?php ns_sidebar::widget( 'rate', array('Has this plugin helped you out? Give back with a 5-star rating!','ns-slidebar') ); ?>
				<?php ns_sidebar::widget( 'donate' ); ?>
				<?php ns_sidebar::widget( 'featured'); ?>
				<?php ns_sidebar::widget( 'links', array('ns-slidebar') ); ?>
				<?php ns_sidebar::widget( 'support' ); ?>
			</div>
			<!-- END Right Column -->
				
		</div>
		<?php
	}
	
	
	/*************************************
	 * FUNCTIONALITY
	 */
	
	function frontend_assets(){
	 	wp_enqueue_style( $this->ns_plugin_slug, plugins_url("css/frontend.css",__FILE__), false, $this->ns_plugin_version );
	 	wp_enqueue_script( $this->ns_plugin_slug, plugins_url("js/frontend.js",__FILE__), array('jquery'), $this->ns_plugin_version );
		wp_localize_script( $this->ns_plugin_slug, $this->ns_plugin_ref, array(
			'ajaxurl' => admin_url('/admin-ajax.php'),
			'results_per_page' => $this->settings['results_per_page'],
			'trigger_width' => preg_replace('/[^\d]/','',$this->settings['trigger_width']),
			'percent_width' => preg_replace('/[^\d]/','',$this->settings['percent_width']),
			'min_width' => preg_replace('/[^\d]/','',$this->settings['min_width'])
		));
	} 
	
	function register_widget_area(){
		register_sidebar( array(
			'name' => 'NS Slidebar Area',
			'id' => 'ns_slidebar',
			'before_widget' => '<div class="ns-slidebar-widget">',
			'after_widget' => '</div>',
			'before_title' => '<h2 class="ns-slidebar-widget-title">',
			'after_title' => '</h2>',
		));		
	}
	
	function get_json_search_results(){
		// get search results and allow filtering of query vars for customization
		$results = get_posts(apply_filters('ns_slidebar_search_query',array(
			'post_type' => 'any',
			'posts_per_page' => -1,
			's' => isset($_REQUEST['s'])? $_REQUEST['s'] : '',
			'offset' => isset($_REQUEST['offset'])? $_REQUEST['offset'] : 0,
		)));
		// add permalink to each one so js on frontend can output links
		// also have another per item filter so excerpts could be customized, etc
		foreach( $results as &$result ){
			$result->permalink = get_permalink($result->ID);
			$result->formatted_post_type = get_post_type_object($result->post_type)->labels->singular_name;
			$result->short_content = substr( strip_tags( do_shortcode( $result->post_content ) ), 0, $this->settings['excerpt_length'] ).'&hellip;';
			$result = apply_filters( 'ns_slidebar_search_item', $result );
		}
		// return in json
		header('Content-type: application/json');
		echo json_encode($results);
		exit;
	}

	function output_slidebar(){
		if( apply_filters('ns_slidebar_do_output',true) ){
			include( apply_filters('ns_slidebar_template', plugin_dir_path(__FILE__).'templates/slidebar.php' ) );
		}
	}
	
	/*************************************
	 * UITILITY
	 */
	 
	 function plugin_image( $filename, $alt='', $class='' ){
	 	echo "<img src='".plugins_url("/images/$filename",__FILE__)."' alt='$alt' class='$class' />";
	 }
	
}

$ns_slidebar = new NS_Slidebar();
