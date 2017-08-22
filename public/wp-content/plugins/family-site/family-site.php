<?php
namespace FamilySite;
use CPTHelper\CptHelper;
use CPTHelper\FieldHelper;
use CPTHelper\DateHelper;
/*
Plugin Name: Family Site
Plugin URI:
Description: Family Site
Author: Derek Storkey
Version: 0.1
Author URI:
*/
class FamilySite {

  public function __construct(){

    $this->setupCPTs();

  }

  protected function setupCPTs(){

    $z = (new CptHelper("person", "Person", "People", []))
        ->addField(new DateHelper("date_birth", "Date of Birth", "Date the person was born, yyyy/mm/dd"))
        ->addField(new CPTSelectHelper("place_birth", "Place of Birth", "Place the person was living in immediately after birth", ["posttype"=>"place"]))
        ->addField(new DateHelper("date_death", "Date of Death", "Date the person was born, yyyy/mm/dd"))
        ->addField(new CPTSelectHelper("place_death", "Place of Death", "Place the person was living when they died", ["posttype"=>"place"]))
        ->addField(new CPTSelectHelper("father", "Father", "", ["posttype"=>"person"]))
        ->addField(new CPTSelectHelper("mother", "Mother", "", ["posttype"=>"person"]))
    ;
    $z = (new CptHelper("event", "Event", "Events", []))
        ->addField(new DateHelper("actual_date", "Actual date", "Date event started"))
        ->addField(new FieldHelper("duration", "Duration", "The length of the event in days"))
        ->addField(new CPTSelectHelper("event_place", "Place of the event", "Place where the event occurred", ["posttype"=>"place"]))
    ;
    $z = (new CptHelper("place", "Place", "Places", []))
        ->addField(new FieldHelper("lat", "Latitude", "In degrees and decimals of a degree, + is North"))
        ->addField(new FieldHelper("long", "Longitude", "Place the person was living when they died"))
    ;
  }


}

$family_site = new FamilySite();

 ?>
