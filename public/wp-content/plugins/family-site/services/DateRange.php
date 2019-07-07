<?php
namespace FamilySite;

/**
* Service function to turn a flexible date string into a centre date and date within
* We expect / separators, and expect yyyy/mm/dd or yy/mm/dd. - then separates the range.
* Two - will be treated as a full date with - separators
* yy is treated as 19yy if yy>=20, otherwise 20yy
* m is allowed for 0m
* example inputs: 48 1967-9 1984-92
* 2013/03-05 - march to may 2013
* 24/7/58 will be switched round - only because 58 cannot be a day of the month
* 9/2/7 will be 7th Feb 2009
* 
* Output dates use - separator
* If we couldnt interpret the input string then mid will be null.
*/
class DateRange {
	
	public $mid = null;		// output actual date - the centre of the range
	public $within = 0;
	public $input;
	protected $minwithin = 0;

  /**
  * $within is expected to be omitted, but if present it will override the calculated range if it is bigger
  */
  public function __construct($userdate,$within=0){
	  $this->input = $userdate;
	  $this->minwithin = $within;
	  $this->digest($userdate);
	  if ($this->minwithin > $this->within) $this->within = $this->minwithin;
  }
  /**
  * Overall - userdate is / delimited, can be range
  */
  protected function digest($userdate){
	  //$userdate = str_replace("/","-",$userdate);
	  $range = explode("-",$userdate);
	  switch(count($range)){
		  case 1: return $this->oneDate(str_replace("/","-",$userdate));
		  case 2: return $this->twoDates(str_replace("/","-",$range[0]),str_replace("/","-",$range[1]));
		  case 3: return $this->oneDate($userdate);
		  default: return null;		// dont understand, so invalid
	  }
  }
  /**
  * One date given. it is now - separated
  */
  protected function oneDate($adate){
	  $bits = $this->split($adate);
	  if (!$bits) return;
	  $bits = $this->swap($bits);
	  $bits = $this->addPad($bits);
	  switch(count($bits)){
		  case 1: return $this->range($bits[0],1,1,$bits[0],12,31);
		  case 2: return $this->range($bits[0],$bits[1],1,$bits[0],$bits[1],31);
		  case 3: $this->mid = implode("-",$bits);
			break;
		  default: return null;
	  }
	  return null;
  }
  /**
  * date range given, todate could be abbreviated. - separated
  */
  protected function twoDates($adate, $todate){
	  $bits = $this->split($adate);
	  if (!$bits) return;
	  $bitsto = $this->split($todate);
	  if (count($bitsto)>count($bits)) return;	// this doesnt make sense
	  
	  // not swapping, i dont think it makes sense with a range
	  $bits = $this->addPad($bits);
	  
	  $bits2 = $this->overlay($bits,$bitsto); 
	  // bits and bits2 have the same length now
	  switch(count($bits)){
		  case 1: return $this->range($bits[0],1,1,$bits2[0],12,31);
		  case 2: return $this->range($bits[0],$bits[1],1,$bits2[0],$bits2[1],31);
		  case 3: return $this->range($bits[0],$bits[1],$bits[2],$bits2[0],$bits2[1],$bits2[2]);
		  default: return null;
	  }
	  return null;
  }
  /**
  * split by hyphens and check the number
  */
  protected function split($adate){
	  $z = explode("-",$adate);
	  if (count($z)>3) return null;
	  return $z;
  }
  /**
  * if it's a triple and if last cant be day then swap round
  */
  protected function swap($bits){
	  if (count($bits)!=3) return $bits;
	  if ($bits[2]<=31) return $bits;
	  return [$bits[2], $bits[1], $bits[0]];
  }
  /**
  * if the year is 2 digits then padd it. yEAR IS DEFINITELY [0] NOW
  * $bits could have length up to 3, it doesnt have to be a complete date
  */
  protected function addPad($bits){
	  if (strlen($bits[0])==2) {
		  if ($bits[0]<"20") $bits[0] = "20".$bits[0];
		  else $bits[0] = "19".$bits[0];
	  }
	  if (count($bits)==1) return $bits;
	  if (strlen($bits[1])==1) $bits[1] = "0".$bits[1];
	  if (count($bits)==2) return $bits;
	  if (strlen($bits[2])==1) $bits[2] = "0".$bits[2];
	  return $bits;
  }
  /**
  * COmbine the end of the range with the start to get the full thing
  * eg 18 over 1914 gives 1918
  * but [5,7] over [2012,03,30] gives [2012, 05, 07]
  */
  protected function overlay($adate, $over){
	  $ix = count($adate)-count($over);
	  $adate[$ix] = $this->strover($adate[$ix], $over[0]);
	  // copy across any remaining
	  for ($k=1; $k<count($over); $k++){
		  $adate[$ix+$k] = strlen($over[$k])==1 ? "0".$over[$k] : $over[$k];
	  }
	  return $adate;
  }
  /**
  * Do the overlay thing for just a string - 1914,18 => 1918
  */
  protected function strover($s1,$s2){
	  if (strlen($s2)>=strlen($s1)) return $s2;	// > doesnt make much sense. = does
	  $x = substr($s1,0,strlen($s1)-strlen($s2));
	  return $x.$s2;
  }
  /**
  * given two dates in y m d form work out the date in the middle and the within
  */
  protected function range($a0,$a1,$a2,$b0,$b1,$b2){
	  $d1 = date_create($a0."/".$a1."/".$a2);
	  $d2 = date_create($b0."/".$b1."/".$b2);
	  //error_log("calculating range ".$a0."/".$a1."/".$a2." = ".$d1->format("Y-m-d"));
	  $int = $d1->diff($d2);
	  $within = floor(($int->days)/2);
	  $dmid = $d1->add(new \DateInterval('P'.$within.'D'));
	  $this->within = $within;
	  $this->mid = $dmid->format("Y-m-d");
	  //error_log("end result ".$this->mid." +/- ".$within);
	  return null;
  }
  public function show(){
	  return $this->mid."+/-".$this->within;
  }
  /**
  * If the above not working, an alternative
  */
  protected function matchit($userdate){
	  $pats = [
		["(\d\d\d\d)" ],
		["(\d\d\d\d)-(\d+)" ],
		["(\d\d\d\d)/()"]
	  ];
  }

}
