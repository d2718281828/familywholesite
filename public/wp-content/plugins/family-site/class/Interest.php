<?php
namespace FamilySite;

// represents an item of interest - an ordinary post, most likely a picture
class Interest extends FSPost {

  protected $taxes = [["person_tax","People"], ["event_tax","Events"], ["place_tax","Places"]];

  /**
  * Do we have an infobox?
  */
  public function hasInfoBox(){
    return false;
  }
  public function infoBox() {
    return "";
  }
  public function getPeople(){
	  return $this->getLinks("person_tax","Person");
  }
  protected function getLinks($tax,$classname = null){
	  global $wpdb;
	  $id = (int)$this->post_id;
	  
	  $s = "select P.ID 
	  from ".$wpdb->term_relationships." TR, ".$wpdb->term_taxonomy." TT, ".$wpdb->postmeta." PM, ".$wpdb->posts." P
	  where TR.object_id = $id and 
	  TR.term_taxonomy_id = TT.term_taxonomy_id and TT.taxonomy=%s and
	  PM.meta_key = 'fs_matching_tag_id' and PM.meta_value = TR.term_taxonomy_id 
	  and P.ID = PM.post_id and P.post_status = 'publish' ;"
	  ;
	  $sql = $wpdb->prepare($s,$tax);
	  if (WP_DEBUG) error_log("Getting post linkes for ".$this->post_id." with SQL ".$sql);
	  $res = $wpdb->get_col($sql);
	  if (WP_DEBUG) error_log("resulting links  ".implode(",",$res);
	  return $res;
  }

}
