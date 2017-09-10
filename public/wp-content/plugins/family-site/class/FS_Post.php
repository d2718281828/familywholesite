<?php
namespace FamilySite;
use CPTHelper\CPost;
use CPTHelper\CptHelper;

// return the tax urls or the post urls they are associated with??
class FSPost extends CPost {

  protected $taxes = [];

  public function xtags(){
    $m = [];
    foreach($this->taxes as $tax) {
        $z = $this->xtagsFor($tax[0]);
        if ($z) $m[] = ["title"=>$tax[1], "tax"=>$tax[0], "list"=>$z ];
    }
    return $m;
  }

  protected function xtagsFor($tax){
    error_log("getting post terms for ".$this->postid." and tax ".$tax);
    $tms = wp_get_post_terms($this->postid, $tax);
    // transform into posts ??? Maybe into CPost objects :)
    $result = [];
    foreach ($tms as $tm){
      $result[] = FSCpt::makeFromTagid($tm->term_id);
    }
    return $result;
  }

  public function actualDate(){
    $actdate = $this->get("actual_date");
    if ($actdate){
      return $actdate;
    }
    $event = $this->get("event");
    if ($event){
      $ev = new Event($event);
      return $ev->actualDate();
    }
    return '';
  }

  public function posted(){
    if (!$this->post) $this->post = get_post($this->postid);
    $m =  $this->post->post_date_gmt;
    $auth = $this->authorId();
    $m.= ' by '.get_the_author_meta('display_name',$auth);
    return $m;
  }

  public function authorId(){
    if (!$this->post) $this->post = get_post($this->postid);
    return $this->post->post_author;
  }
  /**
  * Do we have an index section?
  */
  public function hasIndexSection(){
    return false;
  }
  /**
  * The index section - this is the timeline for people and events. Could be linked posts for Interest
  */
  public function indexSection(){
    return "";
  }
  // TODO the linking process via tags should be moved into base CPost/CptHelper, but not now
  protected function getLinksViaTax($tax,$type){
	  global $wpdb;
	  $id = (int)$this->postid;
	  
	  $s = "select P.ID 
	  from ".$wpdb->term_relationships." TR, ".$wpdb->term_taxonomy." TT, ".$wpdb->postmeta." PM, ".$wpdb->posts." P
	  where TR.object_id = $id and 
	  TR.term_taxonomy_id = TT.term_taxonomy_id and TT.taxonomy=%s and
	  PM.meta_key = 'fs_matching_tag_id' and PM.meta_value = TR.term_taxonomy_id 
	  and P.ID = PM.post_id and P.post_status = 'publish' ;"
	  ;
	  $sql = $wpdb->prepare($s,$tax);
	  if (WP_DEBUG) error_log("Getting post linkes for ".$this->postid." with SQL ".$sql);
	  $res = $wpdb->get_col($sql);
	  $cposts = [];
	  foreach($res as $id) $cposts[] = CptHelper::make($id,$type);
	  if (WP_DEBUG) {
		  foreach ($cposts as $cp) error_log("--- found   ".$cp->show());
	  }
	  return $cposts;
  }

}


 ?>
