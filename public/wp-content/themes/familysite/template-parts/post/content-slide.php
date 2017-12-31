<?php
/**
 * Template part for displaying posts
 *
 * @link https://codex.wordpress.org/Template_Hierarchy
 *
 * @package WordPress
 * @subpackage Twenty_Seventeen
 * @since 1.0
 * @version 1.2
 */
?>
<div class="slick-slide">
	<a href="<?php echo esc_url( get_permalink() ) ?>">
		<?php the_post_thumbnail( 'twentyseventeen-featured-image' ); ?>
	</a>
</div>
