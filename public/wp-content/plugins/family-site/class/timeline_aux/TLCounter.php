<?php
namespace FamilySite;
// require_once("../ApproxDate.php");	// this doesnt work, dont know why.
require_once("Aggregator.php");

/* TODO
the first event isnt counted...
LINKS
the REQUEST params need to be moved to the timeline instead of the shortcode.
After testing, may need to add limits on summary0 without from and to
*/

/**
* This is for level 20 and above. It just counts births, deaths etc in sections of months, years or decades.
* 
*/
class TLCounter extends Aggregator {
	
  protected $counts = ["INTEREST"=>0,
					"BORN"=>0,
					"MARRIAGE"=>0,
					"DIED"=>0
			];
  /**
  * compare is the length of the substring of the date which is used to check when the new one is
  */
  protected $lev;
  protected $lastkey = "";
  
  protected function init(){
	  if ($this->summary >= 20) $this->lev = ["compare" => 7,
											"drill"=>10,
											"lab"=>"",
											"from" => "/01",
											"to" => "/31",
											]; 		// month 
	  if ($this->summary >= 30) $this->lev = ["compare" => 4 
											"drill"=>20,
											"lab"=>"",
											"from" => "/01/01",
											"to" => "/12/31",
											]; 		// year 
	  if ($this->summary >= 40) $this->lev = ["compare" => 3 
											"drill"=>30,
											"lab"=>"0s",
											"from" => "0/01/01",
											"to" => "9/12/31",
											]; 		// decade 
  }
/**
  * This controls the aggregation process. Compare $event with $last to see if this is part of the same
  * aggregation. If it is, return null and aggregate however that needs to be done.
  * If it isnt, then return a new aggregator of the same type with this->last set to $event.
  */
  public function nextOne($event){
	  // has the significant part of the date changed?
	  if (!$this->lastkey) $this->lastkey = substr($this->last["event_date"],0,$this->lev["compare"]);
	  
	  if (substr($event["event_date"],0,$this->lev["compare"]) != $this->lastkey) {
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
	  $evdate = $this->lastkey.$this->lev["lab"];
	  $url = $this->pagelink($this->lev["drill"],$this->lastkey.$this->lev["from"],$this->lastkey.$this->lev["to"]);
	  
      $m.= '<div class="timeline-link"><div class="timeline-date"><a href="'.$url.'">'.$evdate.'</a></div>';
	  
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
