<?php
/*
Plugin Name: Login widget
Plugin URI: 
Description: Provides a login/out link in the nav bar, replaces item with class login-widget
Author: Derek Storkey
Author URI:
Version: 0.1
Text Domain: loginwidget
License:
*/

function login_widget_the_widget(){
	$redirect = home_url(add_query_arg(array(),$wp->request));
	if (is_user_logged_in()){
		$me = wp_get_current_user();
		$m = $me->user_firstname." " ;
		$m.= '<a href="'.wp_logout_url( $redirect ).'">Logout</a>';
		return "Logout";
	}
	$m = '<a href="'.wp_login_url( $redirect ).'">Login</a>';
	return "Login Link";
}

add_filter( 'wp_nav_menu_items', 'login_widget_menu_hook', 10, 2 );

function login_widget_menu_hook ( $items, $args ) {
    if ($args->theme_location == 'primary') {
        $items .= '<li class="login-widget-login-item">'.login_widget_the_widget().'</li>';
    }
    return $items;
}