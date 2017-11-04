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
Brian H isnt there
add the stephens's to wanted
also mr & mrs lively
elaine S children
GOT TO SORT OUT weddngs/marriages PAUL STEPHENS - need to email Rowan
Theme is really not working
	remove the sidebar from single
	on the home page some things are full width. Is the sidebar floated?
	format timeline
	timeline group by day
	timeline into JS?
	need next and prev for post single()
	event slideshow
neils is bens daughter. Even though he was male. saving him solved the problem.
	neil's kids not selected
	joan not married to ben
formatting for picture tags
is there any point tagging photos with events??

Want to be able to add events like "Moved to <new place>", can then tag that event with the whole family. Will timeline handle that?
how to model marriages/spouses. Possibly use wedding event. In the album just a simple spouse field.
test duplicate person
siblings list - full and half
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
    register_taxonomy("person_tax", ["post","fs_event"], [
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
		  if ($cp===null) return "-".$att[0]." not known-";
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
