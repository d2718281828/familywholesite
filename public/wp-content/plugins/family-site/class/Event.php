<?php
namespace FamilySite;

// represents an instance of the Person CPT
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
    $m.=$this->infoBit("Date",$this->get("actual_date"));
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
    require_once("TimeLine.php");
    $tl = new TimeLine($this);
    return $tl->html();
  }
  public function getLinks(){
	  return [];
  }
	public function showPosted(){
		return false;
	}
}
