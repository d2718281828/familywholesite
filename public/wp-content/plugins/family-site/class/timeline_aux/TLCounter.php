<?php
namespace FamilySite;
// require_once("../ApproxDate.php");	// this doesnt work, dont know why.
require_once("Aggregator.php");
/**
* This is for level 20 and above.
* 
*/
class TLCounter extends Aggregator {
	
  protected $counts = ["INTEREST"=>0,
					"BORN"=>0,
					"MARRIAGE"=>0,
					"DIED"=>0
			];
  /**
  * comparelength is the length of the substring of the date which is used to check when the new one is
  */
  protected $compareLength;
  protected $lastkey = "";
  
  protected function init(){
	  if ($this->summary >= 20) $this->compareLength = 7; 		// month 
	  if ($this->summary >= 30) $this->compareLength = 4; 		// year 
	  if ($this->summary >= 40) $this->compareLength = 3; 		// decade 
  }
/**
  * This controls the aggregation process. Compare $event with $last to see if this is part of the same
  * aggregation. If it is, return null and aggregate however that needs to be done.
  * If it isnt, then return a new aggregator of the same type with this->last set to $event.
  */
  public function nextOne($event){
	  // has the significant part of the date changed?
	  if (!$this->lastkey) $this->lastkey = substr($this->last["event_date"],0,$this->compareLength);
	  
	  if (substr($event["event_date"],0,$this->compareLength) != $this->lastkey) {
		  return $this->makeNew($event);
	  }
	  $this->countit($event);
	  $this->last = $event;		// countit compares with the last
	  return null;
  }
  protected function countit($event){
	  $newtype = $event["event_type"];
	  	  
	  if ($newtype=="INTEREST"){
		  // if it is the same pic then we will effectively ignore it.
		  if ($event["source"]==$this->last["source"]) return;
	  } 

	  // these are counted under BORN
	  if ($newtype=="SON" || $newtype=="DAUGHTER") return;
	  
	  // if there is no focus there will be two of these for each wedding
	  if ($newtype=="MARRIAGE"){
		  // this logic might fail if there are two weddings on the same day !!
		  if ($event["object"]==$this->last["object2"] && $event["object2"]==$this->last["object"]) {
			  return;
		  }
	  }
	  // now count what is left
	  $this->counts[$newtype]++;
	  
	  return ;
  }
  public function html(){
	  return $this->summaryHTML();
  }
  protected function summaryHTML(){
	  $m = "";
	  $evdate = $this->lastkey;
	  if (strlen($evdate)<4) $evdate = $evdate."0s";
	  
      $m.= '<div class="timeline-link"><div class="timeline-date">'.$evdate.'</div>';
	  
	  $m.= '<div class="timeline-body"><p>'.$this->formatCounts().'</p></div>';
	  $m.= '</div><!-- end timeline-link --->';
      return $m;
  }
  protected function formatCounts(){
	  $m =$this->counts["INTEREST"]." pictures, ";
	  $m.=$this->counts["BORN"]." births, ";
	  $m.=$this->counts["DIED"]." deaths, ";
	  $m.=$this->counts["MARRIAGE"]." weddings.";
	  return $m;
  }
}
 ?>
