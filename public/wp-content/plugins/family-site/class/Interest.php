<?php
namespace FamilySite;

// represents an item of interest - an ordinary post, most likely a picture
class Interest extends FSPost {

  protected $taxes = [["person_tax","People"], ["event_tax","Events"], ["place_tax","Places"]];

  /**
  * Do we have an infobox?
  */
  public function hasInfoBox(){
    return false;
  }
  public function infoBox() {
    return "";
  }
  public function getLinks(){
	  $x = $this->getLinksViaTax("person_tax","fs_person");
	  //$y = $this->getLinksViaTax("event_tax","fs_event");
	  $y = $this->getEventCpost();
	  $z = $this->getLinksViaTax("place_tax","fs_place");
	  return array_merge($x,$y,$z);
  }
  protected function getEventCpost(){
	  $ev = $this->get("event");
	  if (WP_DEBUG) error_log("getEventCpost: found event number ".$ev);
	  if (!$ev) return [];
	  $evob = \CptHelper::make($ev,"fs_event");
	  if (WP_DEBUG) error_log("getEventCpost: found event ".$evob->show());
	  return [$evob];
  }
  public function on_update($req = false){
	$post_id = $this->postid;
	parent::on_update($req);
	if (WP_DEBUG) error_log("Person::on_update for ".$post_id.", ".($req?"REQ":"props"));
	TimeLine::clearSource($post_id);

	$actual_date = "";
	if (!$actual_date=$this->getcf($req,"actual_date")) {
		if ($event = $this->getcf($req,"event")) {
			$event = (int)$event;
			$actual_date = get_post_meta($event, "actual_date", true);
		}
	}
    if (WP_DEBUG) error_log("Interest $post_id has date $actual_date");
	
	if ($actual_date){
		$links = $this->getLinks();
		foreach($links as $link){
			TimeLine::addInterest($actual_date, $post_id,  $this->getType(), $link->postid, $link->getType());
		}
	}

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
	$ev = $this->getEventCpost();
	if ($ev){
		return ($ev[0])->simpleLink();
	}
    return "";
  }

}
