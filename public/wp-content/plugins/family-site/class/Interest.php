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
  /**
  * Return the CPost representing the event for this item
  * For consistency with tags, return it in a list, although there can only be 0 or 1.
  */
  protected function getEventCpost(){
	  $ev = $this->get("event");
	  if (WP_DEBUG) error_log("getEventCpost: found event number ".$ev);
	  if (!$ev) return [];
	  $evob = \CPTHelper\CptHelper::make($ev,"fs_event");
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
			$date_within = get_post_meta($event, "date_within", true);
		}
	} else {
		$date_within = $this->getcf($req,"date_within");
		$date_within = $date_within ?: 0;	// in case it's null
	}
    if (WP_DEBUG) error_log("Interest $post_id has date $actual_date");
	
	if ($actual_date){
		$links = $this->getLinks();
		$creator = $this->getcf($req,"maker");
		$creator = $creator ?: null;		// turn zero into null for consistency 
		foreach($links as $link){
			TimeLine::addInterest($actual_date, $post_id,  $this->getType(), $link->postid, $link->getType(), $creator, $date_within );
		}
	}

  }
  /**
  * Add the event to the normal xtags list just for interest items, because we arent tagging by event, it's a custom field.
  */
  public function xtags(){
	$m = parent::xtags();
	$ev = $this->getEventCpost();
	if ($ev) $m[] = ["title"=>"Event", "tax"=>"event_tax", "list"=>$ev ];
    return $m;
  }
  /**
  * Do we have an index section?
  */
  public function hasIndexSection(){
    return true;
  }
  public function indexSection(){
	return $this->afterIndexSection();
  }
  /**
  * download asset info
  * @return null if none, or object containing url and icon properties.
  */
  public function downloadAsset(){
	$icon = plugin_dir_url( __FILE__ )."../assets/Download_Icon.svg";
	$url = get_the_post_thumbnail_url($this->postid, "full");	// need to make sure it is full size
	if ($url) {
		return  [
			"icon"=>$icon,
			"url" => $url,
			"alt" => "Download full sized image"
		];
	}
	$mediapost = $this->get("featured_media");
	if (!$mediapost) return null;
	
	$url = wp_get_attachment_url($mediapost);
	
	return [
		"icon"=>$icon,
		"url" => $url,
		"alt" => "Download file"
	];
  }

}
