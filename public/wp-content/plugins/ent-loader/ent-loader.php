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
require_once("class/EntCPost.php");

class EntLoader {
	
	protected $input;
	protected $numfiles = 0;
	protected $set = [];		// Ent objects read from the node files
	protected $cposts = [];		// CPost objects, before creation

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
  //phase 1 - from nodes files to CPost pre-entries
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
	  $m.="</ul>";		// we dont need the full list right now.
	  
	  // pre-filtering
	  $this->get("violet")->setMale(false);
	  $this->setAncs("paulinst");
	  $this->setDescs("violet",5);
	  $this->setGenders();
	  $this->wantedEvents();
	  
	  foreach($this->set as $id=>$obj) $obj->reorg();
	  
	  $this->build();
	  $m = $this->phase1();		// initial WP create of everything.
	  
	  $m.= $this->listWanted();
	  return $m;
  }
  protected function report3(){
	  $m = $this->cposts["neils"]->showAllPend();
	  $m.= $this->cposts["derek"]->showAllPend();
	  $m.= $this->cposts["marians"]->showAllPend();
	  return $m;
  }
  protected function report2(){
  
	  $m = "";
	  foreach($this->cposts as $id=>$cpost) $m.="<br />".$cpost->show()." - ".$this->end2($this->set[$id]->get("type"));
	  return $m;
  }
  protected function end2($str){
	$m = ord(substr($str,-2,1));
	$m.= ".".ord(substr($str,-1,1));
	return $m;
  }
  protected function report(){
  
	  $m = $this->listWanted();
	  //$m.= $this->listTypes("event");
	  //$m.= $this->listTypes("place");
	  
	  $m.=$this->set["violet"]->showAll();
	  $m.=$this->set["yvonne"]->showAll();
	  
	  
	  return $m;
  }
  protected function build(){
	  
	  $convert = new EntCPost($this);

	  foreach($this->set as $id=>$obj) {
		  if (!$obj->isWanted()) continue;
		  $this->cposts[$id] = $convert->make($obj);
	  }
  
  }
  protected function phase1(){
	  $m = "";
	  $cp = $this->cposts["neils"];
	  $rc = $cp->create();
	  $m.= "<br/>neils ".( $rc===false ? $cp->error_message : $rc); 
	  return $m;
  }
  public function get($who){
      return isset($this->set[$who]) ? $this->set[$who] : null;
  }
  // clean up input data
  protected function setGenders(){
	  $males=["brianhe","alanmit","alex","benben","calebs","chrismit","danst","davben","edwardt","elijah","ericm","jackn","jakell",
	  "jamess","jimnay","joelst","johnbus","johnll","johns","johnst","jonathoh","kieran","laurben","markmac","maxn",
	  "natll","nobu","philtur","tobben","torin","zadok"];
	  
	  
	  $females=["akina","haruna","helens","anna","annies","bethany","charlst","chizuko","chloest","daphneg","doreens","doriss",
	  "elaines","emma","flis","hazelst","heathst","hollyll","ionamit","joans","karas","karenst","katiet","kerryl","kimst",
	  "laurapk","maja","marina","mollyn","rhians","yvonne",];
	  
	  
	  foreach ($males as $m) $this->get($m)->setMale(true);
	  foreach ($females as $f) $this->get($f)->setMale(false);
  }
  public function wantedEvents(){
	  $events = ["slub","vvcaleb","wedpsan","vvwpedor","vvwmarhe","vvwjonpa","vvwaldor",];
	  foreach ($events as $m) $this->get($m)->setWanted();
  }
  protected function listWanted(){
	  $m="<ul>";
	  foreach($this->set as $id=>$obj) if ($obj->isWanted()) $m.='<li>'.$obj->show().'</li>';
	  return $m.'</ul>';
  }
  protected function listTypes($type){
	  $m="<ul>";
	  foreach($this->set as $id=>$obj) {
		  if ($obj->getType()==$type) $m.='<li>'.$type.": ".$obj->show().'</li>';
	  }
	  return $m."</ul>";
  }
  protected function setDescs($who,$depth){
	  $anc = $this->get($who);
	  $spo=$this->spouseOf($anc);
	  error_log("Spouse of $who is $spo");
	  if ($spo){
		  $spob = $this->get($spo);
		  $spob->setWanted();
		  $g = $anc->getGender();
		  if ($g=="M") $spob->setMale(false);
		  if ($g=="F") $spob->setMale(true);
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
	  if ($spo=$pers->get("married_to")) return $spo;
	  $persid = $pers->key();
	  foreach($this->set as $id=>$obj) if ($obj->get("married_to")==$persid) return $id;
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
  }

}

$ent_loader = new EntLoader();

 ?>
