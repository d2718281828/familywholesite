<?php
namespace FamilySite;

// represents an instance of the Person CPT
class Place extends FSPost {

  protected $taxes = [];

  public function infoBox() {
    $m = "";
    $m.=$this->infoBit("Latitude",$this->get("lat"));
    $m.=$this->infoBit("Longitude",$this->get("long"));
    return $m;
  }

  /**
  * Do we have an index section?
  */
  public function hasIndexSection(){
    return true;
  }
  /**
  * The index section - this is the timeline for people and events - and now places
  */
  public function indexSection(){
    require_once("TimeLine.php");
    $tl = new TimeLine($this);
    return $tl->html().$this->afterIndexSection();
  }
  public function showPosted(){
	return false;
  }

}
