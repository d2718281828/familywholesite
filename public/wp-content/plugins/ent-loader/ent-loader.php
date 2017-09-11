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
require_once("class/Ent.php");


class EntLoader {
	
	protected $input;
	protected $numfiles = 0;
	protected $set = [];

  public function __construct(){
	  add_action("init", [$this,"init"]);
	  $up = wp_upload_dir();
	  $this->input = $up["basedir"]."/nodes";
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
	  $this->set = [];
  }
  public function load($dir = null){
	  if ($dir){
		  $ddir = $dir;
	  } else {
		  $ddir = "";
		  $this->loadStart();
	  }
	  $m = "<ul>";
	  
	  $adir = $this->input.'/'.$ddir;
	  
	  if (WP_DEBUG) error_log("Loading Ents from ".$adir);
	  $list = scandir($adir);
	  foreach ($list as $fil){
		  if ($fil=='.' || $fil=='..') continue;
		  $full = $adir.'/'.$fil;
		  if (is_dir($full)){
			  $m.=$this->load($full);
		  } else {
			  $z = new Ent($fil, $ddir, $adir);
			  $this->set[$z->key()] = $z;
			  $m.= "<li>".$z->show()."</li>";
		  }
	  }
	  $m.="</ul>";
	  // we dont need the full list right now.
	  $this->get("violet")->setMale(false);
	  $this->setAncs("paulinst");
	  $this->setDescs("violet",5);
	  $m = $this->listWanted();
	  
	  $m.=$this->set["violet"]->showAll();
	  
	  
	  return $m;
  }
  public function get($who){
      return isset($this->set[$who]) ? $this->set[$who] : null;
  }
  protected function listWanted(){
	  $m="<ul>";
	  foreach($this->set as $id=>$obj) if ($obj->isWanted()) $m.='<li>'.$obj->show().'</li>';
	  return $m.'</ul>';
  }
  protected function setDescs($who,$depth){
	  $anc = $this->get($who);
	  $spo=$this->spouseOf($anc);
	  error_log("Spouse of $who is $spo");
	  if ($spo){
		  $spo->setWanted();
	  }
	  foreach($this->set as $id=>$obj) {
		  $mum = $obj->get("mother");
		  $dad = $obj->get("father");
		  if ($who==$mum) {
			  $obj->setWanted();
			  $anc->setMale(false);
			  if ($depth>0) $this->setDescs($id,$depth-1);
		  }
		  if ($who==$dad) {
			  $obj->setWanted();
			  $anc->setMale(true);
			  if ($depth>0) $this->setDescs($id,$depth-1);
		  }
	  }
  }
  public function spouseOf($pers){
	  if ($spo=$pers->get("spouse")) return $spo;
	  $persid = $pers->key();
	  foreach($this->set as $id=>$obj) if ($obj->get("spouse")==$persid) return $id;
	  return null;
  }
  protected function setAncs($who){
	  $person = $this->get($who);
	  $person->setWanted();
	  if ($mum=$person->get("mother")) {
		  error_log("mother of ".$who." is ".$mum);
		  $this->get($mum)->setMale(false); 
		  $this->setAncs($mum);
	  }
	  if ($dad=$person->get("father")) {
		  error_log("father of ".$who." is ".$dad);
		  $this->get($dad)->setMale(true); 
		  $this->setAncs($dad);
	  }
	  if ($spo=$this->spouseOf($person)) {
		  error_log("spouse of ".$who." is ".$spo);
		  $this->setAncs($spo);
	  }
  }

}

$ent_loader = new EntLoader();

 ?>
