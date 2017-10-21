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
  /**
  * return the taxonomy name used to represent this post.
  */
  public function getTax(){
	  return $this->cpthelper->get_taxonomy();
  }
  /**
  * Return the taxonomy term id if the term used to tag other posts to link to this CPost.
  * @return integer/null
  */
  public function getTermTaxId(){
	  $matchingtag = get_post_meta($this->postid, "fs_matching_tag_id", true);
	  return $matchingtag ? $matchingtag : null;
  }
  /**
  * Tag the post with the tags representing the posts in the argument list
  * @param $cplist array of CPosts
  * @return int number of tags added
  */
  public function tagWith($cplist){
	  $count = 0;
	  $termtaxes = [];
	  foreach($cplist as $cp) {
		  $tax = $cp->getTax();
		  $ttid = $cp->getTermTaxId();
		  echo "<p>***--* cp=".$cp->show()." tax=".$tax.", tt=".$ttid;
		  if (isset($termtaxes[$tax])) $termtaxes[$tax][] = $ttid;
		  else $termtaxes[$tax] = [$ttid];
	  }
	  // unfortunately these are all taxonomy termids and we need the termid.
	  foreach ($termtaxes as $tax=>$ttids){
		  $this->tagWithTtids($tax,$ttids);
	  }
	  return $count;
  }
  protected function tagWithTtids($taxname, $ttids){
	  global $wpdb;
	  if (count($ttids)==0) return;
	  $s = "select term_id from ".$wpdb->term_taxonomy." where term_taxonomy_id in (".implode(",",$ttids).");";
	  echo "<p>tagWithTtids translateed term taxids to termids ***** ".$s;
	  $tids = $wpdb->get_col($s);
	  $makeInt = function($num){return (int)$num;};
	  $tids = array_map($makeInt, $tids);
	  echo "<p>tagWithTtids will tag the post in $taxname with these termids: ".implode(",",$tids);
	  wp_set_post_terms($this->postid, $tids, $taxname, true);
	  //!!!! tried this and it didnt work
  }
  /**
  * Slug for the tag which will match this post.
  */ 
  protected function matching_tag_slug(){
	 if (!$this->post) $this->post = get_post($this->postid);
	  return $this->post->post_name;
  }
  protected function matching_tag_title(){
	 if (!$this->post) $this->post = get_post($this->postid);
	  return $this->post->post_title;
  }
  public function on_update($req = false){
	  parent::on_update($req);
	if (WP_DEBUG) error_log("FSpost::on_update for ".$this->postid.", ".($req?"REQ":"props"));
	  
    $name = $this->matching_tag_slug();
	
	if (!$reltax=$this->cpthelper->get_taxonomy()) return;	// if there isnt a related taxonomy then we dont need to create an entry
	
	if (WP_DEBUG) error_log("FSpost::on_update reltax=".$reltax);
    // do we have a matching tag?
    $matchingtag = get_post_meta($this->postid, "fs_matching_tag_id", true);
    if ($matchingtag) return;

	if (WP_DEBUG) error_log("FSpost::on_update reltax=".$reltax.", name=".$name);
    // is there a tag with this postname?
    $term = get_term_by("slug", $name, $reltax );
    if ($term) {
	    if (WP_DEBUG) error_log("eek, already have a tax term with name ".$name." for post=".$this->postid);
	    return;
    }

    $rc = wp_insert_term($this->matching_tag_title(), $reltax, [
        "description"=>"Term relating to post ".$this->post->post_title,
        "slug" => $name,
      ] );
    if (is_wp_error($rc)){
      error_log("ERROR inserting taxonomy term for ".$this->post->post_title.", tax=".$reltax.": ".$rc->get_error_message());
    } else {
      update_post_meta($this->postid, "fs_matching_tag_id", $rc["term_taxonomy_id"]);
      if (WP_DEBUG) error_log("saving term id ".$rc["term_taxonomy_id"]." for ".$this->post->post_title);
    }
	  
  }

    public function on_destroy(){
		global $wpdb;
		parent::on_destroy();
		if (WP_DEBUG) error_log("FSPost::on_delete for ".$this->postid);
		$matchingtag = get_post_meta($this->postid, "fs_matching_tag_id", true);
		if (!$matchingtag) return;
		// this is the term tax id, need to get term id and taxonomy
		$s = "select term_id, taxonomy from ".$wpdb->term_taxonomy." where term_taxonomy_id=%d;";
		$tt = $wpdb->get_results($wpdb->prepare($s,$matchingtag),ARRAY_A);
		if (count($tt)>0) wp_delete_term($tt[0]["term_id"],$tt[0]["taxonomy"]);
    }
	/**
	* Whether to show the posted: section in single
	*/
	public function showPosted(){
		return true;
	}
}


 ?>
