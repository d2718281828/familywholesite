<?php
error_log("doing template functions");
/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function familysite_body_classes( $classes ) {
	global $showside;
error_log("modified body classes. SHowside= ".($showside ? "true" : "false"));
	
	// remove has-sidebar if it shouldnt be there
	if (!$showside){
		$x = array_search('has-sidebar', $classes);
		if ($x!==false){
			error_log("Found has sidebar in the classes list, removing ".$x);
			$classes[$x] = null;
		}
	}

	return $classes;
}
// we need to remove has-sidebar
add_filter( 'body_class', 'familysite_body_classes', 30 );
