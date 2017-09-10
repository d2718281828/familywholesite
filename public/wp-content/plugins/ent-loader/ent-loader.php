<?php
/*
Plugin Name: Ent Loader
Plugin URI:
Description: Loading stuff exported from the ent library
Author: Derek Storkey
Version: 0.1
Author URI:
*/

namespace EntLoader;
use CPTHelper\CptHelper;
use CPTHelper\FieldHelper;
use CPTHelper\DateHelper;
use CPTHelper\CPTSelectHelper;
use CPTHelper\SelectHelper;


class EntLoader {
	
	protected $input;
	protected $numfiles = 0;

  public function __construct(){
	  add_action("init", [$this,"init"]);
	  $up = wp_upload_dir();
	  $this->input = $up["path"]."/nodes";
  }
  public function init(){
	  if (is_admin()) $this->wp_init();
  }
  protected function wp_init(){
	  include("class/EntLoadHelp.php");
	  $admin = new EntLoadHelp($this);
  }
  protected function loadStart(){
	  $this->numfiles = 0;	  
  }
  public function load($dir = null){
	  if ($dir){
		  $ddir = $dir;
	  } else {
		  $ddir = $this->input;
		  $this->loadStart();
	  }
	  $m = "<ul>";
	  
	  if (WP_DEBUG) error_log("Loading Ents from ".$ddir);
	  $list = scandir($ddir);
	  foreach ($list as $fil){
		  if ($fil=='.' || $fil=='..') continue;
		  $full = $ddir.'/'.$fil;
		  if (is_dir($full)){
			  $m.=$this->load($full);
		  } else {
			  $m.= "<li>".$fil."</li>";
		  }
	  }
	  return $m."</ul>";
  }

}

$ent_loader = new EntLoader();

 ?>
