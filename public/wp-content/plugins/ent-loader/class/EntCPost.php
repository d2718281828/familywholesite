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
		$new["post_content"] = $ent->get("description");
		// the ent_ properties are for resolution later
		$new["ent_ref"] = $ent->key();
		if ($s=$ent->get("picnode")) $new["ent_link_featured"] = strtolower($s);
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
			$this->cpLink($new, "ent_link_father" , $ent, "father");
			$this->cpLink($new, "ent_link_mother" , $ent, "mother");
			$this->cpLink($new, "ent_link_spouse" , $ent, "married_to");
			$this->    cp($new, "occupation" , $ent, "occupation");
			$this->cpDate($new, "date_marriage" , $ent, "date_wedding");
			$this->    cp($new, "place_marriage" , $ent, "place_wedding");
			$this->cpDate($new, "date_baptism" , $ent, "date_baptized");
			break;
			
			case "fs_place":
			$new["post_name"] = self::makeName($ent->get("title"));
			break;
			
			case "fs_event":
			$new["post_name"] = self::makeName($ent->get("title"));
			break;
			
			case "post":
			$new["post_name"] = self::makeName( $ent->get("title"));
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
	public function xlateText($txt){
		$o = "";
		$pairs = $this->parseit($txt);
		$including = true;
		foreach ($pairs as $pair){
			switch($pair[0]){
				case "=":
				$o.=$pair[1];
				break;
				default:
				$o.="[".$pair[0]."~".$pair[1]."]";
			}
		}
		return $o;
	}
	// eeek - this code should bee in ent.
	protected function parseit($txt){
		$o = [];
		$p = 0;
		while ($p < strlen($txt)){
			$lb = strpos($txt,"{",$p);
			if ($lb===false){
				$o[] = ["=", substr($txt,$p)];
				return $o;
			}
			if ($lb>$p) $o[] = ["=", substr($txt,$p,$lb-$p)];
			$rb = strpos($txt,"}",$lb+1);
			if ($rb===false) $rb = strlen($txt);
			$bl = strpos($txt," ",$lb+1);
			if ($bl===false || $bl > $rb) $arg = "";
			else $arg = substr($txt, $bl+1, $rb-$bl-1);
			$o[] = [substr($txt,$lb+1, $bl-$lb-1), $arg];
			$p = $rb+1;
		}
		return $o;
	}
	/**
	* Convert the description and resave
	*/
	public function phase3($cpost){
		$m="";
		$desc = $cpost->get("post_content");
		$cpost->set("post_content", $this->xlateText($desc));
		$cpost->on_update(2);		// re-save it, checking the custom fields and ensuring consistency
		$m.="<br />Phase 3 on ".$cpost->get("post_title");
		return $m;
	}
	/**
	* Make a name from the title, including the dob
	*/
	protected function personName($ent){
		$year = substr($ent->get("date_birth"),0,4);
		return self::makeName($ent->get("title")).(($year && $year<"1920") ? "-".$year : "");
	}
	protected function xdate($str){
		return str_replace("/","-",$str);
	}
	static public function makeName($str){
		$s = str_replace(" ","_",$str);
		$s = preg_replace("#\W#","",$s);
		$s = str_replace("_","-",$s);
		$s = strtolower($s);
		return $s;
	}

}
