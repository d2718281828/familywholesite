<?php
namespace FamilySite;

// represents an instance of the Event CPT
class Event extends FSPost {

  protected $taxes = [];

  public function actualDate(){
    $actdate = $this->get("actual_date");
    if ($actdate){
      return $actdate;
    }
    return 'Event undated';
  }

  public function infoBox(){
    $m = "";
	$w = ($x = $this->get("date_within")) ? " +/- ".$x." days" : "";
    $m.=$this->infoBit("Date",$this->get("actual_date").$w);
    if ($dur=$this->get("duration")){
      $m.=$this->infoBit("Duration",$dur." days");
    }
    if ($ep=$this->get("event_place")){
      $place = new Place($ep);
      $m.=$this->infoBit("Location",$place->simpleLink());
    }
    return $m;
  }

  /**
  * Do we have an index section?
  */
  public function hasIndexSection(){
    return true;
  }
  /**
  * The index section - this is the timeline for people and events. Could be linked posts for Interest
  */
  public function indexSection(){
	return $this->afterIndexSection();
  }
  public function getLinks(){
	  return [];
  }
  public function showPosted(){
		return false;
  }
  public function slideShow(){
	return array(
		'meta_key'   => 'event',
		'meta_value' => $this->postid
	);
  }
  public function on_update($req = false){
	$post_id = $this->postid;
	parent::on_update($req);
	if (WP_DEBUG) error_log("Event::on_update for ".$post_id.", ".($req?"REQ":"props"));
	TimeLine::clearSource($post_id);

	$actual_date = "";
	if ($actual_date=$this->getcf($req,"actual_date")) {
		$date_within = $this->getcf($req,"date_within");
		$date_within = $date_within ?: 0;	// in case it's null
	}
    if (WP_DEBUG) error_log("Event $post_id has date $actual_date");
	
	// an event only goes into the timeline if it has links
	if ($actual_date){
		$links = $this->getLinksViaTax("person_tax","fs_person");
		foreach($links as $link){
			TimeLine::addEvent($actual_date, $post_id,  $this->getType(), $link->postid, $link->getType(), $date_within );
		}
	}

  }
}
