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
		$new["ent_required"] = $ent->get("picnode");
		$new["ent_links"] = $ent->get("index");
		
		switch($new["post_type"]){
			case "fs_person":
			$new["post_name"] = $this->personName($ent);
			$new["gender"] = $ent->getGender();
			$new["birthname"] = $ent->get("fullname");
			$new["date_birth"] = $ent->get("date_birth");
			$new["place_birth"] = $ent->get("place_birth");
			$new["date_death"] = $ent->get("date_death");
			$new["place_death"] = $ent->get("place_death");
			$new["father"] = $ent->get("father");
			$new["mother"] = $ent->get("mother");
			$new["spouse"] = $ent->get("married_to");
			$new["occupation"] = $ent->get("occupation");
			$new["date_marriage"] = $ent->get("date_wedding");
			$new["place_marriage"] = $ent->get("place_wedding");
			$new["date_baptism"] = $ent->get("date_baptized");
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

}
