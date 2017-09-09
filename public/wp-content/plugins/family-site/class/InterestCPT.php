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
  protected function on_save($post_id, $post){
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
			$actual_date = get_postmeta($event, "actual_date", true);
		}
	}
	$interest = new Interest($post);
	
	if ($actual_date){
		$this->addTimeLineForTax($post_id, $post->post_type, $actual_date, $interest->getPeople(), "fs_person" );
		//$this->addTimeLineForTax($post_id, $actual_date, "place_tax", "fs_place" );
		//$this->addTimeLineForTax($post_id, $actual_date, "event_tax", "fs_event" );
	}
	
  }
  protected function addTimeLineForTax($sid, $stype, $actual_date, $list, $otype){
	  foreach($list as $linked){
		  TimeLine::addInterest($actual_date, $sid, $stype, $linked, $otype);
	  }
  }
}
 ?>
