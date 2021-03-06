<?php
namespace FamilySite;
// require_once("../ApproxDate.php");	// this doesnt work, dont know why.
require_once("Aggregator.php");
/**
* This is for focussed and unfocussed timelines, level 0 or 10.
* 
*/
class Unique extends Aggregator {
	
  protected $otherparty = "";		// when consolidating events with multiple objects: A and B had a son
  protected $pictureCount = 0;

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
	  $newtype = $event["event_type"];
	  $oldtype = $this->last["event_type"];
	  
	  // suppress BORN records because they will also be a SON or DAUGHTER
	  if ($newtype=="BORN") return null;
	  
	  if ($this->isDuplicate($event)) return null;	// all the Grandson etc records are duplicates if no focus

	  // In general if the types arent the same they will not be aggregated
	  if ($oldtype != $newtype) return $this->makeNew($event);
	  	  
	  if ($newtype=="INTEREST" || $newtype=="EVENT"){
		  // if it is the same pic then we will effectively ignore it.
		  // todo i think it's a general rule if the sources are the same and no focus then ignore
		  if ($event["source"]==$this->last["source"]) return null;
	  } 
	  
	  // if it's another pic and summary level 10 then ignore it but count the pictures ignored
	  if ($newtype=="INTEREST" && $this->summary>=10){
		  $this->pictureCount++;
		  return null;
	  } 


	  if ($newtype=="SON" || $newtype=="DAUGHTER"){
		  if ($event["source"] == $this->last["source"]){
			  // store the other party and then ignore the new record
			  $object = \CPTHelper\CPTHelper::make($event["object"],$event["object_type"]);
			  $this->otherparty = $object->simpleLink();
			  return null;
		  }
	  }
	  if ($newtype=="MARRIAGE"){
		  // this logic might fail if there are two weddings on the same day !!
		  if ($event["object"]==$this->last["object2"] && $event["object2"]==$this->last["object"]) {
			  $object = \CPTHelper\CPTHelper::make($event["object2"],$event["object2_type"]);
			  $this->otherparty = $object->simpleLink();
			  return null;
		  }
	  }
	  
	  return $this->makeNew($event);
  }
  protected function marriageLine($spouse){
	  if ($this->focus) return "Marriage to ".$spouse;
	  
	  return $this->otherparty." and ".$spouse." married.";
  }
  /**
  * The date link that shows in the header for the entry
  */
  protected function dateLink($evdate, $yyyymmdd){
	  if ($this->summary == 0) return $evdate;
	  
	  // detail level pictures for one day
 	  $url = $this->pagelink(0,$yyyymmdd,$yyyymmdd);
	  return "<a href='$url'>$evdate</a>";
 }
  /**
  *
  */
  protected function objectName(){
	  if ($this->focus) return "";
	  
      $object = \CPTHelper\CPTHelper::make($this->last["object"],$this->last["object_type"]);
	  $links = [];
	  if ($this->otherparty) $links[] = $this->otherparty;
	  $this->otherparty = "";
	  if ($object) $links[] = $object->simpleLink();
	  return join(" and ",$links)." ";
  }
}
 ?>
