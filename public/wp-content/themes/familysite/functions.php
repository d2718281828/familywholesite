<?php
global $showside;

$showside = ($_SERVER['REQUEST_URI']=="/"); // is_home and is_front_page dont work at  this point

function my_theme_enqueue_styles() {

    $parent_style = 'twentyseventeen-style'; // This is 'twentyfifteen-style' for the Twenty Fifteen theme.

    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'familysite-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( $parent_style ),
        wp_get_theme()->get('Version')
    );
}
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );

include("inc/template-tags.php");

/**
* Much nicer edit function, with pencil, which can also be used in multiple places in the page.
*/
function fs_edit_post($postid = null){
	global $post, $wpadmin_tab_name;
	$postid = $postid ?: $post->ID;
	if (current_user_can("edit_posts")){		
		$site = get_site_url();
		$template = get_stylesheet_directory_uri();
		$site = site_url();
		$url = "$site/wp-admin/post.php?post=$postid&action=edit";
		echo "<a href='$url' target='".($wpadmin_tab_name ?: "_blank")."' alt='edit'><div class='fs_edit_marker'><img src = '$template/assets/2000px-Blue_pencil.svg.png'></div></a>";
	}
	echo fs_download_image();
}
function fs_download_image(){
	global $cpost;
	if (!$cpost) return "";
	if (!$down = $cpost->downloadAsset()) return "";
	return "<a href='".$down->url."' download alt='".$down->alt."'><div class='fs_edit_marker fs_download_marker'><img src = '".$down->icon."'></div></a>";	
}
/**
 * Over-write body_class function in here.
 */
require get_theme_file_path( '/inc/template-functions.php' );

add_image_size( 'fs-featured-image', 527, 316, true );


?>
