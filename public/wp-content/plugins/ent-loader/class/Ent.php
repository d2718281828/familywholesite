<?php
namespace EntLoader;

class Ent  {
	
	protected $key;
	protected $type = "";
	protected $sourcedir;
	protected $reldir;
	protected $size = 0;
	
	public function __construct($filename, $reldir, $fulldir){
		$this->reldir = $reldir;
		$this->sourcedir = $fulldir;
		$this->key = str_replace(".txt","",strtolower($filename));
		$this->getit();
	}
	public function key(){
		return $this->key;
	}
	protected function getit(){
		$content = file_get_contents($this->sourcedir);
		$this->size = strlen($content);
	}
	public function show(){
		return $this->key.'-'.$this->size.'('.$this->sourcedir.')';
	}


}
