<?php
namespace FamilySite;
use CPTHelper\MultiValued;
/* FieldHelper Class to store several serialised fields representing one marriage, in each instance of a multi valued thing.
Fields are spouse, date start, date end. Possible dropdowns for wedding event and place
*/
class Marriages extends MultiValued {


    protected function fieldInput($k,$value){
      if (WP_DEBUG) error_log("Marriages fieldinput ****************** value=".print_r($value,true));
	  $allVals = $this->unPack($value);
      //$m = "Spouse: <input type='text' class='metafield metasub_p' name='".$this->id."_sp[".$k."]' value='".esc_attr($allVals["spouse"])."'>";
	  $m = "Spouse: ".\CptHelper::selector($this->id."_sp[".$k."]", "fs_person",$allVals["spouse"]);
      $m.= "Date started: <input type='date' class='metafield metasub_d' name='".$this->id."_ds[".$k."]' value='".esc_attr($allVals["date_start"])."'>";
      $m.= "Ended: <input type='date' class='metafield metasub_d' name='".$this->id."_de[".$k."]' value='".esc_attr($allVals["date_end"])."'>";
      $m.= "<input type='hidden' name='".$this->id."_old[".$k."]' value='".esc_attr($value)."'>";
      return $m;
    }
    public function rqValue(){
        $res = [];
        for ($k=0; $k<count($_REQUEST[$this->id."_sp"]); $k++){
          if ($_REQUEST[$this->id."_sp"][$k]) {
			  $vals = [];
			  $vals["spouse"] = $_REQUEST[$this->id."_sp"][$k];
			  $vals["date_start"] = $_REQUEST[$this->id."_ds"][$k];
			  $vals["date_end"] = $_REQUEST[$this->id."_de"][$k];
			  $res[] = json_encode($vals);
		  }
        }
        return $res;
    }

	protected function unPack($str){
		if ($str==null || $str=="") return ["spouse"=>"","date_start"=>"","date_end"=>""];
		return json_decode($str,true);
	}


}




?>
