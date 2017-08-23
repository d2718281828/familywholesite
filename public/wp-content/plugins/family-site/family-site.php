<?php
/*
Plugin Name: Family Site
Plugin URI:
Description: Family Site
Author: Derek Storkey
Version: 0.1
Author URI:
*/
namespace FamilySite;
use CPTHelper\CptHelper;
use CPTHelper\FieldHelper;
use CPTHelper\DateHelper;
use CPTHelper\CPTSelectHelper;

require_once("class/FSCpt.php");

class FamilySite {

  public function __construct(){

    $this->setupCPTs();

  }

  protected function setupCPTs(){

    $z = (new FSCpt("person", "Person", "People", []))
        ->addField(new DateHelper("date_birth", "Date of Birth", "Date the person was born, yyyy/mm/dd"))
        ->addField(new CPTSelectHelper("place_birth", "Place of Birth", "Place the person was living in immediately after birth", ["posttype"=>"fs_place"]))
        ->addField(new DateHelper("date_death", "Date of Death", "Date the person was born, yyyy/mm/dd"))
        ->addField(new CPTSelectHelper("place_death", "Place of Death", "Place the person was living when they died", ["posttype"=>"fs_place"]))
        ->addField(new CPTSelectHelper("father", "Father", "", ["posttype"=>"fs_person"]))
        ->addField(new CPTSelectHelper("mother", "Mother", "", ["posttype"=>"fs_person"]))
        ->addField(new SelectHelper("gender", "Gender", "", ["posttype"=>"fs_person"])
            ->addOption("M","Male")
            ->addOption("F","Female")
        )
    ;
    $z = (new FSCpt("event", "Event", "Events", []))
        ->addField(new DateHelper("actual_date", "Actual date", "Date event started"))
        ->addField(new FieldHelper("duration", "Duration", "The length of the event in days"))
        ->addField(new CPTSelectHelper("event_place", "Place of the event", "Place where the event occurred", ["posttype"=>"fs_place"]))
    ;
    $z = (new FSCpt("place", "Place", "Places", []))
        ->addField(new FieldHelper("lat", "Latitude", "In degrees and decimals of a degree, + is North"))
        ->addField(new FieldHelper("long", "Longitude", "In degrees, + is East, - is West."))
    ;
  }


}

$family_site = new FamilySite();

 ?>
