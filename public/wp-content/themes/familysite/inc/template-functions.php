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
	
	// remove has-sidebar if it shouldnt be there
	if (!$showside){
		$x = array_search('has-sidebar', $classes);
		if ($x!==false){
			$classes[$x] = null;
		}
	}

	return $classes;
}
// we need to remove has-sidebar. Set priority to ensure it runs after twentyseventeen_body_classes
add_filter( 'body_class', 'familysite_body_classes', 30 );
