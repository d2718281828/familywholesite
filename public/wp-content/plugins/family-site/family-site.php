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
Before final load
!!! person pictures - nodepic - might already be done, might just need a final tidyup button on ent load
!!! Add photos to events - lookup based on date? May not be necessary - tst with reloaded nodes.
	test 13/7/2002
	Excerpts
!!! Manual image crop is not working, Have to find another plugin
	
	
!!! test the movie files from 13/7/2002 in  /agd/mov207
	mpg file just shows as a link
!!! Places should be tagged with places (nearby) and people and events

!!! page 2 etc on the front page doesnt show side bar


!!! the left and right buttons on the event slideshow need to be styled

Doreen's music post doesnt have a date

Does a normal post appear on the timeline? SHould there be a category for posts which arent time related?

test add photo and write it up in the help page.
Finding images in the media library (for featured image) isnt easy.
	Do an "Add to entity" button on the post itself.
	ANd a "Make this my photo"
!!!	ALso a Go to Crop link. But crop isnt working

!!! Need an SSL certificate . Could do self certified, since I know all my audience.

Help pages - hosting? SSL certs. 

Timeline should use standard crop size photos

cant upload mpegs. grandma recordings

Person needs to be only editable by admin or by the user who is in the user field.
Maybe we need a privacy policy page…

the home page columns arent stacking properly on page>1
NEED TO SET UP EMAIL ON THE SERVER
BACKUPS ON SERVER
NEED MORE SPACE ON DROPSIE
the pdf viewer is showing cookie messages!!!
Check image upload sizes and timeline overall load times
cant look at photos on the same date. if there isnt an event created then you're stuck.

timeline entries arent being deleted when post is deleted
change 'leave a reply to 'make a comment
if person is deceased, would like to display the dates as a subheading
maybe an edit function to pull out the shortcodes for all tagged people and drop them into the text

family tree

Newsletter and 

Page/widget/shortcode ideas
new arrivals - youngest people
page for the cousins
page for the second cousins
Quiz - what is the relationship between two randomly chosen persons?

--after release
need timeline or a slideshow for a single day.

lookup by uploaders reference

judith dob not showing - dosnt seem to be a problem
date_within is not supported in the import or the timeline. - it works for new post creation
need to store the type of media for interest - it is stored

Event carousel  not stoppable, and not full screen. Would be good to have a hover that  showed picture details.
Main picture detail still shows a thumbnail, not whole pic. Not full sccreen, not fit to screen.
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

done ========

New <public> attribute:
<public>familysite<x>y
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
	  
	$personOptions = ['taxonomies' => ['category' ],
				];
	$eventOptions = $personOptions;
	$placeOptions = $personOptions;

	$thingOptions = ['taxonomies' => ['category', 'person_tax', 'place_tax' ],
				];
	$thingOptions = [];		// dont think that the above is necessary
	
    $z = new PersonCPT("person", "Person", "People", $personOptions );
    $z = new EventCPT("event", "Event", "Events", $eventOptions);
    $z = new PlaceCPT("place", "Place", "Places", $placeOptions);
    $z = new InterestCPT("post", null, null, $thingOptions );
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
  public function do_stats($att,$content,$tag){
	  global $wpdb;
	  $s = "select post_type, count(*) as num from ".$wpdb->posts." 
	  where (post_type like 'fs%' or post_type='post') and post_status='publish' 
	  group by post_type;";
	  $res = $wpdb->get_results($s,ARRAY_A);
	  $m ="<table><thead>";
	  $m.="<tr><td>type</td><td></td><td>number</td></tr>";
	  $m.="</thead><tbody>";
	  for ($k=0; $k<count($res); $k++){
		  $extra = "";
		  switch($res[$k]['post_type']){
			  case 'fs_event': $tt = "Events";
			  break;
			  case 'fs_person': $tt = "People";
			  break;
			  case 'fs_place': $tt = "Places";
			  break;
			  default: $tt = "Memorabilia";
			  $extra = $this->get_post_types($res[$k]['num']);
		  }
		$m.="<tr><td>".$tt."</td><td></td><td>".$res[$k]['num']."</td></tr>".$extra;  
	  }
	  $m.="</tbody></table>";
	  return $m;
  }
  public function get_post_types($totalPosts){
	  global $wpdb;
	  // other attachment types
	  $s = 'select PM.meta_value as attach, count(*) as num
	  from '.$wpdb->posts.' P, '.$wpdb->postmeta.' PM
	  where PM.post_id = P.ID 
	  and P.post_type="post" and P.post_status="publish"
	  and PM.meta_key = "featured_media_type"
	  group by PM.meta_value
	  ;';
	  // number with thumbnail
	  $thumbs = 'select count(*) as num
	  from '.$wpdb->posts.' P, '.$wpdb->postmeta.' PM
	  where PM.post_id = P.ID 
	  and P.post_type="post" and P.post_status="publish"
	  and PM.meta_key = "_thumbnail_id"
	  ;';

	  $m="";
	  $others = $totalPosts;
	  
	  $res = $wpdb->get_results($s,ARRAY_A);
	  for ($k=0; $k<count($res); $k++){
		  $others= $others-$res[$k]['num'];
		  $m.="<tr><td></td><td>".$res[$k]['attach']."</td><td>".$res[$k]['num']."</td></tr>";	  
	  }

	  $pix =  $wpdb->get_results($thumbs,ARRAY_A);
	  if (count($pix)>0){
		  $numpix = $pix[0]["num"];
		  $others= $others - $numpix;
		  $m.="<tr><td></td><td>Pictures</td><td>".$numpix."</td></tr>";	  		  
	  }
	  if ($others>0){
		  $m.="<tr><td></td><td>Others</td><td>".$others."</td></tr>";	  		  
	  }
	  return $m;
  }

}
global $wpadmin_tab_name;
$wpadmin_tab_name = 'family_site_edit';
$family_site = new FamilySite();

 ?>
