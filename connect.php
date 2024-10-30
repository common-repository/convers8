<?php 
require_once(ABSPATH . WPINC . '/registration.php');

define(CONVERS8_LOGIN_FAILED, 0);
define(CONVERS8_LOGIN_SUCCESS, 1);
define(CONVERS8_LOGGED_IN, 2);

$result = array();


function convers8_login() {	
	global $result;

	$result = array("result" => CONVERS8_LOGIN_FAILED,
					"message" => "");
	
	if (!isset($_POST["convers8"])) {
		signon_failed();
	}
	
	$convers8 = $_POST["convers8"];
	
	$convers8_user_id = $convers8["user"]["id"];
	$timestamp = $convers8["timestamp"];
	$nonce = $convers8["nonce"];	
	$convers8_secret = get_option("convers8_secret");	
	$signature = $convers8["signature"];
	
	$mySig = sha1($convers8_user_id . $timestamp . $nonce . $convers8_secret);
	if ($mySig == $signature) {
		// signature is valid. we assume that the user is successfull authenticated at convers8.
		
		// check if the user is already logged in on wordpress.		
		if (is_user_logged_in()) {
			// user is logged in on wordpress, send result 2 for skipping the extra page refresh
			sigon_succeed(CONVERS8_LOGGED_IN, $convers8_user_id);
		}		
	
		// use the Convers8 user ID as the Wordpress username
		$user = username_exists($convers8_user_id); 
				
		$wp_user_id = $user;
		if (!isset($user)) {
			$wp_user_id = register_wordpress_user($convers8["user"], $convers8_secret);
		}

		// try to sign on the user in Wordpress
		$wp_user = null;
		if (isset($wp_user_id) && !$wp_user_id instanceof WP_Error) {			
			$wp_user = wp_signon(
				array(
					'user_login' => $convers8_user_id, 
					'user_password' => md5($convers8_user_id . $convers8_secret)
				), 
				false
			);
			$result["message"] .= "Na de sign on";
			// unset the cached avatar to ensure periodical reloads
			delete_metadata('user', $wp_user->ID, 'convers8_avatar_url');					
		}
		
		if (is_wp_error($wp_user)) {			
			signon_failed();
		}		
	} else {
		// signature is not valid ... delete cookies and log the user out ...
		signon_failed();		
	}	

	// signon succeed, log the user in.
	sigon_succeed(1, $convers8_user_id);	
}

function signon_failed() {
	setcookie('convers8-userid', -1, time()-3600, '/');
	output_json_results();
	wp_logout();
}

function sigon_succeed($status = 1, $convers8_user_id) {
	global $result;
	
	setcookie('convers8-userid', $convers8_user_id, time()+3600, '/');
	$result["result"] = $status;
	
	output_json_results();
}

/**
 * Output login result in JSON format.
 */
function output_json_results() {
	global $result;
	
	print json_encode($result);
		
	die();
}

/**
 * Register Convers8 user in Wordpress
 * 
 * @param array $convers8_user 
 * @param string $secret 
 * @return int $wp_user_id
 */
function register_wordpress_user($convers8_user, $secret) {
	$wp_user_id = wp_insert_user(array(
		'user_pass' => md5($convers8_user["id"] . $secret),
		'user_login' => $convers8_user["id"],
		// make sure no illegal characters occur in user_nicename, since it is also in the member's URL
		'user_nicename' => sanitize_title_with_dashes($convers8_user["firstName"] . '-' . $convers8_user["lastName"]),
		'display_name' => $convers8_user["firstName"] . ' ' . $convers8_user["lastName"],
		'nickname' => $convers8_user["firstName"] . ' ' . $convers8_user["lastName"],
		'first_name' => $convers8_user["firstName"],
		'last_name' => $convers8_user["lastName"],
		'user_email' => $convers8_user["id"] . '-' . get_option('convers8_websiteid') . '-' . md5($convers8_user["id"] . $secret) . '@users.convers8.eu'
	));	

	return $wp_user_id;
}
?>