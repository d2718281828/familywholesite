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
	}
	public function show(){
		return $this->key.'-'.$this->size.'-'.$this->numlines.'('.htmlentities(trim($this->firstline)).')';
	}


}
