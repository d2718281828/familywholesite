<?php
/*
Plugin Name: Admin Page
Plugin URI: https://flipsidegroup.com/
Description: Provides a basic admin page for system settings and documentation. The page can be tabbed and have subpages.
Version: 0.1
Author: Derk Storkey
Author URI:
License: GPLv2 or later
Text Domain: gsgdomain
*/
function admin_page_class_registration($class){
    $bits = explode("\\",$class);
    if ($bits[0]!="AdminPage") return;

    array_splice($bits, 1, 0, "class");
    require(dirname(__FILE__)."/".implode("/",$bits).".php");
}

spl_autoload_register('admin_page_class_registration');

function admin_page_queue_style(){
    wp_enqueue_style("admin_page_style",plugin_dir_url(__FILE__)."AdminPage/css/AdminPage.css",[], "v1.0");
}
add_action("admin_init","admin_page_queue_style");
 ?>
