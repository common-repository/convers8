<?php
define('CONVERS8_FILE_PATH', dirname(__FILE__));
define('CONVERS8_DIR_NAME', basename(CONVERS8_FILE_PATH));

add_action('admin_menu', 'convers8_menu');

function convers8_menu() {
	add_options_page(__('Convers8 options'), 'Convers8', 'manage_options', 'convers8-options', 'convers8_options');
}

function convers8_options() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	
	$option_names = array('convers8_url', 'convers8_websiteid', 'convers8_secret');
	$options = array();
	foreach ($option_names as $option_name) {
		$options[$option_name] = get_option($option_name);
	}
	
	if( isset($_POST['convers8_hidden']) && $_POST['convers8_hidden'] == 'Y' ) {
		foreach ($options as $option_name => $option) {
			if (isset($_POST[$option_name])) {
				$options[$option_name] = $_POST[$option_name];
				if ($option_name == "convers8_url") {
					// Add trailing slash
					if (substr($options['convers8_url'], 6) != 'http://') {
						$options['convers8_url'] = 'http://' . $options['convers8_url'];
					}
					
					if (substr($options['convers8_url'], -1) != '/') {
						$options['convers8_url'] .= '/';
					}
				}
				update_option($option_name, $options[$option_name]);
			}
		}
		
		?>
		<div class="updated"><p><strong><?php _e('Settings saved'); ?></strong></p></div>
		<?php
	}
	?>
	
	<div class="wrap">
	
		<h2><?php _e('Convers8 plugin settings', 'convers8'); ?></h2>
		
		<form name="convers8_settings_form" method="post" actin="">
			<input type="hidden" name="convers8_hidden" value="Y" />


			<p>Domain:&nbsp;
			<input type="text" name="convers8_url" value="<?php echo $options['convers8_url']; ?>" size=30/> (e.g. http://test.engine.convers8.eu/)
			</p>
			<p>Website ID:
			<input type="text" name="convers8_websiteid" value="<?php echo $options['convers8_websiteid']; ?>" />
			</p>	
			<p>API-key:
			<input type="text" name="convers8_secret" value="<?php echo $options['convers8_secret']; ?>" />
			</p>					
			<hr />
			
			<p class="submit">
			<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
			</p>
			
		</form>
	
	</div>
	<?php 
}