<?php
/*
Plugin Name: Convers8
Plugin URI: http://convers8.eu/
Description: Provides social integration for your blog
Version: 0.01
Author: Convers8
Author URI: http://convers8.eu/
License: GPLv2
*/

// TODO: update the above information

define ('CONVERS8_DIR', dirname(__FILE__));
define ('CONVERS8_VERSION', '0.09.0'); // The version of Convers8 scripts and styles to use
define ('CONVERS8_PLUGIN_VERSION', '0.1.3'); // The version number of this plugin

include_once(CONVERS8_DIR . '/options.php');
include_once(CONVERS8_DIR . '/avatars.php');
include_once(CONVERS8_DIR . '/connect.php');

add_action('plugins_loaded', 'convers8_pluginsLoaded');
add_action('wp_logout', 'convers8_logout');
add_action('init', 'convers8_init');
add_action('xprofile_setup_nav', 'convers8_setup_bp_nav');
add_action('bp_before_member_header_meta', 'convers8_bp_member_header');
add_action('login_form', 'convers8_login_buttons');
add_action('login_head','convers8_styles');
add_action('wp_ajax_nopriv_convers8_login', 'convers8_login');
add_action('wp_ajax_convers8_login', 'convers8_login');
add_action('wp_ajax_convers8_logout', 'wordpress_logout');

add_filter("the_content", "convers8_content");

function get_page_url() {
	return $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
}

function convers8_content($content) {
//	$new_content .= "<a class='convers8_share' onclick=\"javascript:convers8_opendeelbox();\" href=\"javascript:void(0);\"></a>";
	return $content;
}

add_option('convers8_url', 'http://engine.convers8.eu/');

function convers8_init() {	
	if (!is_admin()) {
		// load the proper version of jquery
		wp_deregister_script("jquery");
		wp_register_script("jquery", WP_PLUGIN_URL ."/".CONVERS8_DIR_NAME."/jquery-1.5.3.js");
	    		
		wp_enqueue_script('jquery-cookie', WP_PLUGIN_URL ."/".CONVERS8_DIR_NAME."/jquery-cookie.js",
				array('jquery'), '1.0');
		
		// load convers8 script and style information
		wp_enqueue_script('convers8_js',
				'http://static.cnvr.st/js/convers8-ui-' . CONVERS8_VERSION . '.js',
				array('jquery',  'convers8_wordpress_js'),
				CONVERS8_VERSION);
				
		wp_localize_script('convers8_js', 'convers8', array(
				'url' => get_option('convers8_url'),
				'website' => get_option('convers8_websiteid')
		));
		
		wp_enqueue_script('convers8_wordpress_js', WP_PLUGIN_URL ."/".CONVERS8_DIR_NAME."/convers8.js",
				array('jquery'), CONVERS8_PLUGIN_VERSION);
				

		wp_enqueue_style('convers8_css', 'http://static.cnvr.st/css/convers8-' . CONVERS8_VERSION . '.css', array(), CONVERS8_VERSION);
		wp_enqueue_style('convers8_wordpress_css', WP_PLUGIN_URL . '/' . CONVERS8_DIR_NAME . '/convers8.css',
				array('convers8_css'), CONVERS8_PLUGIN_VERSION);
	}
}

function convers8_logout() {	
	setcookie('convers8-logout', true, time()+3600, '/');	
}

function wordpress_logout() {
	wp_logout();
}

function convers8_pluginsLoaded() {
	register_sidebar_widget('Convers8 connect', 'convers8Connect_createSidebarWidget');
	register_sidebar_widget('Convers8 share', 'convers8Share_createSidebarWidget');
}

// sidebar widget contents
function convers8Connect_createSidebarWidget($args) {
	extract($args);
	
	echo $before_widget;
	echo $before_title . $after_title;
	
	if (is_user_logged_in() && $_COOKIE["convers8-userid"] > 0) {		
		$json = file_get_contents(get_option('convers8_url') . 'api-1/user/?user=' . $_COOKIE["convers8-userid"] . '&website=' . get_option('convers8_websiteid'));
				
		$data = json_decode($json);		
		
		print "<h3>Welkom, " . $data->user->firstName . " " . $data->user->lastName . "</h3>";
			
		print create_convers8_userpic($data->user);
		
		print "<a style='display:block;clear:both;' href='javascript:convers8_logout()'>Uitloggen</a>";
	} else {
	?>
	<div id='convers8_default_wp_login'>	
		<h3>Login met je sociale profiel</h3>	
		<a href='javascript:convers8_login("FaceBook")'>
			<img alt="Login met Facebook" src="<?php print WP_PLUGIN_URL ."/".CONVERS8_DIR_NAME. "/img/icon_FaceBook_large.png" ?>">
		</a>
		<a href='javascript:convers8_login("Hyves");'>
			<img alt="Login met Hyves" src="<?php print WP_PLUGIN_URL ."/".CONVERS8_DIR_NAME. "/img/icon_Hyves_large.png" ?>">
		</a>
		<a href='javascript:convers8_login("LinkedIn");'>
			<img alt="Login met LinkedIn" src="<?php print WP_PLUGIN_URL ."/".CONVERS8_DIR_NAME. "/img/icon_LinkedIn_large.png" ?>">
		</a>
		<a href='javascript:convers8_login("Twitter");'>
			<img alt="Login met Twitter" src="<?php print WP_PLUGIN_URL ."/".CONVERS8_DIR_NAME. "/img/icon_Twitter_large.png" ?>">
		</a>
		<br /><br />
	</div>	
	
	<?php 		
	}
	
	echo $after_widget;
}

// sidebar widget contents
function convers8Share_createSidebarWidget($args) {
	extract($args);
	
	echo $before_widget;
	echo $before_title . __('Gedeeld door', 'Convers8') . $after_title;
	
	$url = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
		
	$json = file_get_contents(get_option('convers8_url') . 'api-1/share/shared/?website=' . get_option('convers8_websiteid') . '&uri='.$url);
	$data = json_decode($json);	

	if (count($data->users) == 0) {
		print "Nobody shared this page. Are you the first one?";
	} else {
		$friends = array();
		$others = array();
		$shared = false;
		
		var_dump($data);
		
		foreach($data->users as $user) {
			if ($user->id == $_COOKIE["convers8-userid"]) {
				$shared = true;
				continue;				
			}
			
			if ($user->friend === true) {
				$friends[] = $user;
				continue;
			}
			$others[] = $user;
		}		
		
		if (count($friends) > 0) {	
			print "<h2>Friends</h2>";		
			foreach($friends as $user) {
				print create_convers8_userpic($user);
			}	
		} else {
			print "<p>None of your friends shared this page. Are you the first?</p>";
		}
		
		print "<br />";
		
		if (count($others) > 0) {
			print "<h2>Anderen</h2>";
			foreach($others as $user) {
				print create_convers8_userpic($user);
			}
		}
		
		
		
		// print de poppetjes!
	}	
	?>
		
	<?php
	echo $after_widget;
}

function create_convers8_userpic($user) {
	
	$content = "<div class='convers8_userpic'>";
	$content .= "<div class='convers8_attached_networks'>";
					
	

	foreach($user->networks as $network) {
		
		$content .= "<a href='".$network->url."' class='convers8_icon_small convers8_".$network->network."' target='_blank' title='".$network->network."'></a>";

				
	}
	$content .= "</div>";
	
	$content .= "<img class='convers8_avatar' src=".$user->picture." />";
	$content .= "<span class='convers8_colors_small'></span>";
	$content .= "</div>";
	
	return $content;
}

function convers8_setup_bp_nav() {
	// allow the user to manage their networks via the BuddyPress profile page, if BP is installed
	global $bp;
	bp_core_new_subnav_item(array(
			'name' => __('Netwerken beheren', 'Convers8'),
			'slug' => 'convers8-networks',
			'parent_url' => $bp->loggedin_user->domain . $bp->profile->slug . '/',
			'parent_slug' => $bp->profile->slug,
			'screen_function' => 'convers8_manage_networks'
	));
}

function convers8_manage_networks() {
	if ( !bp_is_my_profile() ) {
		return false;
	}
	bp_core_load_template( 'members/single/convers8-networks' );
}

function convers8_bp_member_header() {
	global $bp; 
	?>
		<div class="convers8_wp_enabled_networks" name="<?php echo $bp->displayed_user->userdata->user_login; ?>"></div>
	<?php
}

function convers8_login_buttons() {
	?>
	<div id='convers8_default_wp_login'>
		<label>Or use your social network to login:</label><br />
		<a href='javascript:convers8_login("FaceBook", true);'>
			<img alt="Login with Facebook" src="<?php print WP_PLUGIN_URL ."/".CONVERS8_DIR_NAME. "/img/icon_FaceBook_large.png" ?>">
		</a>
		<a href='javascript:convers8_login("Hyves", true);'>
			<img alt="Login with Hyves" src="<?php print WP_PLUGIN_URL ."/".CONVERS8_DIR_NAME. "/img/icon_Hyves_large.png" ?>">
		</a>
		<a href='javascript:convers8_login("LinkedIn", true);'>
			<img alt="Login with LinkedIn" src="<?php print WP_PLUGIN_URL ."/".CONVERS8_DIR_NAME. "/img/icon_LinkedIn_large.png" ?>">
		</a>
		<a href='javascript:convers8_login("Twitter", true);'>
			<img alt="Login with Twitter" src="<?php print WP_PLUGIN_URL ."/".CONVERS8_DIR_NAME. "/img/icon_Twitter_large.png" ?>">
		</a>
		<br /><br />
	</div>
	<?php 
}

function convers8_styles() {
	echo '<link rel="stylesheet" href="' . WP_PLUGIN_URL . '/' . CONVERS8_DIR_NAME . '/convers8.css" type="text/css" />';
	echo '<link rel="stylesheet" href="http://static.cnvr.st/css/convers8-' . CONVERS8_VERSION . '.css" type="text/css" />';
}