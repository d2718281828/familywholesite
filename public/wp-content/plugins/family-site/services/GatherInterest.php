<?php
namespace FamilySite;
use CPTHelper\CptHelper;
//require_once("../class/Interest.php");

// Find all interest items with the same date as the given eventt, and add them to this event
class GatherInterest {

  public function __construct(){
  }

  /**
  * Find all interest items with this date and add them. The argument must be an event
  */
  public function process($eventId){
	  global $wpdb;
	  
	  $event = CptHelper::make($eventId, "fs_event");
	  if (!$event) return "Post $eventId is not an event";
	  
	  $actual_date = $event->get("actual_date");
	  if (!$actual_date) return "Event $eventId does not have an actual date set";
	  
	  // get posts with the same actual date
	  $s = "select P.ID
	  from ".$wpdb->posts." P, ".$wpdb->postmeta." AD
	  where P.ID = AD.post_id and P.post_type = 'post' and P.post_status = 'publish'
	  and AD.meta_key = 'actual_date' and AD.meta_value = %s";
	  
	  $sprep = $wpdb->prepare($s,$actual_date);
	  $res = $wpdb->get_results($sprep, ARRAY_A);
	  
	  if (count($res)==0) return "Found no posts with actual date $actual_date";
	  
	  $m = "<p>Found ".count($res)." posts with actual date $actual_date</p>";
	  
	  $added = 0;
	  $already = 0;
	  
	  for ($k=0; $k<count($res); $k++) {
		  $postid = $res[$k]["ID"];
		  $int = CptHelper::make($postid, "post");	// could I use just a new here?
		  if ($int->get("event")) {
			  $already++;
		  } else {
			  // set the event
			  $int->set("event", $eventId);
			  $added++;
		  }
	  }
	  $m.= "<p>Added $added to the event, and $already already had an event set.";
	  return $m;
  }

}
