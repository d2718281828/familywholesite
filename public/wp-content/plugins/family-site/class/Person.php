<?php
namespace FamilySite;
use CPTHelper\CptHelper;

// represents an instance of the Person CPT
class Person extends FSPost {

  protected $taxes = [];
  protected $loggedin = false;
  protected $sensitive = true;	// is the persson object sensitive (i.e. still around)
  
  public function init(){
	// is the user logged in?
	$this->loggedin = is_user_logged_in();
	$this->sensitive = $this->get("date_death") ? false : true;
	$this->showDates = !$this->sensitive || $this->loggedin;
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
		if ($userdata->description) $m.= $this->infoBit("About",$userdata->description);
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
    return $tl->html().$this->afterIndexSection();
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
  public function on_update($req = 0){
		$post_id = $this->postid;
		parent::on_update($req);
		if (WP_DEBUG) error_log("Person::on_update for ".$post_id.", REQ=".$req);
		TimeLine::clearSource($post_id);
		
		//if (WP_DEBUG) error_log("Person::on_update date_birth= ".$this->getcf($req,"date_birth","none"));
		if ($birthdate=$this->getcf($req,"date_birth")){
			$place = $this->getcf($req,"place_birth",0);
			if (WP_DEBUG) error_log("Person::on_update GOT PLACE ".$place." FOR ".$post_id. ", REQ".$req );
			TimeLine::add1($birthdate, $post_id, "BORN", $place, 0);
			// add mother and father too
			$gender = $this->getcf($req,"gender");
			$type = ($gender=="M") ? "SON" : "DAU";
			/*
			if ($mum=$this->getcf($req,"mother",0)){
				TimeLine::addChild($s, $post_id, $type, $mum, $place, 0);
			}
			if ($dad=$this->getcf($req,"father",0)){
				TimeLine::addChild($s, $post_id, $type, $dad, $place, 0);
			}
			*/
			$this->setAllAncestors($birthdate, $post_id, $type, $post_id, $birthdate, $place);
		}
		if ($s=$this->getcf($req,"date_death")){
			$place = $this->getcf($req,"place_death", 0);
			TimeLine::add1($s, $post_id, "DIED", $place,0 );
			// add children
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
  /**
  * On saving a person post, add a record of the birth to all ancestors who were alive at the time.
  * The first 3 parms are what you are posting, the rest are whho to post it for
  */
  protected function setAllAncestors($birthdate, $post_id, $childtype, $ancestor, $ancestorbirth, $place){
	  global $wpdb;

	  // do outer join to the parents (PAR) of post_id (P), pulling back parents
	  // so the outer join brings back those with and without a date of death
	  $sql = "select P.meta_key,P.meta_value as parid , PAR.meta_value as pardied
	  from ".$wpdb->prefix."postmeta as P
	  LEFT OUTER JOIN ".$wpdb->prefix."postmeta as PAR on (PAR.post_id = P.meta_value 
	  and PAR.meta_key = 'date_death') 
	  where P.post_id=%d and P.meta_key in ('father','mother') ";
	  
	  $res = $wpdb->get_results($wpdb->prepare($sql, $ancestor), ARRAY_A);
	  if (WP_DEBUG) error_log("Person::setGrandchildren for ".$post_id." finds ".count($res));
	  
	  for ($p = 0; $p<count($res); $p++){
		  $parent = $res[$p];
		  
		  /* Recurse upwards to find all ancestors alive at $birthdate
		  * The problem is how to terminate the recursion, especially as some have a date of death,
		  * and some dont have a date of birth either.
		  * First get approx parent death: if we dont have the actual, just to limit timeline entries
		  */
		  $cparent = CptHelper::make($parent["parid"],"fs_person");
		  if ($x = $cparent->get("date_birth")) $parentborn = $x;
		  elseif ($x = $cparent->get("date_baptism")) $parentborn = $this->addYear($x, -5);
		  else $parentborn = $this->addYear($ancestorbirth, -15);		// this cant be  null
			  
		  // here we assume max lifetime 90 - that just means we dont add timeline entries after age 90
		  // unless we know that the person lived longer than that from their date of death.
		  if ($parent["pardied"]) $parentdied = $parent["pardied"];
		  else $parentdied = $this->addYear($parentborn, 90);
		  		  
		  if ($parentdied >= $birthdate) {
			// add the son/daughter to the parent's timeline
			TimeLine::addChild($birthdate, $post_id, $childtype, $parent["parid"], $place, 0);
		  }
		  
		  // recursion is limited by death date on parents. If parent died > 50 years before dont go up
		  // the 50 is arbitrary... but we have to allow that the grandparents might outlive the parents
		  // parentborn is passed up the tree just as an estimate for when birth date and death date are missing.
		  if ($this->addYear($parentdied,50)> $birthdate){
			$this->setAllAncestors($birthdate, $post_id, "G".$childtype, $parent["parid"], $parentborn, $place);
		  }
		  // the parent's death will be added to the childrens timeline by setAllDescendants
	  }  
	   
  }
  /** WARNING: THIS IS NOT READY YET AND IT IS NOT USED
  * On saving a person post, add a record of the death to all descendants who were alive at the time.
  * The first 3 parms are what you are posting, the rest are whho to post it for
  */
  protected function setAllDescendants($deathdate, $post_id, $parenttype, $descendent, $descendentbirth, $place){
	  global $wpdb;

	  // do outer join to the children (KID) of descendent (P), pulling back parents
	  // so the outer join brings back those with and without a date of death
	  $sql = "select P.meta_key,P.meta_value as kidid , KID.meta_value as kidborn
	  from ".$wpdb->prefix."postmeta as P
	  LEFT OUTER JOIN ".$wpdb->prefix."postmeta as KID on (P.post_id = KID.meta_value 
	  and KID.meta_key = 'date_birth') 
	  where P.post_id=%d and KID.meta_key in ('father','mother') ";
	  
	  $res = $wpdb->get_results($wpdb->prepare($sql, $descendent), ARRAY_A);
	  if (WP_DEBUG) error_log("Person::setGrandchildren for ".$post_id." finds ".count($res));
	  
	  for ($p = 0; $p<count($res); $p++){
		  $parent = $res[$p];
		  
		  /* Recurse upwards to find all ancestors alive at $birthdate
		  * The problem is how to terminate the recursion, especially as some have a date of death,
		  * and some dont have a date of birth either.
		  * First get approx parent death: if we dont have the actual, just to limit timeline entries
		  */
		  $cparent = CptHelper::make($parent["parid"],"fs_person");
		  if ($x = $cparent->get("date_birth")) $parentborn = $x;
		  elseif ($x = $cparent->get("date_baptism")) $parentborn = $this->addYear($x, -5);
		  else $parentborn = $this->addYear($ancestorbirth, -15);		// this cant be  null
			  
		  // here we assume max lifetime 90 - that just means we dont add timeline entries after age 90
		  // unless we know that the person lived longer than that from their date of death.
		  if ($parent["pardied"]) $parentdied = $parent["pardied"];
		  else $parentdied = $this->addYear($parentborn, 90);
		  		  
		  if ($parentdied >= $birthdate) {
			// add the son/daughter to the parent's timeline
			TimeLine::addChild($birthdate, $post_id, $childtype, $parent["parid"], $place, 0);
		  }
		  
		  // recursion is limited by death date on parents. If parent died > 50 years before dont go up
		  // the 50 is arbitrary... but we have to allow that the grandparents might outlive the parents
		  // parentborn is passed up the tree just as an estimate for when birth date and death date are missing.
		  if ($this->addYear($parentdied,50)> $birthdate){
			$this->setAllAncestors($birthdate, $post_id, "G".$childtype, $parent["parid"], $parentborn, $place);
		  }
		  // the parent's death will be added to the childrens timeline by setAllDescendants
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
