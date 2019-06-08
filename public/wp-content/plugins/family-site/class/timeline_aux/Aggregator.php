<?php
namespace FamilySite;
// require_once("../ApproxDate.php");	// this doesnt work, dont know why.

/**
*	Base class for timeline aggregator.
* It assumes that the records will be sorted, and it checks each new record for whether it needs to be 
*  aggregated
* This base class treats every line as new, so it is for the focused, detailed timeline.
*/
class Aggregator {

  protected $last = null;

  /**
  * The aummary level for an Aggregator should be 0.
  */
  public function __construct($summarylevel = 0, $focus = null){
    $this->focus = $focus;
	$this->summary = $summarylevel;
	$this->ad = new ApproxDate();
	
	$this->init();
  }
  protected function init(){
  }
  public function hasData(){
	  return ($this->last !== null);
  }
  /**
  * This controls the aggregation process. Compare $event with $last to see if this is part of the same
  * aggregation. If it is, return null and aggregate however that needs to be done.
  * If it isnt, then return a new aggregator of the same type with this->last set to $event.
  */
  public function nextOne($event){
	  if (!$this->last){
		  $this->addEvent0($event);
		  return null;
	  }
	  return $this->makeNew($event);
  }
  /**
  * Output the suitably aggregated record.
  */
  public function html(){
	  if (!$this->last) return "";
	  return $this->detailHtml();
  }
  protected function makeNew($event){
	  $className = get_class($this);
	  $res = new $className($this->summary, $this->focus);
	  $res->addEvent0($event);
	  return $res;
  }
  protected function addEvent0($event){
	  $this->last = $event;
  }
  /* timeline types
  source is the post that writes these when being saved
  object is the filter for a particular timeline
  
  source		object	object2
  person  BORN  samepers place
  person  SON   parent
  person  DIED	samepers place
  person  MARRIED samepers spouse place
  picture PIC   tagged-pers
  picture PIC   tagged-event
  picture PIC   tagged-place
  event   EVENT  tagged-place
  event   EVENT  tagged-person (wedding)
  
  plus derived types GSON, GDAUGHTER etc. 
  */
  protected function detailHtml(){
	  $event = $this->last;
	  $m = "";
      $source = \CPTHelper\CPTHelper::make($event["source"],$event["source_type"]);
	  
	  $evdate = $this->ad->full($event["event_date"],$event["date_within"]);
	  
      $m.= '<div class="timeline-link"><div class="timeline-date">'.$this->dateLink($evdate, $event["event_date"]).'</div>';
	  switch($event["event_type"]){
		case "BORN":
		$m.= '<div class="timeline-body">'.$this->objectName().'Born</div>';
		break;
		case "DIED":
		$m.= '<div class="timeline-body">'.$this->objectName().'Passed away</div>';
		break;
		case "SON":
		case "DAUGHTER":
		case "GSON":
		case "GDAUGHTER":
		case "GGSON":
		case "GGDAUGHTER":
		case "GGGSON":
		case "GGGDAUGHTER":
		$evtype = $this->eventDescription($event["event_type"]);
		$m.= '<div class="timeline-body">'.$this->objectName().$evtype.' '.$source->simpleBirthLink().'</div>';
		break;
		case "MARRIAGE":
		if ($event["object2"]){
			$spouse = \CPTHelper\CPTHelper::make($event["object2"],$event["object2_type"]);
			$m.= '<div class="timeline-body">'.$this->marriageLine($spouse->simpleLink()).'</div>';
		}
		break;
		default:
		$m.= '<div class="timeline-pic">'.$source->link().'</div><!-- end timeline-pic --->';
	  }
	  $m.= '</div><!-- end timeline-link --->';
      return $m;
  }
  protected function eventDescription($type){
	  $res = strtolower($type);
	  $res = str_replace("gs","xrands",$res);
	  $res = str_replace("daug","daux",$res);
	  $res = str_replace("gd","xrandd",$res);
	  $res = str_replace("g","xreat-",$res);
	  $res = str_replace("x","g",$res);
	 
	  return ucfirst($res);
  }
  protected function dateLink($evdate, $yyyymmdd){
	  return $evdate;
  }
  protected function marriageLine($spouse){
	  return "Marriage to ".$spouse;
  }
  /**
  * Return the object name where possible. For a focussed thing the object is always the focus so not needed
  */
  protected function objectName(){
	  return "";
  }
  /**
  * Link to this page.
  */
  protected function pagelink($summary, $from, $to){
	global $wp;
	$current_url = $this->root_url(true);
	$x = explode("?",$current_url);
	$current_url = $x[0];	// remove the current parameter list
	
	// replace current requests with the new values
	if ($summary!==null && $summary !== "") $_REQUEST["summary"] = $summary;
	if ($from) $_REQUEST["from"] = $from;
	if ($to) $_REQUEST["to"] = $to;
	
	$q = "";
	foreach($_REQUEST as $prop=>$var){
		$q.="&".$prop."=".$var;
	}
	return $current_url."?".substr($q,1);
  }
  public function root_url($uri=false) { 
	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : ""; // an s if it is https 
	$p=explode("/",strtolower($_SERVER["SERVER_PROTOCOL"])); 
	$protocol = $p[0].$s; 
	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]); 
	if ($s && $_SERVER["SERVER_PORT"]==443) $port = ""; 
	$z=$protocol."://".$_SERVER['SERVER_NAME'].$port; 
	if ($uri) return $z.$_SERVER['REQUEST_URI']; 
	return $z; 
  }
  /**
  * used by unique and TLcounter
  */
  protected function isDuplicate($type){
	  if ($this->focus) return false;
	  
	  if (preg_match("/^G+SON$/", $type)) return true;
	  if (preg_match("/^G+DAUGHTER$/", $type)) return true;
	  return false;
  }
}
 ?>
