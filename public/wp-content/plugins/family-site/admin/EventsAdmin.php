<?php 
namespace FamilySite;
use \AdminPage\BaseAdmin;

class EventsAdmin extends BaseAdmin {

  protected function setOpts(){
    $this->parms = ["Events", "Events", "activate_plugins", "family_events"];
    $this->options = [
      ["Gather", "eventgather"],
    ];
  }
  /**
  * This is the main content of the form page, including settings. It should not show the
  * actual form tags.
  * It should echo, not return the text.
  */
  public function page_content() {
	echo "<h3>Event Tools</h3>";
	
	$event_value=$_REQUEST["gatherevent"] ?: 0;
	echo "<p>Gather all interest items with the same date and add them to this event</p>";
	echo "<p><input name='eventid' value='$event_value'> Event id to gather.";
	echo "<p><input name='eventid_to_gather' value='$event_value'> Event id to gather.";
	echo "<input type='submit' name='action' value='Gather'></p>";
  }
  protected function eventgather(){
	  
	  $m = "Gathered ".$eventId;
	  return $m;
  }

}
