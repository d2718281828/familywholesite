<?php
namespace FamilySite;

// A function for converting a date with a day accuracy to a human readable date range
class ApproxDate {

  public function __construct(){
  }

  /**
  * Express the date as naturally as possible given the within days. 
  * ultimately could have results like 1975/03 or 1975/05-08 or 1975-80 
  * @param thedate is string yyyy/mm/dd
  */
  public function convert($thedate, $within){
	  if (!$within) return $thedate;
	  if ($within<1) return $thedate;
	  if ($within<12) return $thedate." +/- ".$within." days";
	  if ($within<20) return substr($thedate,0,7);
	  // now complicated stuff about a range of months
	  if ($within<310) return substr($thedate,0,4);
	  // now complicated stuff about a range of years
	  $wyears = round($within/365);
	  return substr($thedate,0,4)." +/- ".$wyears." yrs";
  }

}
