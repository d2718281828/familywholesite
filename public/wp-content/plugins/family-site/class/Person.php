<?php
namespace FamilySite;

// represents an instance of the Person CPT
class Person extends FSPost {

  protected $taxes = [];
  protected $loggedin = false;
  protected $sensitive = true;	// is the persson object sensitive (i.e. still around)
  
  public function init(){
	// is the user logged in?
	$this->loggedin = is_user_logged_in();
	$this->sensitive = $this->get("date_death") ? false : true;
	$this->showDates = $this->sensitive && !$this->loggedin;
  }


  public function infoBox(){
    $m = "";
    $m.=$this->infoBit("Full name at birth",$this->get("birthname"));

	if ($this->showDates) {
		if ($bp=$this->get("place_birth")){
		  $place = new Place($bp);
		  $placename = " at ".$place->simpleLink();
		} else $placename = "";
		$m.=$this->infoBit("Born",$this->get("date_birth").$placename);

		if ($z=$this->get("date_baptism")) $m.= $this->infoBit("Baptized",$z);

		if ($dd=$this->get("date_death")){
		  if ($bp=$this->get("place_death")){
			$place = new Place($bp);
			$placename = " at ".$place->simpleLink();
		  } else $placename = "";
		  $m.=$this->infoBit("Died",$dd.$placename);
		}
	}
    if ($z=$this->get("occupation")) $m.= $this->infoBit("Occupation",$z);
	
    if ($z=$this->relativeLink("father")) $m.= $this->infoBit("Father",$z);
    if ($z=$this->relativeLink("mother")) $m.= $this->infoBit("Mother",$z);
	
	// Spouse
    if ($z=$this->relativeLink("spouse")) $m.= $this->infoBit("Spouse",$z);
	else {
		if ($z=$this->otherSpouseLink()) $m.= $this->infoBit("Spouse",$z);
	}
    if ($z=$this->marriageList("prior_marriages")) $m.= $this->infoBit("Previous Marriages",$z);
	
	// children
	$kids = $this->getChildren();
	$k = "";
	foreach ($kids as $kid) $k.=", ".$kid->simpleLink();
	if ($k) $m.= $this->infoBit("Children",substr($k,2));
	
	$userid = $this->get("userid");
	if ($userid){
		$userdata = get_userdata( $userid );		
		$m.= $this->infoBit("Email",$userdata->user_email);
	}
    return $m;
  }
  /** 
  * make a simple link for father or mother or spouse
  */
  protected function relativeLink($prop){
    $z = $this->get($prop);
    if (!$z) return "";
    $pers = new Person($z);
    return $pers->simpleLink();
  }
  protected function marriageList($prop){
    $z = $this->get($prop,true);		// get multiple
    if (!$z) return "";
	$res = "<table>";
	foreach($z as $marriage) $res.=$this->marriageOne($marriage);
	$res.= "</table>";
    return $res;	  
  }
  protected function marriageOne($marriage){
	  $mar = json_decode($marriage,true);
	  $m = "<tr>";
	  $spouse = new Person($mar["spouse"]);
	  $m.= "<td>".$spouse->simpleLink()."</td>";
	  $m.= "<td>".$mar["date_start"]."</td>";
	  $m.= "<td>".$mar["date_end"]."</td>";
	  return $m."</tr>";
  }
  /** 
  * make a simple link for father or mother or spouse
  */
  protected function otherSpouseLink(){
	global $wpdb;
	$s = "select post_id from ".$wpdb->postmeta." where meta_key='spouse' and meta_value = %d; ";
	$z = $wpdb->get_var($wpdb->prepare($s, $this->postid));
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
  protected function matching_tag_title($fromrequest = false){
	  $dob = ($fromrequest && isset($_REQUEST["date_birth"])) ? $_REQUEST["date_birth"]: $this->get("date_birth");
	  $title = ($fromrequest && isset($_REQUEST["post_title"])) ? $_REQUEST["post_title"]: $this->get("post_title");
	  
	  $app = ($dob && $dob<"1920") ? " (".substr($dob,0,4).")" : "";
	  return $title.$app;
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
    /**
    * Return a simple a tag linked to the permalink, with text which is the person's birthname.
    */
    public function simpleBirthLink(){
      $url = get_permalink($this->postid);
	  $birthname = $this->get("birthname");
      return '<a href="'.$url.'">'.($birthname ?: $this->get("post_title")).'</a>';
    }
	public function showPosted(){
		return false;
	}

}
