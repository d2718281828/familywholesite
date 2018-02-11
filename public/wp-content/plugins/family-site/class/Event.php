<?php
namespace FamilySite;

// represents an instance of the Event CPT
class Event extends FSPost {

  protected $taxes = [];

  public function actualDate(){
    $actdate = $this->get("actual_date");
    if ($actdate){
      return $actdate;
    }
    return 'Event undated';
  }

  public function infoBox(){
    $m = "";
	$w = ($x = $this->get("date_within")) ? " +/- ".$x." days" : "";
    $m.=$this->infoBit("Date",$this->get("actual_date").$w);
    if ($dur=$this->get("duration")){
      $m.=$this->infoBit("Duration",$dur." days");
    }
    if ($ep=$this->get("event_place")){
      $place = new Place($ep);
      $m.=$this->infoBit("Location",$place->simpleLink());
    }
    return $m;
  }

  /**
  * Do we have an index section?
  */
  public function hasIndexSection(){
    return true;
  }
  /**
  * The index section - this is the timeline for people and events. Could be linked posts for Interest
  */
  public function indexSection(){
	return "";
  }
  public function getLinks(){
	  return [];
  }
	public function showPosted(){
		return false;
	}
	public function slideShow(){
		return array(
			'meta_key'   => 'event',
			'meta_value' => $this->postid
		);
	}
}
