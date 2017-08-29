<?php
namespace FamilySite;

// represents an instance of the Person CPT
class Person extends FSPost {

  protected $taxes = [];

  public function infoBox(){
    $m = "";
    $m.=$this->infoBit("Full name at birth",$this->get("birthname"));

    if ($bp=$this->get("place_birth")){
      $place = new Place($bp);
      $placename = " at ".$place->simpleLink();
    } else $placename = "";
    $m.=$this->infoBit("Born",$this->get("date_birth").$placename);

    if ($dd=$this->get("date_death")){
      if ($bp=$this->get("place_death")){
        $place = new Place($bp);
        $placename = " at ".$place->simpleLink();
      } else $placename = "";
      $m.=$this->infoBit("Died",$dd.$placename);
    }
    if ($z=$this->relativeLink("father")) $m.= $this->infoBit("Father",$z);
    if ($z=$this->relativeLink("mother")) $m.= $this->infoBit("Mother",$z);
    return $m;
  }
  protected function relativeLink($prop){
    $z = $this->get($prop);
    if (!$z) return "";
    $pers = new Person($z);
    return $pers->simpleLink();
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
    return "pictures";
  }

}
