<?php
namespace FamilySite;

// represents an instance of the Person CPT
class Person extends FSPost {

  protected $taxes = [];

  public function infoBox(){
    $m = "";
    $m.=$this->infoBit("Full name at birth",$this->get("birthname"));

    if ($bp=$this->get("place_birth")){
      $place = new Place($bp);
      $placename = " at ".$place->simpleLink();
    } else $placename = "";
    $m.=$this->infoBit("Born",$this->get("date_birth").$placename);

    if ($dd=$this->get("date_death")){
      if ($bp=$this->get("place_death")){
        $place = new Place($bp);
        $placename = " at ".$place->simpleLink();
      } else $placename = "";
      $m.=$this->infoBit("Died",$dd.$placename);
    }
    if ($z=$this->relativeLink("father")) $m.= $this->infoBit("Father",$z);
    if ($z=$this->relativeLink("mother")) $m.= $this->infoBit("Mother",$z);
	
	// children
	$kids = $this->getChildren();
	$k = "";
	foreach ($kids as $kid) $k.=" ".$kid->simpleLink();
	if ($k) $m.= $this->infoBit("Children",$k);
	
    return $m;
  }
  /** make a simple link for father or mother
  */
  protected function relativeLink($prop){
    $z = $this->get($prop);
    if (!$z) return "";
    $pers = new Person($z);
    return $pers->simpleLink();
  }
  /**
  * This isnt very efficient. Perhaps there should be some accelerator tables...
  * I have resisted the temptation to do this in the timeline... it doesnt apply to all timelines and therefore would be wrong
  */
  protected function getChildren(){
	  global $wpdb;
	  $s = "select post_id from ".$wpdb->postmeta." where meta_key in ('father','mother') and meta_value = %d";
	  $ids = $wpdb->get_col($wpdb->prepare($s,$this->postid));
	  $res = [];
	  foreach($ids as $id) $res[] = new Person($id);
	  return $res;
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
    require_once("TimeLine.php");
    $tl = new TimeLine($this);
    return $tl->html();
  }
  public function getLinks(){
	  return [];
  }
  /** For a person, the matching tag will have the year of birth appended if it is before 1920
  */
  protected function matching_tag_title(){
	  $dob = $this->get("date_birth");
	  $app = ($dob && $dob<"1920") ? " (".substr($dob,0,4).")" : "";
	  return $this->post->post_title.$app;
  }
  public function on_update($req = false){
		$post_id = $this->postid;
		parent::on_update($req);
		if (WP_DEBUG) error_log("Person::on_update for ".$post_id.", ".($req?"REQ":"props"));
		TimeLine::clearSource($post_id);
		
		//if (WP_DEBUG) error_log("Person::on_update date_birth= ".$this->getcf($req,"date_birth","none"));
		if ($s=$this->getcf($req,"date_birth")){
			$place = $this->getcf($req,"place_birth",0);
			TimeLine::add1($s, $post_id, "BORN", $place, 0);
			// add mother and father too
			$gender = $this->getcf($req,"gender");
			$type = ($gender=="M") ? "SON" : "DAUGHTER";
			if ($mum=$this->getcf($req,"mother",0)){
				TimeLine::addChild($s, $post_id, $type, $mum, $place, 0);
			}
			if ($dad=$this->getcf($req,"father",0)){
				TimeLine::addChild($s, $post_id, $type, $dad, $place, 0);
			}
		}
		if ($s=$this->getcf($req,"date_death")){
			$place = $this->getcf($req,"place_death", 0);
			TimeLine::add1($s, $post_id, "DIED", $place,0 );
			// add mother and father too ???
		}
		if ($s=$this->getcf($req,"date_marriage")){
			$place = $this->getcf($req,"place_marriage",0);
			$spouse = $this->getcf($req,"spouse",0);		// so you can record that someone married without saying who to!
			TimeLine::addMarriage($s, $post_id, $post_id, $spouse,$place,0);
			if ($spouse){
				TimeLine::addMarriage($s, $post_id, $spouse, $post_id,$place,0);
			}
		}
  }
    public function on_destroy(){
		parent::on_destroy();
		if (WP_DEBUG) error_log("Person::on_delete for ".$this->postid);
		TimeLine::clearSource($this->postid);
    }

}
