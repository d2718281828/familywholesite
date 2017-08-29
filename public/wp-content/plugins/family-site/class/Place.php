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

}
