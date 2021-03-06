<?php
namespace EntLoader;
use CPTHelper\CptHelper;

/**
This class is a factory class which converts an Ent into a CPost in the make method
*/
class EntCPost  {
	
	protected $entloader;
		
	public function __construct($loader){
		$this->exifFields = ["ApertureFNumber","camera_make","camera_model","Orientation","fnumber",
			"iso","exposure","flash","focal_length","max_aperture","width","height",
		];
		
		$this->entloader = $loader;
	}
	/**
	* Takes an ent and spits out a cpost object
	*/
	// I need to keep some values which arent part of the CPT - internal housekeeping. they will be prefixed ent_
	// some of these values cannot be set until after all nodes have been created.
	public function make($ent){
		$new = [];
		$enttype = $ent->get("type");
		$new["post_type"] = $this->xlateType($enttype);
		$new["post_title"] = $ent->get("title");
		if ($ent->key()=="slub") $new["post_title"] = "Derek and Anna's wedding";
		
		// this will need to be translated when all the cposts are in.
		$new["post_content"] = "pending";
		$new["ent_curly_desc"] = $ent->get("description");		// for subsequent translation, after everything is loaded.
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
			$this->cpDate($new, "actual_date" , $ent, "date_created");
			$this->    cp($new, "date_within" , $ent, "date_within");
			break;
			
			case "post":
			$new["post_name"] = self::makeName($ent->get("title"));
			$new["uploader_ref"] = $ent->key();
			
			// all the EXIF camera fields
			foreach($this->exifFields as $ex){
				$val = $ent->get($ex);
				if ($val) $new["exif"][$ex] = $val;				
			}
			
			$this->cpDate($new, "actual_date" , $ent, "date_created");
			$this->    cp($new, "date_within" , $ent, "date_within");
			$creator = $ent->get("created_by");
			if ($creator) {
				$maker = $this->entloader->getCreator($creator);		
				//echo "<p>created by example : ".$creator." = ".$maker[0]." / ".$maker[1];
				if ($maker[0]) $new["maker"] = $maker[0];
				if ($maker[1]) $new["maker_text"] = $maker[1];
			}
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
				$name_type = self::get_postdata_by_entref($pair[1][0]);
				$args = $name_type[0];
				if (count($pair[1])>1) {
					$args.=" ";
					if (strpos($pair[1][1]," ")!==false) $args.='"'.$pair[1][1].'"';
					else $args.=$pair[1][1];
				} 
				if ($including) $o.="[".$this->cpostName($name_type[1])." ".$args."]";
				break;
				default:
				if ($including) $o.="[".$pair[0]." ".$pair[1]."]";
			}
		}
		return $o;
	}
	/**
	* return the name of the [a] tag for a given post type.
	*/
	public function cpostName($posttype){
		switch($posttype){
			case 'fs_person':
				return 'person';
			case 'fs_event':
				return 'event';
			case 'fs_place':
				return 'place';
			default:
				return 'interest';
		}
		return 'interest';
	}
	/**
	* Convert the description and resave
	*/
	public function phase3($cpost){
		$m="";
		
		// resolve the descriptions
		$desc = $cpost->get("ent_curly_desc");	// should be array
		$cpost->set("post_content", $this->wpRender($desc));
		$cpost->set("post_excerpt", $this->wpRender($desc));
		
		$cpost->on_update(2);		// re-save it, checking the custom fields and ensuring consistency
		$m.="<br />Phase 3 on ".$cpost->show();
		return $m;
	}
	/**
	* check if the actual_date of cpost corresponds to the date of an event, if so add that event to cpost.
	*/
	public function addEvent($cpost){
		global $wpdb;
		$m="<p>addEvent to ".$cpost->show();
		$picdate = $cpost->get("actual_date");
		$s = "select P.ID from ".$wpdb->postmeta." PM, ".$wpdb->posts." P 
		where P.ID = PM.post_id 
		and P.post_type = 'fs_event' and P.post_status='publish' 
		and PM.meta_key = 'actual_date' and PM.meta_value=%s;";
		$res = $wpdb->get_col($wpdb->prepare($s,$picdate));
			//echo "<p>-+-+-+-+-+ addevent SQL=".$wpdb->prepare($s,$picdate);
		if (count($res)!=1) {
			$m.=" Found ".count($res)." matching events, nothing done";
			//echo "<p>-+-+-+-+-+ addevent doing nothing - picdate=".$picdate.", res=".print_r($res,true);
			return;
		}
		//echo "<p>-+-+-+-+-+ addevent adding ".$res[0];
		$cpost->set("event",$res[0]);
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
		  $pdata = self::get_postdata_by_entref($entref);
		  if ($pdata) return $pdata[0];
		  return null;
	  }
	  /**
	  * @return two element array or nothing
	  */
	  static function get_postdata_by_entref($entref){
		  global $wpdb;
		  $s = "select post_name, post_type from ".$wpdb->postmeta." PM,
			".$wpdb->posts." P 
			where P.ID = PM.post_id and P.post_status = 'publish' and 
			meta_key = 'ent_ref' and meta_value=%s;";
		  $pid = $wpdb->get_row($wpdb->prepare($s,$entref),ARRAY_N);
		  return $pid;
	  }

}
