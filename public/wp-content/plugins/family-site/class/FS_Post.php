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

}


 ?>
