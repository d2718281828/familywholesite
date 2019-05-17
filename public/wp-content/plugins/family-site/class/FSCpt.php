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
	$this->taxonomiesExtra[] = $taxname;	// this will get it into the register
    return $this;
  }
  public function get_taxonomy(){
    return $this->related_tax;
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
