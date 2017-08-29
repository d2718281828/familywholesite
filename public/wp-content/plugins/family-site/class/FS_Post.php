<?php
namespace FamilySite;
use CPTHelper\CPost;

// return the tax urls or the post urls they are associated with??
class FSPost extends CPost {

  protected $taxes = [["person_tax","People"], ["event_tax","Events"], ["place_tax","Places"]];

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
    return 'Undated';
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

}


 ?>
