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
	
	public function __construct($filename, $reldir, $fulldir){
		$this->reldir = $reldir;
		$this->sourcedir = $fulldir;
		$this->key = str_replace(".txt","",strtolower($filename));
		$this->getit($filename);
	}
	public function key(){
		return $this->key;
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
		foreach($lines as $line){
			$l = rtrim($line);		// remove any residual line end crap
			if (substr($l,1,1)=='<'){
				$etag = strpos($l,'>');
				if ($etag!==false){
					$prop = strtolower(substr($l,1,$etag-2));
					$lastprop = $prop;
					$this->props[$lastprop]=substr($l,$etag+1);
				}
			} else {
				$this->props[$lastprop].="\n".$l;
			}
		}
	}
	public function show(){
		return $this->key.'-'.$this->size.'-'.$this->numlines.'('.htmlentities(trim($this->firstline)).')';
	}
	public function showAll(){
		$m = "";
		foreach($this->props as $prop=>$val){
			$m.='<p><strong>'.$prop.'</strong>'.htmlentities($val).'</p>';
		}
		return $m;
	}


}
