<?php
/*
Plugin Name: Family Site
Plugin URI:
Description: Family Site
Author: Derek Storkey
Version: 0.1
Author URI:
*/
/* TODOs
need a phase 3 which drives the update again - and need another req value (0,1,2) to direct it to use normal get
	also to translate the descriptions
add places on load

Create the virtual places. give them virtual ent ids and add those to the post. 

person pics link
people, places etc shortcodes
how to model marriages/spouses. Possibly use wedding event. In the album just a simple spouse field.
set up test env
EntLoader pictures
test duplicate person
siblings list - full and half. Also children
*/
namespace FamilySite;
use CPTHelper\CptHelper;
use CPTHelper\FieldHelper;
use CPTHelper\DateHelper;
use CPTHelper\CPTSelectHelper;
use CPTHelper\SelectHelper;
use CPTHelper\UseridSelector;

require_once("class/FSCpt.php");
require_once("class/FSPost.php");
require_once("class/Person.php");
require_once("class/Event.php");
require_once("class/Place.php");
require_once("class/Interest.php");
require_once("class/PersonCPT.php");
require_once("class/PlaceCPT.php");
require_once("class/EventCPT.php");
require_once("class/InterestCPT.php");
require_once("class/TimeLine.php");

class FamilySite {

  public function __construct(){

    $this->setupCPTs();

    add_action("init", [$this, "init"]);
    add_action("wp_head", [$this, "wp_head"]);
	register_activation_hook(__FILE__, [$this,"on_activation"]);
  }
  public function on_activation(){
	  TimeLine::activate();
  }
  public function init(){
    $this->setupTaxes();
	add_shortcode("a",[$this,"do_a"]);
  }
  public function wp_head(){
    // if this is a single page set up the cpost which will be used in templates
    error_log("In WP HEAD");
    if (is_single()){
      global $post;
      $GLOBALS["cpost"] = CptHelper::make($post);
      error_log("In wp head is single, cpost ".( $GLOBALS["cpost"] ? "is good": "is NULL")." post type=".$post->post_type);
    } else $GLOBALS["cpost"] = null;
  }
  protected function setupCPTs(){

    $z = new PersonCPT("person", "Person", "People", []);
    $z = new EventCPT("event", "Event", "Events", []);
    $z = new PlaceCPT("place", "Place", "Places", []);
    $z = new InterestCPT("post", null, null, []);
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
  public function do_a($att,$content,$tag){
	  if (isset($att[0]) && $att[0]){
		  $cp = CptHelper::makeByName($att[0]);
		  if ($cp===null) return "-".$att[0]." not known-"
		  if ($content) $text = do_shortcode($content);
		  elseif (isset($att[1]) && $att[1]) $text = $att[1];
		  else $text = null;
		  return $cp->simpleLink($text);
	  }
	  return "";
  }

}

$family_site = new FamilySite();

 ?>
