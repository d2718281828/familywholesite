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
Have to close off JSON access from the veil, and XMLRPC
private data attribute on import
need to store the type of media for interest
timeline entries arent being deleted when post is deleted
change 'leave a reply to 'make a comment
More person info = pull contact info from user profile
if person is deceased, would like to display the dates as a subheading
maybe an edit function to pull out the shortcodes for all tagged people and drop them into the text
date_within is not supported in the import or the timeline

--after release
Theme is really not working
	timeline group by day
	timeline into JS?
	need next and prev for post single()
Add siblings to the person infobox siblings list - full and half
is there any point tagging photos with events??

Want to be able to add events like "Moved to <new place>", can then tag that event with the whole family. Will timeline handle that?
test duplicate person

maps
family tree
birthdays calendar
anniversaries calendar
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
    add_action("admin_init", [$this, "admin_init"]);
    add_action("wp_head", [$this, "wp_head"]);
	register_activation_hook(__FILE__, [$this,"on_activation"]);
  }
  public function on_activation(){
	  TimeLine::activate();
  }
  public function init(){
    $this->setupTaxes();
	add_shortcode("stats",[$this,"do_stats"]);
	
	// change the reply text
	add_filter( 'comment_reply_link', [$this, 'change_comment'] );
  }
  public function admin_init(){
    wp_enqueue_style( 'family-site-admin-css', plugin_dir_url( __FILE__ ).'css/admin.css' );
  }
  /**
  * I just want to 'leave a comment', not a reply
  */
  public function change_comment($title){
	  return str_replace("Reply","Comment",$title);
	  return $title;
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
  // obsolete
  public function do_a_obs($att,$content,$tag){
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
  public function do_stats($att,$content,$tag){
	  global $wpdb;
	  $s = "select post_type, count(*) as num from ".$wpdb->posts." 
	  where (post_type like 'fs%' or post_type='post') and post_status='publish' 
	  group by post_type;";
	  $res = $wpdb->get_results($s,ARRAY_A);
	  $m ="<table><thead>";
	  $m.="<tr><td>type</td><td>number</td></tr>";
	  $m.="</thead><tbody>";
	  for ($k=0; $k<count($res); $k++){
		  switch($res[$k]['post_type']){
			  case 'fs_event': $tt = "Events";
			  break;
			  case 'fs_person': $tt = "People";
			  break;
			  case 'fs_place': $tt = "Places";
			  break;
			  default: $tt = "Photos etc.";
		  }
		$m.="<tr><td>".$tt."</td><td>".$res[$k]['num']."</td></tr>";  
	  }
	  $m.="</tbody></table>";
	  return $m;
  }

}
global $wpadmin_tab_name;
$wpadmin_tab_name = 'family_site_edit';
$family_site = new FamilySite();

 ?>
