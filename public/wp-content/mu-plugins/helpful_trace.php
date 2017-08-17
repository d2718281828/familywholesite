<?php
/*
Plugin Name: Helpful Trace Function
Plugin URI: https://flipsidegroup.com/
Description: Adds traceit() function, and TRACEIT define. Just for whilst debugging. traces to file application.trace in uploads directory
Version: 0.1
Author: Derk Storkey
Author URI:
License: GPLv2 or later
Text Domain: gsgdomain
*/
// to disable it set TRACEIT to false.
define("TRACEIT",false);

function traceit($m){
    return;
    error_log($m);
    $dir = wp_upload_dir();
    $tracefile = $dir["basedir"]."/application.trace";
    $logfile = fopen($tracefile, "a");
    fwrite($logfile,"\n".$m);
    fclose($logfile);
}

?>
