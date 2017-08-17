<?php

/**
* Wrap an array which is just a list of strings with a delimiter.
* It avoids problems encountered with serialisation or json and wp update postmeta
*/
function array_wrap($list){
    $delims = "|:;?$&^$~";  // delimiter characters to try - none of which are escape characters
    for ($k=0;$k<strlen($delims);$k++){
        $delim = substr($delims,$k,1);    // test this delimiter
        if (!array_contains($list,$delim)) return($delim.implode($delim,$list).$delim);
    }
    return($delim.implode($delim,$list).$delim);  // give up
}
/**
* Test if a wrapped array is actually all full of empty strings - it will look like a long string of delimieters like ||||
*/
function array_is_empty($string){
  if (TRACEIT) traceit("array_is_empty(".$string.")");
  if (!$string) return true; // edge case
  return trim($string,substr($string,0,1))=="";
}
function array_contains($haystack,$needle){
    for ($j=0;$j<count($haystack);$j++){
      if (strpos($haystack[$j],$needle)!==false) return true;
    }
    return false;
}
function array_unwrap($string){
    if (strlen($string)==0) return [];
    $delim = substr($string,0,1);
    $ans = explode($delim,$string);
    array_splice($ans,0,1);     // remove the end elements
    array_splice($ans,-1);
    return $ans;
}





 ?>
