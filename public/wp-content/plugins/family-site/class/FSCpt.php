<?php
namespace FamilySite;
use CPTHelper\CptHelper;

class FSCpt extends CptHelper {

  protected $related_tax = null;

  protected function setup() {
    $this->prefix = "fs_";
  }
  public function set_taxonomy($taxname){
    $this->related_tax = $taxname;
    return $this;
  }
  protected function on_save($post_id, $post){
//die("Got to onsave");
    if (WP_DEBUG) error_log("in FamilySite::on_save method");
    $name = $post->post_name;
    // do we have a matching tag?
    $matchingtag = get_post_meta($post_id, "fs_matching_tag", true);
    if ($matchingtag) return;

    // is there a tag with this postname?
    $term = get_term_by("slug", $name, $this->related_tax );
    if ($term) {
	if (WP_DEBUG) error_log("eek, already have a tax term with name ".$name);
	return;
    }

    wp_insert_term($post->post_title, $this->related_tax, [
        "description"=>"Term relating to post ".$post->post_title,
        //"slug" => $name,
      ] );
  }

}


 ?>
