<?php
use FamilySite\FSPost;
/**
 * Prints HTML with meta information for the categories, tags and comments.
 */
function twentyseventeen_entry_footer() {
  global $cpost;

	/* translators: used between list items, there is a space after the comma */
	$separate_meta = __( ', ', 'twentyseventeen' );

	// Get Categories for posts.
	$categories_list = get_the_category_list( $separate_meta );

	// Get Tags for posts.
	$tags_list = get_the_tag_list( '', $separate_meta );

	// We don't want to output .entry-footer if it will be empty, so make sure its not.
	if ( ( ( twentyseventeen_categorized_blog() && $categories_list ) || $tags_list ) || get_edit_post_link() ) {

		echo '<footer class="entry-footer">';
      echo '<div class="standard-cats">';
			if ( 'post' === get_post_type() ) {
				if ( ( $categories_list && twentyseventeen_categorized_blog() ) || $tags_list ) {
					echo '<span class="cat-tags-links">';

						// Make sure there's more than one category before displaying.
						if ( $categories_list && twentyseventeen_categorized_blog() ) {
							echo '<span class="cat-links">' . twentyseventeen_get_svg( array( 'icon' => 'folder-open' ) ) . '<span class="screen-reader-text">' . __( 'Categories', 'twentyseventeen' ) . '</span>' . $categories_list . '</span>';
						}

						if ( $tags_list ) {
							echo '<span class="tags-links">' . twentyseventeen_get_svg( array( 'icon' => 'hashtag' ) ) . '<span class="screen-reader-text">' . __( 'Tags', 'twentyseventeen' ) . '</span>' . $tags_list . '</span>';
						}

					echo '</span>';
				}
			}

      twentyseventeen_edit_link();
      echo '</div>';

      if ($cpost){
        $xtags = $cpost->xtags();
	      if (count($xtags)>0) {
          echo "<div class='tagsets'>";
          foreach($xtags as $xtag){
            echo "<div class='tagset tagset-".$xtag["tax"]."'>";
              echo "<div class='title'>".$xtag["title"]."</div>";
              foreach ($xtag["list"] as $tag){
                echo $tag ? $tag->get_template_part("link-small") : "Null-tag ";
              }
            echo "</div>";
          }
          echo "</div>";
        }
      } else echo "No CPost";

		echo '</footer> <!-- .entry-footer -->';
	}
}


 ?>
