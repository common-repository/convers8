<?php
add_filter('get_avatar', 'convers8_get_avatar_filter', 20, 5);
add_filter('bp_get_loggedin_user_avatar', 'convers8_loggedin_avatar_filter');
add_filter('bp_get_displayed_user_avatar', 'convers8_displayed_avatar_filter');
add_filter('bp_get_member_avatar', 'convers8_member_avatar_filter');

function convers8_get_avatar($args) {
	
	// Set the default variables array
	$defaults = array(
		'item_id'		=> false,
		'object'		=> 'user',		// user/group/blog/custom type (if you use filters)
		'type'			=> 'thumb',		// thumb or full
		'width'			=> false,		// Custom width (int)
		'height'		=> false,		// Custom height (int)
		'class'			=> 'avatar',	// Custom <img> class (string)
		'alt'			=> __( 'Avatar Image', 'buddypress' ),	// Custom <img> alt (string)
		'email'			=> false,		// Pass the user email (for gravatar) to prevent querying the DB for it
		'html'			=> true			// Wrap the return img URL in <img />
	);

	// Compare defaults to passed and extract
	$params = wp_parse_args( $args, $defaults );
	$username = get_userdata($params['item_id']);
	if ($username === false) {
		return false;
	}
	$username = $username->user_login;
	if (!ctype_digit($username)) {
		// users created via convers8 have an all-numeric username, so don't bother
		return false;
	}
	
	$avatar_url = get_metadata($params['object'], $params['item_id'], 'convers8_avatar_url');
	if (count($avatar_url)) {
		// avatar URL is cached, use the first (and only) one
		$avatar_url = $avatar_url[0];
	} else {
		// don't know a URL for the avatar, see if we can find one
		$json = file_get_contents(get_option('convers8_url') . 'api-1/user/?user=' . $username . 
				'&website=' . get_option('convers8_websiteid'));
		$data = json_decode($json);
	
		if (!isset($data->user->picture)) {
			return false;
		}
		$avatar_url = $data->user->picture;
		
		add_metadata($params['object'], $params['item_id'], 'convers8_avatar_url', $avatar_url);
	}
	
	
	
	// Return it wrapped in an <img> element
	if ( true === $params['html'] ) {		
		// Add an identifying class to each item
		$class = $params['class'] . ' ' . $params['object'] . '-' . $params['item_id'] . '-avatar';
		
		// Set avatar width

		$html_width = ($params["width"] ? " width='{$params["width"]}'" : '');
		$html_height = ($params["height"] ? " height='{$params["height"]}'" : '');
		
		
		
		return '<img src="' . $avatar_url . '" alt="' . $params['alt'] . '" class="' . $class . '"' . $html_width . $html_height . ' />';
	// ...or only the URL
	} else {
		return $avatar_url;
	}
	
	return false;
}

function convers8_loggedin_avatar_filter( $avatar ) {
	global $current_user;
	
	// Let Convers8 handle the fetching of the avatar
	$convers8_avatar = convers8_get_avatar( array( 'item_id' => $current_user->ID, 'width' => 50, 'height' => 50));
	// If Convers8 found an avatar, use it. If not, use the result of get_avatar
	return ( !$convers8_avatar ) ? $avatar : $convers8_avatar;
}

function convers8_displayed_avatar_filter( $avatar ) {
	global $bp;

	$convers8_avatar = convers8_get_avatar( array( 'item_id' => $bp->displayed_user->id));
	return ( !$convers8_avatar ) ? $avatar : $convers8_avatar;
}

function convers8_member_avatar_filter( $avatar ) {
	global $members_template;
	
	
	
	$convers8_avatar = convers8_get_avatar( array('item_id' => $members_template->member->id));
	return ( !$convers8_avatar ) ? $avatar : $convers8_avatar;
}

/**
 * Attempts to filter get_avatar function and let Convers8 have a go
 * at finding an avatar that is linked from a social network.
 *
 * @global array $authordata
 * @param string $avatar The result of get_avatar from before-filter
 * @param int|string|object $user A user ID, email address, or comment object
 * @param int $size Size of the avatar image (thumb/full)
 * @param string $default URL to a default image to use if no avatar is available
 * @param string $alt Alternate text to use in image tag. Defaults to blank
 * @return <type>
 */
function convers8_get_avatar_filter( $avatar, $user, $size, $default, $alt ) {
	// If passed an object, assume $user->user_id
	
	if ( is_object( $user ) )
		$id = $user->user_id;

	// If passed a number, assume it was a $user_id
	else if ( is_numeric( $user ) )
		$id = $user;

	// If passed a string and that string returns a user, get the $id
	else if ( is_string( $user ) && ( $user_by_email = get_user_by_email( $user ) ) )
		$id = $user_by_email->ID;

	// If somehow $id hasn't been assigned, return the result of get_avatar
	if ( empty( $id ) )
		return !empty( $avatar ) ? $avatar : $default;

	// Let Convers8 handle the fetching of the avatar
	$convers8_avatar = convers8_get_avatar( array( 'item_id' => $id, 'width' => $size, 'height' => $size, 'alt' => $alt ) );
	// If Convers8 found an avatar, use it. If not, use the result of get_avatar
	return ( !$convers8_avatar ) ? $avatar : $convers8_avatar;
}
