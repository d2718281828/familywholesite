<?php
namespace EntLoader;
use CPTHelper\CptHelper;

class EntCPost  {
	
	protected $entloader;
		
	public function __construct($loader){
		$this->entloader = $loader;
	}
	// I need to keep some values which arent part of the CPT - internal housekeeping. they will be prefixed ent_
	// some of these values cannot be set until after all nodes have been created.
	public function make($ent){
		$new = [];
		$enttype = $ent->get("type");
		$new["post_type"] = $this->xlateType($enttype);
		$new["post_title"] = $ent->get("title");
		// this will need to be translated when all the cposts are in.
		$new["post_content"] = $this->xlateText($ent->get("description"));
		// the ent_ properties are for resolution later
		$new["ent_ref"] = $ent->key();
		$new["ent_required"] = strtolower($ent->get("picnode"));
		$new["ent_links"] = $ent->get("index");
		
		switch($new["post_type"]){
			case "fs_person":
			$new["post_name"] = $this->personName($ent);
			$new["gender"] = $ent->getGender();
			$this->    cp($new, "birthname" , $ent, "fullname");
			$this->cpDate($new, "date_birth" , $ent, "date_birth");
			$this->    cp($new, "place_birth" , $ent, "place_birth");
			$this->cpDate($new, "date_death" , $ent, "date_death");
			$this->    cp($new, "place_death" , $ent, "place_death");
			$this->cpLink($new, "father" , $ent, "father");
			$this->cpLink($new, "mother" , $ent, "mother");
			$this->cpLink($new, "spouse" , $ent, "married_to");
			$this->    cp($new, "occupation" , $ent, "occupation");
			$this->cpDate($new, "date_marriage" , $ent, "date_wedding");
			$this->    cp($new, "place_marriage" , $ent, "place_wedding");
			$this->cpDate($new, "date_baptism" , $ent, "date_baptized");
			break;
			
			case "fs_place":
			$new["post_name"] = $this->itemName($ent);
			break;
			
			case "fs_event":
			$new["post_name"] = $this->itemName($ent);
			break;
			
			case "post":
			$new["post_name"] = $this->itemName($ent);
			break;
		}
		
		return CptHelper::make($new);
	}
	protected function cp(&$new, $newprop , $ent, $entprop){
		$val = $ent->get($entprop);
		if ($val) $new[$newprop] = $val;
	}
	protected function cpDate(&$new, $newprop , $ent, $entprop){
		$val = $ent->get($entprop);
		if ($val) $new[$newprop] = $this->xdate($val);
	}
	protected function cpLink(&$new, $newprop , $ent, $entprop){
		$val = $ent->get($entprop);
		if ($val) $new[$newprop] = strtolower($val);
	}
	/**
	* Convert link references - this will be tricky.
	*/
	protected function xlateType($type){
		$tpe = rtrim($type);
		switch($type){
			case "person": return "fs_person";
			case "place": return "fs_place";
			case "event": return "fs_event";
			default: return "post";
		}
		return "post";
	}
	/**
	* Convert link references - this will be tricky.
	*/
	protected function xlateText($txt){
		return $txt;
	}
	/**
	* Make a name from the title, including the dob
	*/
	protected function personName($ent){
		$year = substr($ent->get("date_birth"),0,4);
		return $this->itemName($ent).(($year && $year<"1920") ? "-".$year : "");
	}
	/**
	* Make a name from the title
	*/
	protected function itemName($ent){
		$txt = str_replace(" ","-",strtolower($ent->get("title")));
		$txt = str_replace("(","",$txt);
		$txt = str_replace(")","",$txt);
		$txt = str_replace("'","",$txt);
		$txt = str_replace("\"","",$txt);
		$txt = str_replace("--","-",$txt);
		return $txt."_test";
	}
	protected function xdate($str){
		return str_replace("/","-",$str);
	}

}
