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
use CPTHelper\SelectHelper;

require_once("class/FSCpt.php");

class FamilySite {

  public function __construct(){

    $this->setupCPTs();
    $this->setupTaxes();

  }

  protected function setupCPTs(){

    $z = (new FSCpt("person", "Person", "People", []))
        ->set_taxonomy("person_tax")
        ->addField(new DateHelper("date_birth", "Date of Birth", "Date the person was born, yyyy/mm/dd"))
        ->addField(new CPTSelectHelper("place_birth", "Place of Birth", "Place the person was living in immediately after birth", ["posttype"=>"fs_place"]))
        ->addField(new DateHelper("date_death", "Date of Death", "Date the person was born, yyyy/mm/dd"))
        ->addField(new CPTSelectHelper("place_death", "Place of Death", "Place the person was living when they died", ["posttype"=>"fs_place"]))
        ->addField(new CPTSelectHelper("father", "Father", "", ["posttype"=>"fs_person"]))
        ->addField(new CPTSelectHelper("mother", "Mother", "", ["posttype"=>"fs_person"]))
        ->addField((new SelectHelper("gender", "Gender", "", ["posttype"=>"fs_person"]))
            ->addOption("M","Male")
            ->addOption("F","Female")
        )
        ->addField(new FieldHelper("birthname", "Birth Name", "Full name at birth (maiden name for ladies)"))
    ;
    $z = (new FSCpt("event", "Event", "Events", []))
        ->set_taxonomy("event_tax")
        ->addField(new DateHelper("actual_date", "Actual date", "Date event started"))
        ->addField(new FieldHelper("duration", "Duration", "The length of the event in days"))
        ->addField(new CPTSelectHelper("event_place", "Place of the event", "Place where the event occurred", ["posttype"=>"fs_place"]))
    ;
    $z = (new FSCpt("place", "Place", "Places", []))
        ->set_taxonomy("place_tax")
        ->addField(new FieldHelper("lat", "Latitude", "In degrees and decimals of a degree, + is North"))
        ->addField(new FieldHelper("long", "Longitude", "In degrees, + is East, - is West."))
    ;
    $z = (new FSCpt("post", null, null, []))
        ->addField(new DateHelper("actual_date", "Actual date", "Date that the picture was actually taken"))
        ->addField(new CPTSelectHelper("event", "Event", "", ["posttype"=>"fs_event"]))
    ;
  }

  protected function setupTaxes(){
    register_taxonomy("person_tax", "post", [
      "labels"=>[
        "name"=>__("People","familysite"),
        "singular_name"=>__("Person","familysite"),
        "all_items"=>__("All people","familysite"),
        "edit_item"=>__("Edit person","familysite"),
        "view_item"=>__("View person","familysite"),
        "update_item"=>__("Update person","familysite"),
        "add_new_item"=>__("Add new person","familysite"),
        "new_item_name"=>__("New person name","familysite"),
      ],
      "description" => "Used for tagging people, matches person CPT",
      "show_admin_column" => true,
      "re-write" => ["slug"=>"person",],

    ]);
    register_taxonomy("place_tax", "post", [
      "labels"=>[
        "name"=>__("Places","familysite"),
        "singular_name"=>__("Place","familysite"),
        "all_items"=>__("All places","familysite"),
        "edit_item"=>__("Edit place","familysite"),
        "view_item"=>__("View place","familysite"),
        "update_item"=>__("Update place","familysite"),
        "add_new_item"=>__("Add new place","familysite"),
        "new_item_name"=>__("New place name","familysite"),
      ],
      "description" => "Used for tagging places, matches place CPT",
      "show_admin_column" => true,
      "re-write" => ["slug"=>"place",],

    ]);
    register_taxonomy("event_tax", "post", [
      "labels"=>[
        "name"=>__("Events","familysite"),
        "singular_name"=>__("Event","familysite"),
        "all_items"=>__("All events","familysite"),
        "edit_item"=>__("Edit events","familysite"),
        "view_item"=>__("View events","familysite"),
        "update_item"=>__("Update events","familysite"),
        "add_new_item"=>__("Add neww event","familysite"),
        "new_item_name"=>__("New event name","familysite"),
      ],
      "description" => "Used for tagging events, matches event CPT",
      "show_admin_column" => true,
      "re-write" => ["slug"=>"event",],

    ]);
  }

}

$family_site = new FamilySite();

 ?>
