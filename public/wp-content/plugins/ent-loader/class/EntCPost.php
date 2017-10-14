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
		$new["post_content"] = "pending";
		$new["ent_curly_desc"] = $ent->get("description");
		// the ent_ properties are for resolution later
		$new["ent_ref"] = $ent->key();
		if ($s=$ent->get("picnode")) $new["ent_link_featured"] = strtolower($s);
		$new["ent_links"] = $ent->get("index");
		
		$new = array_merge($new, $ent->getPropsLike("ent_link_"));
		
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
			$new["post_name"] = self::makeName($ent->get("title"));
			// what about all the camera fields?
			$this->cpDate($new, "actual_date" , $ent, "date_created");
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
	* Prepare my curly markup for Wordpress post content
	* @param $struct is an array of parsed things 
	*/
	public function wpRender($pairs){
		$o = "";
		$including = true;
		foreach ($pairs as $pair){
			switch($pair[0]){
				case "=":
				if ($including) $o.=$this->deLineEnd($pair[1]);
				break;
				case "iffor":
				$including = false;
				break;
				case "endif":
				$including = true;
				break;
				case "a":
				$args = self::get_postname_by_entref($pair[1][0]);
				if (count($pair[1])>1) {
					$args.=" ";
					if (strpos($pair[1][1]," ")!==false) $args.='"'.$pair[1][1].'"';
					else $args.=$pair[1][1];
				} 
				if ($including) $o.="[".$pair[0]." ".$args."]";
				break;
				default:
				if ($including) $o.="[".$pair[0]." ".$pair[1]."]";
			}
		}
		return $o;
	}
	/**
	* Convert the description and resave
	*/
	public function phase3($cpost){
		$m="";
		
		// resolve the descriptions
		$desc = $cpost->get("ent_curly_desc");	// should be array
		$cpost->set("post_content", $this->wpRender($desc));
		
		$cpost->on_update(2);		// re-save it, checking the custom fields and ensuring consistency
		$m.="<br />Phase 3 on ".$cpost->show();
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
	/**
	*
	*/
	protected function deLineEnd($txt){
		$t = str_replace("\n"," ",$txt);
		return str_replace("<p>","\n\n",$t);
	}
	static public function makeName($str){
		$s = str_replace(" ","_",$str);
		$s = preg_replace("#\W#","",$s);
		$s = str_replace("_","-",$s);
		$s = strtolower($s);
		return $s;
	}
	  static function get_postid_by_entref($entref){
		  global $wpdb;
		  $s = "select post_id from ".$wpdb->postmeta." PM,
			".$wpdb->posts." P 
			where P.ID = PM.post_id and P.post_status = 'publish' and 
			meta_key = 'ent_ref' and meta_value=%s;";
		  $pid = $wpdb->get_var($wpdb->prepare($s,$entref));
		  return $pid;
	  }
	  static function get_postname_by_entref($entref){
		  global $wpdb;
		  $s = "select post_name from ".$wpdb->postmeta." PM,
			".$wpdb->posts." P 
			where P.ID = PM.post_id and P.post_status = 'publish' and 
			meta_key = 'ent_ref' and meta_value=%s;";
		  $pid = $wpdb->get_var($wpdb->prepare($s,$entref));
		  return $pid;
	  }

}
