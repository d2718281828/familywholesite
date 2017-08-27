<?php
namespace FamilySite;
use CPTHelper\CPost;

// return the tax urls or the post urls they are associated with??
class FSPost extends CPost {

  protected $taxes = ["person_tax", "event_tax", "place_tax"];

  public function xtags(){
    $m = [];
    foreach($this->taxes as $tax) $m = array_merge($m, $this->xtagsFor($tax));
    return $m;
  }

  protected function xtagsFor($tax){
    error_log("getting post terms for ".$this->postid." and tax ".$tax);
    $tms = wp_get_post_terms($this->postid, $tax);
    // transform into posts ??? Maybe into CPost objects :)
    return $tms;
  }

}


 ?>
