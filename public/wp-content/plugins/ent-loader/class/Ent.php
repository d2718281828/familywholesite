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
	
	public function __construct($filename, $reldir, $fulldir){
		$this->reldir = $reldir;
		$this->sourcedir = $fulldir;
		$this->key = str_replace(".txt","",strtolower($filename));
		$this->getit($filename);
	}
	public function key(){
		return $this->key;
	}
	public function setWanted(){
		$this->wanted = true;
	}
	public function isWanted(){
		return $this->wanted;
	}
	protected function getit($fname){
		$content = file_get_contents($this->sourcedir.$fname);
		$this->size = strlen($content);
		$lines = explode("\n",$content);
		$this->firstline = $lines[0];
		$this->numlines = count($lines);
		$this->digest($lines);
	}
	protected function digest($lines){
		$lastprop = "";
		$this->props["index"] = [];
		
		foreach($lines as $line){
			$l = rtrim($line);		// remove any residual line end crap
			if (substr($l,0,1)=='<'){
				$etag = strpos($l,'>');
				if ($etag!==false){
					$last=substr($l,$etag+1);
					$prop = strtolower(substr($l,1,$etag-1));
					$lastprop = $prop;
					if ($prop=="index"){
						$this->props[$lastprop][] = explode("<x>", $last);
					} else $this->props[$lastprop]=$last;
				}
			} else {
				$this->props[$lastprop].="\n".$l;
			}
		}
	}
	public function setMale($ismale){
		$this->gender = $ismale ? "M" : "F";
	}
	public function getGender(){
		return $this->gender;
	}
	public function get($prop){
		return isset($this->props[$prop]) ? $this->props[$prop] : null;
	}
	public function show(){
		return $this->key.'-'.$this->size.'-'.$this->numlines.'('.$this->get("title").')'.$this->gender;
	}
	public function showAll(){
		$m = "";
		foreach($this->props as $prop=>$val){
			if ($prop=="index"){
				$vv = "";
				foreach ($val as $entry) $vv.="<br />".implode("-",$entry);
			} else $vv = htmlentities($val);
			$m.='<p><strong>'.$prop.'</strong> '.$vv.'</p>';
		}
		if ($this->gender) $m.='<p><strong>Gender</strong> '.$this->gender.'</p>';
		return $m;
	}


}
