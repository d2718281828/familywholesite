<?php
namespace FamilySite;
// require_once("../ApproxDate.php");	// this doesnt work, dont know why.
require_once("Aggregator.php");
/**
* This is for a detailed but unfocussed timeline.
*/
class Unique extends Aggregator {
	
  protected $otherparty = "";		// when consolidating events with multiple objects.

  /**
  * This controls the aggregation process. Compare $event with $last to see if this is part of the same
  * aggregation. If it is, return null and aggregate however that needs to be done.
  * If it isnt, then return a new aggregator of the same type with this->last set to $event.
  */
  public function nextOne($event){
	  $newtype = $event["event_type"];
	  $oldtype = $this->last["event_type"];
	  
	  // In general if the types arent the same they will definitely not be aggregated
	  if ($oldtype != $newtype) return $this->makeNew($event);
	  
	  if ($newtype=="INTEREST"){
		  // if it is the same pic then we will effectively ignore it.
		  if ($event["source"]==$this->last["source"]) return null;
	  } 
	  
	  if ($newtype=="SON" || $newtype=="DAUGHTER"){
		  if ($event["object"] == $this->last["object"]){
			  // store the other party and then ignore the new record
			  $object = \CPTHelper\CPTHelper::make($event["object"],$event["object_type"]);
			  $this->otherparty = $object->simpleLink();
			  return null;
		  }
	  }
	  
	  return $this->makeNew($event);
  }
  
  protected objectName(){
      $object = \CPTHelper\CPTHelper::make($this->last["object"],$this->last["object_type"]);
	  $links = [];
	  if ($this->otherparty) $links[] = $this->otherparty;
	  $this->otherparty = "";
	  if ($object) $links[] = $object->simpleLink();
	  return join(" and ",$links)." ";
  }
}
 ?>