<?php
namespace EntLoader;

class Ent  {
	
	protected $key;
	protected $type = "";
	protected $sourcedir;
	protected $reldir;
	protected $size = 0;
	protected $firstline;
	protected $numlines = 0;
	public $props = [];
	protected $wanted = false;
	protected $gender=null;  // only applicable to people. Set during the ancestor process
	protected $virtual = false;	// virtual is for those that are being created, not read from disk
	protected $media = [];		// each element is a triplet describing the file
	protected $tags = [];		// each element is a cpost for the item to tag this with
	protected $created = false;	// boolean has it been created already? Set by exists()
	
	public function __construct($filename, $reldir = null, $fulldir = null){
		$this->key = self::makeKey($filename);
		if ($fulldir){
			$this->addFile($filename, $reldir, $fulldir);
		} else {
			$this->virtual = true;
		}
	}
	public function addFile($filename, $reldir = null, $fulldir = null){
		// if it is the descriptor file, then save it, otherwise, save up the media file details
		if (substr($filename,-4)==".txt") {
			$this->reldir = $reldir;
			$this->sourcedir = $fulldir;
			$this->getit($filename);
		} else {
			$this->media[] = [$filename, $reldir, $fulldir];
		}
	}
	public function key(){
		return $this->key;
	}
	public function setWanted($wanted=true){
		$this->wanted = $wanted;
	}
	public function isWanted(){
		return $this->wanted;
	}
	public function exists(){
		global $wbdp;
		$s = "select post_id from ".$wpdb->postmeta." where meta_key = 'ent_ref' and meta_value = %s";
		$res = $wpdb->get_col($wpdb->prepare($s,$this->key));
		$this->created = (count($res)>0);
		return $this->created;
	}
	/**
	* Read the file to bring in this ent
	*/
	protected function getit($fname){
		$content = file_get_contents($this->sourcedir.'/'.$fname);
		$this->size = strlen($content);
		$lines = explode("\n",$content);
		$this->firstline = $lines[0];
		$this->numlines = count($lines);
		$this->digest($lines);
		$this->digestAtts();
	}
	protected function digest($lines){
		$lastprop = "";
		$this->props["index"] = [];
		
		foreach($lines as $line){
			if ($this->isBadLine($line)) continue;
			$l = rtrim($line);		// remove any residual line end crap
			if (substr($l,0,1)=='<'){
				$etag = strpos($l,'>');
				if ($etag!==false){
					$last=substr($l,$etag+1);
					$prop = strtolower(substr($l,1,$etag-1));
					$lastprop = $prop;
					if ($prop=="index"){
						$this->props[$prop][] = explode("<x>", $last);
					} else $this->props[$lastprop]=$last;
				}
			} else {
				//echo "<br>line 78 ".$lastprop."=".$l;
				if ($l && $lastprop!="index" &&  $lastprop!="") $this->props[$lastprop].="\n".$l;
			}
		}
	}
	protected function isBadLine($str){
		$bad = (strlen($str)==0) || ((strlen($str)==1) && (ord($str)==26));
		return $bad;
		// leave this here in case we encounter other bad inputs
		$m = "";
		for ($k=0; $k<strlen($str); $k++) $m.=".".ord(substr($str,$k,1));
		echo "<br>".$str." ".strlen($str)." ".$m.($bad?" BAD":" GOOD");
	}
	public function setMale($ismale){
		$this->gender = $ismale ? "M" : "F";
	}
	public function getGender(){
		return $this->gender;
	}
	public function getType(){
		return $this->type;
	}
	public function get($prop){
		return isset($this->props[$prop]) ? $this->props[$prop] : null;
	}
	/**
	* get all the properties where the property name starts with $start - it needs to be a prop=>val map
	*/
	public function getPropsLike($start){
		$res = [];
		$len = strlen($start);
		foreach($this->props as $kee=>$val) {
			if (substr($kee,0,$len)==$start) $res[$kee] = $val;
		}
		return $res;
	}
	/**
	* return the full path of the media file, if it is an image, if local on the server. 
	* This would be the featured image if it is an image
	* @return string full image path or null if none
	*/
	public function getImageFile(){
		$f = $this->getMediaFile();
		$dot = strrpos($f, ".");
		if ($dot) {
			$ext = substr($f,$dot);
			if ($ext = ".jpg" || $ext = ".gif" || $ext = ".jpeg") return $f;
		}
		return null;
	}
	/**
	* return the full path of the media file, if local on the server. 
	* @return string full file path or null if none
	*/
	public function getMediaFile(){
		foreach($this->media as $media){
			$fn = $media[0];
			if (strpos($fn,"_t.")===false) return $media[2]."/".$fn;
		}
		return null;
	}
	public function set($prop,$val){
		//echo "<br/>Setting ent property ".$this->key."-".$prop."=".$val;
		$this->props[$prop] = $val;
	}
	/**
	* This needs to be called from outside, later (cant remember  why)
	*/
	public function reorg(){
		if (isset($this->props["type"])) $this->type = $this->props["type"];
	}
	/**
	* Any further special processing for different types of attribute. Applies to virtuals as well as reals.
	*/
	public function digestAtts(){
		// process the markup
		$for = ["description"];
		foreach ($for as $att){
			if (isset($this->props[$att])) $this->props[$att] = $this->parseMarkup($this->props[$att]);
		}
	}
	/**
	* This ent needs to be tagged with the entity represented by $cpost
	*/
	public function tagWith($cpost){
		$this->tags[] = $cpost;
	}
	/**
	* For description and other marked up fields, convert them into a sequence of pairs
	* @param $txt string containing my trademark markup language with curly brackets
	* @return list of pairs of [operation, argument]. For neat text, the operation is =
	* Process the values a bit, so a tags are split by colon
	*/
	protected function parseMarkup($txt){
		$o = [];
		$p = 0;
		while ($p < strlen($txt)){
			$lb = strpos($txt,"{",$p);
			if ($lb===false){
				$o[] = ["=", substr($txt,$p)];
				return $o;
			}
			if ($lb>$p) $o[] = $this->makePair("=", substr($txt,$p,$lb-$p));
			$rb = strpos($txt,"}",$lb+1);
			if ($rb===false) $rb = strlen($txt);
			$bl = strpos($txt," ",$lb+1);
			if ($bl===false || $bl > $rb) $arg = "";
			else $arg = substr($txt, $bl+1, $rb-$bl-1);
			$o[] = $this->makePair(substr($txt,$lb+1, $bl-$lb-1), $arg);
			$p = $rb+1;
		}
		return $o;
	}
	protected function makePair($op,$arg){
		switch($op){
			case 'a':
			return [$op, explode(":",$arg)];
			default:
			return [$op,$arg];
		}
	}
	// used for debugging
	public function show(){
		$m = $this->key.'-'.$this->size.'-'.$this->numlines.'('.$this->get("title").')'.$this->gender;
		if ($this->media) $m.=":MEDIA=".count($this->media);
		if ($pic=$this->getImageFile()) $m.="-pic=".$pic;
		if ($this->isWanted()) $m.="*";
		return $m;
	}
	public function showAll(){
		$m = "";
		foreach($this->props as $prop=>$val){
			if ($prop=="index"){
				$vv = "";
				foreach ($val as $entry) $vv.="<br />".implode("-",$entry);
			} elseif(!is_string($val)) {
 				$vv = print_r($val,true);
			} else $vv = htmlentities($val);
			$m.='<p><strong>'.$prop.'</strong> '.$vv.'</p>';
		}
		if ($this->gender) $m.='<p><strong>Gender</strong> '.$this->gender.'</p>';
		return $m;
	}
	public function thumb(){
		$m="x";
		foreach($this->media as $mediafile){
			if (substr($mediafile[0],-6,2)=="_t"){
				$ud = wp_upload_dir();
				return '<img src="'.$ud["baseurl"]."/album".$mediafile[1]."/".$mediafile[0].'">';
			}
			$m.=" ".substr($mediafile[0],-6,2);
		}
		return $m;
	}
	static function makeKey($filename){
		$point = strrpos($filename, ".");
		$fn = ($point===false) ? $filename : substr($filename,0, $point);
		$fn = strtolower($fn);
		if (substr($fn,-2)=="_t") $fn = substr($fn,0,strlen($fn)-2);
		return $fn;
	}


}
