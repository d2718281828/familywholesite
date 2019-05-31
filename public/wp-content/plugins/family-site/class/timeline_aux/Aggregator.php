<?php
namespace FamilySite;
require_once("../ApproxDate.php");

/**
*	Base class for timeline aggregator.
* It assumes that the records will be sorted, and it checks each new record for whether it needs to be 
*  aggregated
*/
class Aggregator {

  protected $last = null;

  public function __construct($focus = null){
    $this->focus = $focus;
	$this->ad = new ApproxDate();
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
	  $res = $this->makeNew();
	  $res->last = $event;
	  return $res;
  }
  /**
  * Output the suitably aggregated record.
  */
  public function html(){
	  if (!$this->last) return "";
	  return $this->detailHtml();
  }
  protected function makeNew(){
	  $className = get_class($this);
	  $res = new $className($this->focus);
	  return $res;
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
  */
  protected function detailHtml(){
	  $event = $this->last;
	  $m = "";
      $source = \CPTHelper\CPTHelper::make($event["source"],$event["source_type"]);
	  
	  $evdate = $this->ad->full($event["event_date"],$event["date_within"]);
	  
      $m.= '<div class="timeline-link"><div class="timeline-date">'.$evdate.'</div>';
	  switch($event["event_type"]){
		case "BORN":
		$m.= '<div class="timeline-body">Born</div>';
		break;
		case "DIED":
		$m.= '<div class="timeline-body">Passed away</div>';
		break;
		case "SON":
		case "DAUGHTER":
		$m.= '<div class="timeline-body">'.$event["event_type"].' '.$source->simpleBirthLink().'</div>';
		break;
		case "MARRIAGE":
		if ($event["object2"]){
			$spouse = \CPTHelper\CPTHelper::make($event["object2"],$event["object2_type"]);
			$m.= '<div class="timeline-body">Marriage to '.$spouse->simpleLink().'</div>';
		}
		break;
		default:
		$m.= '<div class="timeline-pic">'.$source->link().'</div><!-- end timeline-pic --->';
	  }
	  $m.= '</div><!-- end timeline-link --->';
      return $m;
  }
}
 ?>
