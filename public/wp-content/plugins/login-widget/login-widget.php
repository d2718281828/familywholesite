<?php
/*
Plugin Name: Login widget
Plugin URI: 
Description: Provides a login/out link in the nav bar, adds to the end
Author: Derek Storkey
Author URI:
Version: 0.1
Text Domain: loginwidget
License:
*/
// TODO change so that it looks for an item with text '[login-widget]' and replaces just that item
function login_widget_current_url($uri=false) { 
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; // an s if it is https 
	$p=explode("/",strtolower($_SERVER["SERVER_PROTOCOL"])); 
	$protocol = $p[0].$s; 
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
	if ($s && $_SERVER["SERVER_PORT"]==443) $port = ""; 
	$z=$protocol."://".$_SERVER['SERVER_NAME'].$port; 
	if ($uri) return $z.$_SERVER['REQUEST_URI']; 
	return $z; 
}
function login_widget_the_widget(){
	global $wpadmin_tab_name;
	//$redirect = login_widget_current_url(true);
	$redirect = home_url('/wp-admin');
	if (is_user_logged_in()){
		$me = wp_get_current_user();
		$m = '<a href="'.wp_logout_url( $redirect ).'" target="'.($wpadmin_tab_name ?: '_blank').'">Logout '.$me->user_firstname.'</a>';
		return $m;
	}
	$m = '<a href="'.wp_login_url( ).'">Login</a>';
	return $m;
}

add_filter( 'wp_nav_menu_items', 'login_widget_menu_hook', 10, 2 );

function login_widget_menu_hook ( $items, $args ) {
    //if ($args->theme_location == 'primary') {
        $items .= '<li class="login-widget-login-item">'.login_widget_the_widget().'</li>';
    //}
    return $items;
}
