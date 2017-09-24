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
  public function on_save($post_id, $post, $data = null){
    if (WP_DEBUG) error_log("in FamilySite::FSCpt::on_save method");
    $name = $post->post_name;
	
	if (!$this->related_tax) return;	// if there isnt a related taxonomy then we dont need to create an entry
	
    // do we have a matching tag?
    $matchingtag = get_post_meta($post_id, "fs_matching_tag_id", true);
    if ($matchingtag) return;

    // is there a tag with this postname?
    $term = get_term_by("slug", $name, $this->related_tax );
    if ($term) {
	    if (WP_DEBUG) error_log("eek, already have a tax term with name ".$name);
	    return;
    }

    $rc = wp_insert_term($post->post_title, $this->related_tax, [
        "description"=>"Term relating to post ".$post->post_title,
        "slug" => $name,
      ] );
    if (is_wp_error($rc)){
      error_log("ERROR inserting taxonomy term for ".$post->post_title.", tax=".$this->related_tax.": ".$rc->get_error_message());
    } else {
      update_post_meta($post_id, "fs_matching_tag_id", $rc["term_taxonomy_id"]);
      if (WP_DEBUG) error_log("saving term id ".$rc["term_id"]." for ".$post->post_title);
    }
  }
  // TODO adapt to work with set of ids and return set of cposts.
  static function makeFromTagid($tagid){
    global $wpdb;
    $s = "select post_id from ".$wpdb->postmeta." where meta_key='fs_matching_tag_id' and meta_value=%s";
    $res = $wpdb->get_results($wpdb->prepare($s,$tagid),ARRAY_A);
	error_log("Got ".count($res)." rows from ".$wpdb->prepare($s,$tagid));
    if (count($res)==0) return null;
    return self::make($res[0]["post_id"]);
  }

}


 ?>
