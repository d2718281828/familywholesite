<?php
namespace FamilySite;
use CPTHelper\CptHelper;
use CPTHelper\FieldHelper;
use CPTHelper\DateHelper;
use CPTHelper\CPTSelectHelper;
use CPTHelper\SelectHelper;
use CPTHelper\UseridSelector;

class InterestCPT extends FSCpt {

  public function setup(){
    parent::setup();
	$this
	      ->setClass("FamilySite\Interest")
        ->addField(new DateHelper("actual_date", "Actual date", "Date that the picture was actually taken"))
        ->addField(new CPTSelectHelper("event", "Event", "", ["posttype"=>"fs_event"]))
    ;
  }
  public function on_save_obs($post_id, $post){
    if (WP_DEBUG) error_log("in FamilySite::InterestCPT::on_save method");
	parent::on_save($post_id, $post);
	
	// TODO check it isnt editorial or help (although absence of actual date will also do the same)

	// refresh timeline info
	TimeLine::clearSource($post_id);
	
	$actual_date = "";
	if (isset($_REQUEST["actual_date"]) && $_REQUEST["actual_date"]) $actual_date = $_REQUEST["actual_date"];
	else {
		if (isset($_REQUEST["event"]) && $_REQUEST["event"]) {
			$event = (int)$_REQUEST["event"];
			$actual_date = get_post_meta($event, "actual_date", true);
		}
	}
    if (WP_DEBUG) error_log("Interest $post_id has date $actual_date");
	
	if ($actual_date){
		$interest = new Interest($post);
		$links = $interest->getLinks();
		foreach($links as $link){
			TimeLine::addInterest($actual_date, $post_id,  $post->post_type, $link->postid, $link->getType());
		}
	}
	
  }
}
 ?>
