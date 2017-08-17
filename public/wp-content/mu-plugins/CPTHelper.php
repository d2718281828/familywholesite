<?php
/*
Plugin Name: CPT Helper
Plugin URI: https://flipsidegroup.com/
Description: Provides classes to help with Custom Post Types. This should be in mu-plugins
Version: 0.1
Author: Derk Storkey
Author URI:
License: GPLv2 or later
Text Domain: gsgdomain
*/
function cpt_helper_class_registration($class){
    $bits = explode("\\",$class);
    if ($bits[0]!="CPTHelper") return;

    array_splice($bits, 1, 0, "class");
    require(dirname(__FILE__)."/".implode("/",$bits).".php");
}

spl_autoload_register('cpt_helper_class_registration');

// Load helper functions
require_once(dirname(__FILE__)."/CPTHelper/class/serviceFunctions.php");
?>
