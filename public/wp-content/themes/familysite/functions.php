<?php
global $showsides;

$showsides = false;

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

function fs_edit_post($postid = null){
	global $post;
	if (current_user_can("edit_posts")){
		$postid = $postid ?: $post->ID;
		$site = get_site_url();
		$template = get_stylesheet_directory_uri();
		$url = "http://dev.storkey.uk/wp-admin/post.php?post=$postid&action=edit";
		echo "<a href='$url'><div class='fs_edit_marker'><img src = '$template/assets/2000px-Blue_pencil.svg.png'></div></a>";
	}
}
?>
