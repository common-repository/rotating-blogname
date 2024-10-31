<?php 
/*
Plugin Name: Rotating Blogname
Plugin URI: http://blog.bigcircle.nl/about/wordpress-plugins/
Description: Changes your blogname dynamicaly based on a list of blog titles. There are options to set how and how often the blogname should change.
Version: 1.3.1
Author: Maarten Swemmer
Author URI: http://blog.bigcircle.nl
License: GPL2
*/

// taking care of translations
$plugin_dir = plugin_basename( dirname( __FILE__ ) .'/languages' );
load_plugin_textdomain( 'rotating-blogname', null, $plugin_dir );


// Hook for adding admin menus
if ( is_admin() ){ // admin actions
  add_action( 'admin_menu', 'rtt_blogname_add_options_page' );
  add_action( 'admin_init', 'rtt_blogname_register_mysettings' );
} 
else {
  // non-admin enqueues, actions, and filters
}

// Activation action
function rtt_blogname_activation(){
	add_option('rtt_blogname_list', get_bloginfo( 'name' ));
	add_option('rtt_blogname_behavior', 'random');
	add_option('rtt_blogname_offset', 'Yz'); // date() 'year'+'day of the year' as either seed for the random generator or index for the list
	add_option('rtt_blogname_startdate', '');
	add_option('rtt_blogname_repeat', 'continue');
	
}
register_activation_hook( __FILE__, 'rtt_blogname_activation' );

//Uninstalling Action
function rtt_blogname_uninstall(){
	delete_option('rtt_blogname_list');	
	delete_option('rtt_blogname_behavior');
	delete_option('rtt_blogname_offset');
	delete_option('rtt_blogname_startdate');
	delete_option('rtt_blogname_repeat');
	
}
register_deactivation_hook( __FILE__, 'rtt_blogname_uninstall' );

function rtt_blogname_register_mysettings() { // whitelist options
	register_setting( 'rtt-blogname-settings', 'rtt_blogname_list' );
	register_setting( 'rtt-blogname-settings', 'rtt_blogname_behavior' );
	register_setting( 'rtt-blogname-settings', 'rtt_blogname_offset' );
	register_setting( 'rtt-blogname-settings', 'rtt_blogname_startdate' );
	register_setting( 'rtt-blogname-settings', 'rtt_blogname_repeat' );	
}

// action function for above hook
function rtt_blogname_add_options_page() 
{
    // Add a new submenu under Settings:
    add_options_page(__('Rotating Blogname','rotating-blogname'), __('Rotating Blogname','rotating-blogname'), 'manage_options', 'rotating-blogname-settings', 'rtt_blogname_options_page');
}


// Return a random title 
function rtt_blogname_title(){
		
	$lines = explode("\n", get_option('rtt_blogname_list'));
	
	$f = get_option('rtt_blogname_offset'); //frequency
	if ($f == 'U') { $interval = 1;} // one second
	if ($f == 'YH') { $interval = 3600;} // one hour
	if ($f == 'Yz') { $interval = 3600*24;} // one day
	if ($f == 'YW') { $interval = 3600*24*7;} // one week
	if ($f == 'Ym') { $interval = 3600*24*30;} // 30 days
	
	
	if (get_option('rtt_blogname_behavior') == 'random')
	{
		srand(date(get_option('rtt_blogname_offset'))); // in this case the srand function will take care of the interval: the seed will be identical during an interval
		return $lines[array_rand($lines)];
	}
	else 
	{
		if (($timestamp = strtotime(get_option('rtt_blogname_startdate'))) === false)
		{
			$index = round($index / $interval);
		}
		else
		{
			if ($timestamp > time()) { $index = 0; }
			else { $index = round((time() - $timestamp )/ $interval); }
		}
		
		if ((get_option('rtt_blogname_repeat') == 'once') && ($index > sizeof($lines)))
		{
			$index = sizeof($lines) - 1;
		}
		else
		{
			$index = fmod($index,sizeof($lines));
		}		return $lines[$index];
	}
}
add_filter('bloginfo', 'rtt_blogname', 1, 2);

// Filter for wordpress that redefines output of bloginfo('name')
function rtt_blogname($result='', $show='') {
	
	switch ($show) {
		case 'name':
			$result = rtt_blogname_title();
			break;
		default: 
	}
	return $result;
}

// Options Page
function rtt_blogname_options_page() 
{
	if (!current_user_can( 'manage_options' ) ) {
		wp_die ( __( 'You do not have sufficient permissions to access this page' ) );
	}
	$list = get_option('rtt_blogname_list');
	$behavior = get_option('rtt_blogname_behavior');
	$offset = get_option('rtt_blogname_offset');
	$startdate = get_option('rtt_blogname_startdate');
	$repeat = get_option('rtt_blogname_repeat');
	?>
	<div class="wrap">
	<h2> <?php _e('Rotating Blogname Settings','rotating-blogname'); ?> </h2>

	<form method="post" action="options.php">
	<?php settings_fields( 'rtt-blogname-settings' ); ?>
	<table class="form-table">
		<tr valign="top">
		<th scope="row"><?php _e('Provide a list of titles you want to see as the name of your blog:','rotating-blogname'); ?></th>
		<td><textarea name="rtt_blogname_list" class="large-text code" rows="15"><?php echo $list; ?></textarea></td>
		</tr>
	
	     
        <tr valign="top">
        <th scope="row"><?php _e('How should your blog name change?','rotating-blogname'); ?></th>
        <td><input type="radio" name="rtt_blogname_behavior" value="random" <?php if ($behavior == 'random') echo 'CHECKED'; ?> /><?php _e('Random','rotating-blogname'); ?><br />
			<input type="radio" name="rtt_blogname_behavior" value="followlist" <?php if ($behavior == 'followlist') echo 'CHECKED'; ?> /><?php _e('Following the list','rotating-blogname'); ?></td>
        </tr>

        <tr valign="top">
        <th scope="row"><?php _e('How often should it change?','rotating-blogname'); ?></th>
        <td><input type="radio" name="rtt_blogname_offset" value="U" <?php if ($offset == 'U') echo 'CHECKED'; ?> /><?php _e('Completely random','rotating-blogname'); ?><br />
			<input type="radio" name="rtt_blogname_offset" value="YH" <?php if ($offset == 'YH') echo 'CHECKED'; ?> /><?php _e('Every hour','rotating-blogname'); ?><br />			<input type="radio" name="rtt_blogname_offset" value="Yz" <?php if ($offset == 'Yz') echo 'CHECKED'; ?> /><?php _e('Every day','rotating-blogname'); ?><br />
			<input type="radio" name="rtt_blogname_offset" value="YW" <?php if ($offset == 'YW') echo 'CHECKED'; ?> /><?php _e('Every week','rotating-blogname'); ?><br />
			<input type="radio" name="rtt_blogname_offset" value="Ym" <?php if ($offset == 'Ym') echo 'CHECKED'; ?> /><?php _e('Every 30 days','rotating-blogname'); ?>
		</td>
        </tr>

		<tr valign="top">
        <th scope="row"><?php _e('If following the list, at which time/date should it start?','rotating-blogname'); ?></th>
        <td><input type="text" name="rtt_blogname_startdate" value="<?php echo $startdate; ?>" /><br />
			<?php _e('The next blogname is determined based on your webserver\'s date/time relative to the given date/time. The current server date/time is ','rotating-blogname'); echo date('Y-m-d H:i').'.<br />'."\n"; _e('Enter a date in the format YYYY-MM-DD HH:MM. If you would like to have the list start at the top from now on, copy-paste the current date/time.','rotating-blogname');  ?></td>
        </tr>
		
		<tr valign="top">
        <th scope="row"><?php _e('If following the list, would you like to repeat the list or stop at the end of the list?','rotating-blogname'); ?></th>
        <td><input type="radio" name="rtt_blogname_repeat" value="once" <?php if ($repeat == 'once') echo 'CHECKED'; ?> /><?php _e('Rotating should stop at the last blogname.','rotating-blogname'); ?><br />
			<input type="radio" name="rtt_blogname_repeat" value="continue" <?php if ($repeat == 'continue') echo 'CHECKED'; ?> /><?php _e('The blogname should continue to rotate.','rotating-blogname'); ?></td>
        </tr>

		
	</table>
	
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="page_options" value="rtt_blogname_list" />	

	<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes','rotating-blogname'); ?>" /></p>

	</form>
	</div>
	<?php
}
?>