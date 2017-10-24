<?php
namespace FamilySite;
use CPTHelper\CptHelper;
use CPTHelper\FieldHelper;
use CPTHelper\DateHelper;
use CPTHelper\CPTSelectHelper;
use CPTHelper\SelectHelper;
use CPTHelper\UseridSelector;

class EventCPT extends FSCpt {

  public function setup(){
    parent::setup();
	$this
        ->set_taxonomy("event_tax")
        ->setClass("FamilySite\Event")
        ->addField(new DateHelper("actual_date", "Actual date", "Date event started"))
        ->addField(new FieldHelper("duration", "Duration", "The length of the event in days - leave blank if it took place on only one day"))
        ->addField(new CPTSelectHelper("event_place", "Place of the event", "Place where the event occurred", ["posttype"=>"fs_place"]))
    ;
  }
  public function on_save($post_id, $post){
    if (WP_DEBUG) error_log("in FamilySite::EventCPT::on_save method");
	parent::on_save($post_id, $post);

	// refresh timeline info
	TimeLine::clearSource($post_id);
	$source = new Person($post);
	
	if (isset($_REQUEST["date_birth"]) && $_REQUEST["date_birth"]){
		$place = $_REQUEST["place_birth"] ?: 0;
		TimeLine::add1($_REQUEST["date_birth"], $post_id, "BORN", $place, 0);
	}
	if (isset($_REQUEST["date_death"]) && $_REQUEST["date_death"]){
		$place = $_REQUEST["place_death"] ?: 0;
		TimeLine::add1($_REQUEST["date_death"], $post_id, "DIED", $place,0 );
	}
	if (isset($_REQUEST["date_marriage"]) && $_REQUEST["date_marriage"]){
		$place = $_REQUEST["place_marriage"] ?: 0;
		$spouse = $_REQUEST["spouse"] ?: 0;		// so you can record that someone married without saying who to!
		TimeLine::addMarriage($_REQUEST["date_marriage"], $post_id, $post_id, $spouse,$place,0);
		if ($spouse){
			TimeLine::addMarriage($_REQUEST["date_marriage"], $post_id, $spouse, $post_id,$place,0);
		}
	}
  }
	protected function list_heading(){
		return "<th>Event</th><th>Date</th>";
	}
	protected function list_row($cpost){
		$url = $cpost->permalink();
		$m = '<td><a href="'.$url.'">'.$cpost->get("post_title").'</a></td>';
		$m.= '<td>'.$cpost->get("actual_date").'</td>';
		return $m;
	}
}
 ?>
