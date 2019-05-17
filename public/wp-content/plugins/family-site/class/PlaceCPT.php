<?php
namespace FamilySite;
use CPTHelper\CptHelper;
use CPTHelper\FieldHelper;
use CPTHelper\DateHelper;
use CPTHelper\CPTSelectHelper;
use CPTHelper\SelectHelper;
use CPTHelper\UseridSelector;

class PlaceCPT extends FSCpt {

  public function setup(){
    parent::setup();
	$this
        ->set_taxonomy("place_tax")
		->addToQueries(["category"])
        ->setClass("FamilySite\Place")
        ->addField(new FieldHelper("lat", "Latitude", "In degrees and decimals of a degree, + is North"))
        ->addField(new FieldHelper("long", "Longitude", "In degrees, + is East, - is West."))
    ;
  }
 public function on_save($post_id, $post){
    if (WP_DEBUG) error_log("in FamilySite::PlaceCPT::on_save method");
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
}
 ?>
