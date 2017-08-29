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
    $event = $this->get("event");
    return $actdate;
  }

  public function posted(){
    $m =  get_the_date();
    $auth = $this->authorId();
    $m.= ' by '.get_the_author_meta('display_name',$auth);
  }

  public function authorId(){
    if (!$this->post) $this->post = get_post($this->postid);
    return $this->post->post_author;
  }

}


 ?>
